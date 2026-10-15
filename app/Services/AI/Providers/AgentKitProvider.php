<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ImageProviderInterface;
use App\Services\AI\DTO\ImageGenerationRequest;
use App\Services\AI\DTO\ImageGenerationResult;
use App\Services\ActivityLogService;
use App\Services\AgentAiCredentialPool;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AgentKitProvider implements ImageProviderInterface
{
    public function __construct(
        private readonly AgentAiCredentialPool $credentialPool,
        private readonly ActivityLogService $activity,
    ) {
    }

    public function name(): string
    {
        return 'agentkit';
    }

    public function supportsModel(string $model): bool
    {
        $model = strtolower(trim($model));

        return in_array($model, [
            'gpt-image-2.5-sunburst',
            'gpt-image-2.5-flare',
            'openai/gpt-image-2.5-sunburst',
            'openai/gpt-image-2.5-flare',
        ], true);
    }

    /**
     * Validate the Codex session with a minimal text-only request. This verifies
     * real server-side token acceptance without invoking image generation or
     * consuming image-generation quota.
     */
    public function testCredential(\App\Models\AgentAiCredential $credential): array
    {
        $token = (string) $credential->access_token;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'text/event-stream',
            'User-Agent' => 'codex_cli_rs/0.0.0',
            'originator' => 'codex_cli_rs',
        ];

        // Codex expects the account context when it is present in the OAuth JWT.
        // Only decoded public claims are used; the token itself is never logged.
        try {
            $parts = explode('.', $token);
            if (count($parts) >= 2 && $parts[1] !== '') {
                $payload = strtr($parts[1], '-_', '+/');
                $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
                $decoded = base64_decode($payload, true);
                $claims = is_string($decoded) ? json_decode($decoded, true) : null;

                if (is_array($claims)) {
                    $auth = $claims['https://api.openai.com/auth'] ?? [];
                    if (is_array($auth)) {
                        $accountId = $auth['chatgpt_account_id'] ?? null;
                        if (is_string($accountId) && $accountId !== '') {
                            $headers['ChatGPT-Account-ID'] = $accountId;
                        }

                        $residency = $auth['chatgpt_data_residency'] ?? $auth['chatgpt_compute_residency'] ?? null;
                        if (is_string($residency) && trim($residency) !== '') {
                            $headers['x-openai-internal-codex-residency'] = trim($residency);
                        }
                    }

                    $expiresAt = $claims['exp'] ?? null;
                    if (is_numeric($expiresAt) && (int) $expiresAt <= time()) {
                        $credential->forceFill([
                            'is_active' => false,
                            'status' => 'invalid',
                            'cooldown_until' => null,
                            'last_error_type' => 'token_expired',
                            'last_error' => 'Access token sudah kedaluwarsa. Login Codex ulang dan tambahkan akun kembali.',
                            'last_exit_code' => null,
                        ])->save();

                        return [
                            'success' => false,
                            'status' => 'invalid_credential',
                            'message' => 'Access token sudah kedaluwarsa. Login Codex ulang untuk memperbarui akun.',
                        ];
                    }
                }
            }
        } catch (\Throwable) {
            // Continue with the server-side probe so the endpoint can provide
            // the authoritative response for malformed or changed JWT formats.
        }

        try {
            // A tiny text-only Responses request verifies that the OAuth token is
            // accepted by Codex without invoking the image-generation endpoint.
            $response = Http::withHeaders($headers)
                ->timeout(25)
                ->post('https://chatgpt.com/backend-api/codex/responses', [
                    'model' => 'gpt-5.5',
                    'instructions' => 'Reply with OK.',
                    'input' => [[
                        'type' => 'message',
                        'role' => 'user',
                        'content' => [[
                            'type' => 'input_text',
                            'text' => 'hi',
                        ]],
                    ]],
                    'stream' => true,
                    'store' => false,
                ]);
        } catch (\Throwable $e) {
            $message = 'Tidak dapat menghubungi endpoint validasi Codex. Periksa koneksi host AgentKit, lalu coba lagi.';
            $this->activity->error(
                action: 'agent_credential_test',
                category: 'api',
                title: 'Validasi sesi AgentKit gagal dijangkau.',
                description: $message,
                metadata: [
                    'provider' => $this->name(),
                    'credential_id' => $credential->id,
                    'classification' => 'codex_validation_unreachable',
                ],
            );

            return [
                'success' => false,
                'status' => 'validation_unreachable',
                'message' => $message,
            ];
        }

        $streamCompleted = false;
        $streamFailed = false;
        if ($response->successful()) {
            foreach (preg_split('/\\r?\\n\\r?\\n/', trim($response->body())) ?: [] as $eventBlock) {
                foreach (preg_split('/\\r?\\n/', $eventBlock) ?: [] as $line) {
                    if (! str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $event = json_decode(trim(substr($line, 5)), true);
                    if (! is_array($event)) {
                        continue;
                    }

                    $type = (string) ($event['type'] ?? '');
                    if ($type === 'response.completed') {
                        $streamCompleted = data_get($event, 'response.status') === 'completed';
                    } elseif (in_array($type, ['response.failed', 'response.incomplete', 'error'], true)) {
                        $streamFailed = true;
                    }
                }
            }
        }

        if ($response->successful() && $streamCompleted && ! $streamFailed) {
            app(AgentAiCredentialPool::class)->reportSuccess($credential->id, auth()->id());

            $credential->forceFill([
                'is_active' => true,
                'status' => 'active',
                'cooldown_until' => null,
                'last_error_type' => null,
                'last_error' => null,
                'last_exit_code' => null,
            ])->save();

            $message = 'Autentikasi Codex tervalidasi lewat request teks ringan. Akun aktif dan siap dipilih worker AgentKit; generate gambar tidak dijalankan.';
            $this->activity->success(
                action: 'agent_credential_test',
                category: 'api',
                title: 'Validasi token AgentKit berhasil.',
                description: $message,
                metadata: [
                    'provider' => $this->name(),
                    'credential_id' => $credential->id,
                    'credential_name' => $credential->name,
                    'validation' => 'codex_responses_text_probe',
                    'image_generation_tested' => false,
                    'http_status' => $response->status(),
                ],
                httpStatus: $response->status(),
            );

            return [
                'success' => true,
                'status' => 'active',
                'message' => $message,
                'validation' => 'codex_responses_text_probe',
                'image_generation_tested' => false,
            ];
        }

        $httpStatus = $response->status();
        $errorType = $response->successful()
            ? 'codex_validation_incomplete'
            : match ($httpStatus) {
            401 => 'invalid_credential',
            403 => 'provider_access_denied',
            429 => 'rate_limited',
            default => 'codex_validation_failed',
        };

        // Only a definitive 401 invalidates the token. A 403 can mean that this
        // endpoint or account is not permitted; it is not proof of a bad login.
        $updates = [
            'last_error_type' => $errorType,
            'last_error' => 'Codex session validation returned HTTP ' . $httpStatus . '.',
            'last_exit_code' => null,
        ];

        if ($httpStatus === 401) {
            $updates['is_active'] = false;
            $updates['status'] = 'invalid';
            $updates['cooldown_until'] = null;
        } elseif (! $credential->is_active && $credential->status === 'pending_validation') {
            $updates['status'] = 'pending_validation';
            $updates['cooldown_until'] = null;
        } elseif ($credential->is_active) {
            // Keep a previously validated account usable while recording a
            // non-auth failure in the read-only probe.
            $updates['status'] = 'active';
        }

        $credential->forceFill($updates)->save();

        $message = match ($httpStatus) {
            401 => 'Codex menolak autentikasi token. Login ulang diperlukan.',
            403 => 'Endpoint validasi Codex menolak akses (HTTP 403). Token tidak otomatis dianggap invalid; akun baru tetap Pending Validation.',
            429 => 'Endpoint validasi Codex membatasi request. Coba lagi nanti; token tidak otomatis dianggap invalid.',
            default => $response->successful()
                ? 'Codex menerima HTTP request tetapi validasi stream belum selesai. Akun tidak diaktifkan; coba Test Token lagi.'
                : 'Validasi sesi Codex gagal (HTTP ' . $httpStatus . '). Akun tidak diaktifkan oleh tes ini.',
        };

        $this->activity->error(
            action: 'agent_credential_test',
            category: 'api',
            title: 'Validasi token AgentKit gagal.',
            description: $message,
            metadata: [
                'provider' => $this->name(),
                'credential_id' => $credential->id,
                'credential_name' => $credential->name,
                'http_status' => $httpStatus,
                'classification' => $errorType,
                'validation' => 'codex_responses_text_probe',
                'image_generation_tested' => false,
            ],
        );

        return [
            'success' => false,
            'status' => $errorType,
            'message' => $message,
            'http_status' => $httpStatus,
        ];
    }

    public function generate(ImageGenerationRequest $request): ImageGenerationResult
    {
        $this->assertStoreLogoReference($request);

        $maxAttempts = max(1, min(
            (int) config('services.agent_ai.credential_retry_attempts', 3),
            5,
        ));
        $excludedCredentialIds = [];
        $lastError = null;
        $lastExitCode = null;
        $model = str_starts_with(strtolower($request->model), 'openai/')
            ? substr($request->model, 7)
            : $request->model;
        $promptFile = null;

        try {
            $promptFile = $this->promptFile($request);

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $credential = $this->credentialPool->acquire(
                    $request->userId,
                    $excludedCredentialIds,
                );

                if (! $credential) {
                    if ($lastError !== null) {
                        throw new RuntimeException($lastError);
                    }

                    throw new RuntimeException(
                        'Belum ada Agent AI credential aktif. Buka Settings → AI Provider → Agent AI.'
                    );
                }

                $excludedCredentialIds[] = (int) $credential['id'];
                $output = storage_path(
                    'app/agent-ai/' .
                    ($request->generationId ?: uniqid('generation-', true)) .
                    '-' . uniqid('', true) . '.png'
                );

                try {
                    $directory = dirname($output);

                    if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
                        throw new RuntimeException('Folder Agent AI output tidak dapat dibuat.');
                    }

                    $arguments = [
                        $this->binary(),
                        '-m',
                        $this->module(),
                        '--prompt-file', $promptFile,
                        '--live',
                        '--auth-provider', 'env',
                        '--image-model', $model,
                        '--quality', $request->quality,
                        '--size', $this->requestedCanvasSize($request),
                        '--action', 'generate',
                        '--output-format', $request->outputFormat,
                        '--out', $output,
                    ];

                    foreach ($request->references as $reference) {
                        $arguments[] = '--ref';
                        $arguments[] = $reference->path;
                        $arguments[] = '--ref-role';
                        $arguments[] = $this->normalizeRole($reference->role);
                    }

                    $this->activity->processing(
                        action: 'ai_provider_request',
                        category: 'api',
                        title: 'Agent AI request dimulai.',
                        description: sprintf(
                            'Percobaan %d/%d menggunakan credential %s untuk model %s.',
                            $attempt,
                            $maxAttempts,
                            $credential['name'],
                            $model
                        ),
                        metadata: [
                            'provider' => $this->name(),
                            'model' => $model,
                            'generation_id' => $request->generationId,
                            'agent_credential_id' => $credential['id'],
                            'agent_credential_name' => $credential['name'],
                            'attempt' => $attempt,
                            'max_attempts' => $maxAttempts,
                            'image_count' => $request->normalizedImageCount(),
                            'invocation_mode' => 'one_invocation_per_image',
                        ],
                    );

                    [$exitCode, $stdout, $stderr] = $this->runProcess(
                        $arguments,
                        $credential['key'],
                        (int) config('services.agent_ai.timeout', 300),
                    );

                    if ($exitCode !== 0 || ! is_file($output) || filesize($output) === 0) {
                        $detail = trim($stderr) !== '' ? trim($stderr) : trim($stdout);
                        $detail = $detail !== ''
                            ? mb_substr($detail, -2000)
                            : 'Agent AI invocation gagal tanpa detail.';
                        $lastError = $detail;
                        $lastExitCode = $exitCode;

                        // Use upstream AgentKit's Responses client and classify its
                        // actual backend error normally. Protocol errors must not be
                        // mistaken for proof that the user's OAuth token is invalid.
                        $classification = $this->credentialPool->reportFailure(
                            $credential['id'],
                            $request->userId,
                            $detail,
                            $exitCode,
                        );

                        $this->activity->error(
                            action: 'ai_provider_request',
                            category: 'api',
                            title: 'Agent AI request gagal.',
                            description: $detail,
                            metadata: [
                                'provider' => $this->name(),
                                'model' => $model,
                                'generation_id' => $request->generationId,
                                'agent_credential_id' => $credential['id'],
                                'agent_credential_name' => $credential['name'],
                                'attempt' => $attempt,
                                'max_attempts' => $maxAttempts,
                                'exit_code' => $exitCode,
                                'failure_reason' => $classification['reason'] ?? 'agent_request_failed',
                                'retryable' => (bool) ($classification['retry'] ?? false),
                            ],
                        );

                        if (! ($classification['retry'] ?? false) || $attempt >= $maxAttempts) {
                            $normalizedDetail = strtolower($detail);
                            if (
                                str_contains($normalizedDetail, "tool choice 'image_generation' not found in 'tools' parameter")
                                || (
                                    str_contains($normalizedDetail, 'tool_choice')
                                    && str_contains($normalizedDetail, 'image_generation')
                                    && str_contains($normalizedDetail, 'not found in')
                                )
                            ) {
                                throw new RuntimeException(
                                    'Backend Codex yang digunakan AgentKit saat ini tidak menerima tool image_generation pada endpoint Responses. Ini masalah kompatibilitas/kapabilitas backend, bukan bukti token salah. Generate dihentikan tanpa mencoba token lain. Provider AgentKit memerlukan jalur backend yang mendukung image_generation; Vercel tidak terpengaruh.'
                                );
                            }

                            throw new RuntimeException($detail);
                        }

                        continue;
                    }

                    $binary = file_get_contents($output);

                    if ($binary === false || $binary === '') {
                        $lastError = 'Agent AI output image tidak dapat dibaca.';
                        $lastExitCode = $exitCode;
                        $classification = $this->credentialPool->reportFailure(
                            $credential['id'],
                            $request->userId,
                            $lastError,
                            $exitCode,
                        );

                        if (! ($classification['retry'] ?? false) || $attempt >= $maxAttempts) {
                            throw new RuntimeException($lastError);
                        }

                        continue;
                    }

                    // AgentKit's CLI produces one output file per invocation. Mirror the
                    // Generator's requested image count instead of silently returning only
                    // the first image (Vercel receives the same count as the API's n field).
                    $images = [[
                        'b64_json' => base64_encode($binary),
                        'url' => null,
                        'provider' => $this->name(),
                        'model' => $model,
                    ]];
                    $requestedImageCount = $request->normalizedImageCount();

                    for ($imageIndex = 1; $imageIndex < $requestedImageCount; $imageIndex++) {
                        $extraOutput = storage_path(
                            'app/agent-ai/' .
                            ($request->generationId ?: uniqid('generation-', true)) .
                            '-image-' . ($imageIndex + 1) . '-' . uniqid('', true) . '.png'
                        );

                        try {
                            $extraDirectory = dirname($extraOutput);
                            if (! is_dir($extraDirectory) && ! @mkdir($extraDirectory, 0775, true) && ! is_dir($extraDirectory)) {
                                throw new RuntimeException('Folder Agent AI output tidak dapat dibuat.');
                            }

                            $extraArguments = $arguments;
                            $outputOption = array_search('--out', $extraArguments, true);
                            if ($outputOption === false || ! isset($extraArguments[$outputOption + 1])) {
                                throw new RuntimeException('Argumen output Agent AI tidak valid.');
                            }
                            $extraArguments[$outputOption + 1] = $extraOutput;

                            [$extraExitCode, $extraStdout, $extraStderr] = $this->runProcess(
                                $extraArguments,
                                $credential['key'],
                                (int) config('services.agent_ai.timeout', 300),
                            );

                            if ($extraExitCode !== 0 || ! is_file($extraOutput) || filesize($extraOutput) === 0) {
                                $extraDetail = trim($extraStderr) !== '' ? trim($extraStderr) : trim($extraStdout);
                                throw new RuntimeException(
                                    'Agent AI gagal membuat gambar ' . ($imageIndex + 1) . '/' . $requestedImageCount . ': ' .
                                    ($extraDetail !== '' ? mb_substr($extraDetail, -1500) : 'output image tidak tersedia.')
                                );
                            }

                            $extraBinary = file_get_contents($extraOutput);
                            if ($extraBinary === false || $extraBinary === '') {
                                throw new RuntimeException('Output Agent AI gambar ' . ($imageIndex + 1) . ' tidak dapat dibaca.');
                            }

                            $images[] = [
                                'b64_json' => base64_encode($extraBinary),
                                'url' => null,
                                'provider' => $this->name(),
                                'model' => $model,
                            ];
                        } finally {
                            if (is_file($extraOutput)) {
                                @unlink($extraOutput);
                            }
                        }
                    }

                    $this->credentialPool->reportSuccess($credential['id'], $request->userId);

                    $this->activity->success(
                        action: 'ai_provider_request',
                        category: 'api',
                        title: 'Agent AI request berhasil.',
                        description: sprintf('Agent backend mengembalikan %d image yang tervalidasi.', count($images)),
                        metadata: [
                            'provider' => $this->name(),
                            'model' => $model,
                            'generation_id' => $request->generationId,
                            'agent_credential_id' => $credential['id'],
                            'agent_credential_name' => $credential['name'],
                            'attempt' => $attempt,
                            'max_attempts' => $maxAttempts,
                            'image_count' => count($images),
                            'requested_image_count' => $requestedImageCount,
                            'invocation_mode' => 'one_invocation_per_image',
                        ],
                    );

                    return new ImageGenerationResult(
                        images: $images,
                        provider: $this->name(),
                        model: $model,
                        metadata: [
                            'agent_credential_id' => $credential['id'],
                            'agent_credential_name' => $credential['name'],
                            'agent_credential_source' => $credential['source'],
                            'attempt' => $attempt,
                            'http_status' => 200,
                            'image_count' => count($images),
                            'requested_image_count' => $requestedImageCount,
                        ],
                    );
                } finally {
                    if (is_file($output)) {
                        @unlink($output);
                    }
                }
            }

            throw new RuntimeException($lastError ?: 'Agent AI gagal setelah seluruh percobaan.');
        } finally {
            if ($promptFile && is_file($promptFile)) {
                @unlink($promptFile);
            }
        }
    }

    private function binary(): string
    {
        return (string) config('services.agent_ai.python_binary', 'python');
    }

    private function module(): string
    {
        return (string) config('services.agent_ai.module', 'gpt_image25_agent');
    }

    /**
     * Require the official Store logo as a real, readable image reference.
     * The application orchestrator resolves it from the Store attached to the
     * selected Template; providers must never silently proceed without it.
     */
    private function assertStoreLogoReference(ImageGenerationRequest $request): void
    {
        foreach ($request->references as $reference) {
            $role = strtolower(trim((string) $reference->role));
            if (
                in_array($role, ['store_logo', 'logo', 'branding'], true)
                && is_readable($reference->path)
            ) {
                return;
            }
        }

        throw new RuntimeException(
            'Generation dibatalkan: reference logo resmi Store tidak tersedia. Pilih Template dengan Store yang memiliki logo valid.'
        );
    }

    private function lockedPrompt(ImageGenerationRequest $request): string
    {
        $roles = array_map(
            static fn ($reference): string => strtolower(trim((string) $reference->role)),
            $request->references,
        );
        $hasStoreLogo = count(array_intersect($roles, ['store_logo', 'logo', 'branding'])) > 0;
        $hasInstalled = count(array_intersect($roles, ['installed', 'installed_reference'])) > 0;

        return $request->prompt
            . "\n\nAGENTKIT CREATIVE POLICY — mandatory:\n"
            . "- Create a fresh, premium, high-impact marketplace poster with a rich, deliberate composition—not a plain background. Use suitable lighting, depth, textures, graphic accents, frames, badges or concise callouts when compatible with the selected Template. Keep claims factual; never invent prices, discounts, specifications, compatibility, certifications, guarantees or unsupported benefits.\n"
            . "- Use the primary product reference as the sole authority for product identity and visible details. Preserve its real shape, proportions, materials, colors, packaging and branding. Never invent unseen geometry or replace it with another product.\n"
            . "- The Template reference controls brand feel, palette, typography direction and visual hierarchy, but its example product/text must not replace the current product/title. Keep a recognizable Store design system while adapting composition to the actual product.\n"
            . ($hasStoreLogo
                ? "- The dedicated Store-logo image is the ONLY source for the Store logo. Use the supplied artwork visibly and without distortion; never redraw, typeset, extract or substitute logos from product/installed photos or the Template. Manufacturer branding stays only on the physical product/packaging.\n"
                : "- Store logo reference is missing: do not invent one; generation must stop until the official logo is attached.\n")
            . "- Exact custom title: use the provided wording as the main headline without rewriting, translating, shortening or replacing it.\n"
            . ($hasInstalled
                ? "- An installed reference exists: use it only for truthful installation/use context and visibly include the installed product. Include the supplied product/packaging as a supporting visual when present.\n"
                : "- No installed reference exists: do not invent a motorcycle, vehicle, mounting or usage scene. Make the supplied product/package the hero; only use details visible in the source image.\n")
            . "- Use one cohesive advertisement, not a literal collage. Keep product identity, exact title, official Store branding and Template direction faithful; decorative layout may be creatively improved.";
    }

    private function promptFile(ImageGenerationRequest $request): string
    {
        $directory = storage_path('app/agent-ai/prompts');

        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder Agent AI prompt tidak dapat dibuat.');
        }

        $path = $directory . '/' .
            ($request->generationId ?: uniqid('prompt-', true)) .
            '-' . uniqid('', true) . '.txt';

        if (@file_put_contents($path, $this->lockedPrompt($request)) === false) {
            throw new RuntimeException('Prompt Agent AI tidak dapat disimpan sementara.');
        }

        return $path;
    }

    /**
     * AgentKit accepts custom canvases and the requested dimensions are not
     * guaranteed to equal the decoded output dimensions. Request a larger canvas
     * while preserving the selected aspect ratio, and record actual dimensions
     * separately when the result is saved.
     */
    private function requestedCanvasSize(ImageGenerationRequest $request): string
    {
        $ratio = (string) ($request->metadata['aspect_ratio'] ?? '');

        // AgentKit outputs must use the same fixed square canvas requested by the user.
        // Ignore a stale UI aspect-ratio value rather than accidentally asking for a portrait canvas.
        $size = '1024x1024';

        if ($ratio !== '') {
            return $size;
        }

        if (! preg_match('/^(\\d+)x(\\d+)$/', $request->size, $matches)) {
            return $request->size;
        }

        $width = (int) $matches[1];
        $height = (int) $matches[2];
        if ($width < 1 || $height < 1) {
            return $request->size;
        }

        $scale = 1.5;
        $scaledWidth = max(16, (int) (round(($width * $scale) / 16) * 16));
        $scaledHeight = max(16, (int) (round(($height * $scale) / 16) * 16));

        return $scaledWidth . 'x' . $scaledHeight;
    }

    private function normalizeRole(string $role): string
    {
        // Map the shared provider-neutral reference roles to AgentKit's supported
        // semantic roles. In particular, store_logo must never degrade to general.
        return match (strtolower(trim($role))) {
            'product', 'product_reference', 'primary_product' => 'identity',
            'template', 'template_reference', 'template_master' => 'layout',
            'store_logo', 'logo', 'brand', 'branding' => 'logo',
            'installed', 'installed_reference', 'in_use' => 'general',
            'identity', 'style', 'layout', 'general' => strtolower(trim($role)),
            default => 'general',
        };
    }

    /**
     * @return array{0:int,1:string,2:string}
     */
    private function runProcess(array $arguments, string $token, int $timeout): array
    {
        $command = implode(' ', array_map(
            static fn (string $argument): string => escapeshellarg($argument),
            $arguments
        ));

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $environment = getenv();
        if (! is_array($environment)) {
            $environment = [];
        }

        $environment['CHATGPT_CODEX_ACCESS_TOKEN'] = $token;
        $environment['PATH'] = $environment['PATH'] ?? '/usr/local/bin:/usr/bin:/bin';
        $environment['HOME'] = $environment['HOME'] ?? storage_path('app/agent-ai/home');
        $environment['USER'] = $environment['USER'] ?? get_current_user();

        // Add a package-path bridge so the installed upstream AgentKit modules
        // are discoverable. The bridge does not override client.py or the
        // upstream request protocol; Vercel provider code remains untouched.
        $agentKitTransportPath = base_path('packages/agentkit-codex-transport');
        $existingPythonPath = trim((string) ($environment['PYTHONPATH'] ?? ''));
        $environment['PYTHONPATH'] = $agentKitTransportPath
            . ($existingPythonPath !== '' ? PATH_SEPARATOR . $existingPythonPath : '');

        if (! is_dir($environment['HOME'])) {
            @mkdir($environment['HOME'], 0700, true);
        }

        $process = proc_open(
            $command,
            $descriptorSpec,
            $pipes,
            base_path(),
            $environment,
        );

        if (! is_resource($process)) {
            throw new RuntimeException('Agent AI process tidak dapat dimulai.');
        }

        fclose($pipes[0]);

        $startedAt = microtime(true);
        $stdout = '';
        $stderr = '';

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        while (true) {
            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';

            $status = proc_get_status($process);

            if (! $status['running']) {
                $exitCode = (int) $status['exitcode'];
                break;
            }

            if ((microtime(true) - $startedAt) >= $timeout) {
                proc_terminate($process, 15);
                usleep(250000);

                if (proc_get_status($process)['running']) {
                    proc_terminate($process, 9);
                }

                $exitCode = 124;
                $stderr .= "Agent AI process timeout after {$timeout}s.";
                break;
            }

            usleep(100000);
        }

        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return [$exitCode, $stdout, $stderr];
    }
}
