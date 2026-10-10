<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ImageProviderInterface;
use App\Services\AI\DTO\ImageGenerationRequest;
use App\Services\AI\DTO\ImageGenerationResult;
use App\Services\ActivityLogService;
use App\Services\AgentAiCredentialPool;
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
     * Perform an explicit live credential smoke test. Agent Kit has no separate
     * auth-only endpoint, so this intentionally consumes one live image request.
     */
    public function testCredential(\App\Models\AgentAiCredential $credential): array
    {
        // A live image smoke test can take longer than PHP's default 30-second
        // web-request limit. Align the request budget with the subprocess timeout.
        $processTimeout = max(30, (int) config('services.agent_ai.timeout', 300));
        @set_time_limit($processTimeout + 30);

        $output = storage_path('app/agent-ai/tests/' . $credential->id . '-' . uniqid('', true) . '.png');
        $promptFile = storage_path('app/agent-ai/tests/' . $credential->id . '-' . uniqid('', true) . '.txt');

        try {
            $directory = dirname($output);

            if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException('Folder Agent AI test tidak dapat dibuat.');
            }

            if (@file_put_contents($promptFile, 'Minimal premium product hero for a motorcycle spare part package, clean studio lighting, no text.') === false) {
                throw new RuntimeException('Prompt Agent AI test tidak dapat dibuat.');
            }

            $arguments = [
                $this->binary(),
                '-m',
                $this->module(),
                '--prompt-file', $promptFile,
                '--live',
                '--auth-provider', 'env',
                '--token-env', 'CHATGPT_CODEX_ACCESS_TOKEN',
                '--image-model', 'gpt-image-2.5-sunburst',
                '--quality', 'low',
                '--size', '1024x1024',
                '--output-format', 'png',
                '--out', $output,
                '--json',
            ];

            [$exitCode, $stdout, $stderr] = $this->runProcess(
                $arguments,
                (string) $credential->access_token,
                (int) config('services.agent_ai.timeout', 300),
            );

            $detail = trim($stderr) !== '' ? trim($stderr) : trim($stdout);
            $detail = $detail !== '' ? mb_substr($detail, -2000) : null;

            // This specific HTTP 400 is a Codex Responses API/tool-routing
            // incompatibility in the AgentKit client, not evidence of a bad token.
            // Keep a newly imported account pending so it can be retried after the
            // upstream client/backend compatibility is resolved.
            if (
                $exitCode !== 0
                && $detail !== null
                && str_contains(strtolower($detail), "tool choice 'image_generation' not found in 'tools' parameter")
            ) {
                $message = 'Token belum dapat divalidasi: endpoint Codex menolak tool image_generation (HTTP 400). Ini masalah kompatibilitas AgentKit/Codex, bukan bukti token salah. Credential tetap Pending Validation dan tidak dimasukkan ke pool aktif.';

                // A credential may have been enabled by an earlier test attempt
                // before this compatibility error was classified. Enforce the
                // invariant every time: unsupported tool routing can never leave
                // the credential enabled or eligible for generation.
                $credential->forceFill([
                    'is_active' => false,
                    'status' => 'pending_validation',
                    'cooldown_until' => null,
                    'last_error_type' => 'codex_image_tool_unsupported',
                    'last_error' => mb_substr($message, 0, 2000),
                    'last_exit_code' => $exitCode,
                    'updated_at' => now(),
                ])->save();

                $this->activity->error(
                    action: 'agent_credential_test',
                    category: 'api',
                    title: 'Agent credential test terhambat kompatibilitas Codex.',
                    description: $message,
                    metadata: [
                        'provider' => $this->name(),
                        'credential_id' => $credential->id,
                        'credential_name' => $credential->name,
                        'classification' => 'codex_image_tool_unsupported',
                        'exit_code' => $exitCode,
                    ],
                );

                return [
                    'success' => false,
                    'status' => 'provider_incompatible',
                    'message' => $message,
                    'exit_code' => $exitCode,
                ];
            }

            if ($exitCode !== 0 || ! is_file($output) || filesize($output) === 0) {
                $classification = app(AgentAiCredentialPool::class)->reportFailure(
                    $credential->id,
                    auth()->id(),
                    $detail ?: 'Agent credential test gagal tanpa detail.',
                    $exitCode,
                );

                $this->activity->error(
                    action: 'agent_credential_test',
                    category: 'api',
                    title: 'Agent credential test gagal.',
                    description: $detail ?: 'Agent credential test gagal.',
                    metadata: [
                        'provider' => $this->name(),
                        'credential_id' => $credential->id,
                        'credential_name' => $credential->name,
                        'classification' => $classification['reason'] ?? 'agent_request_failed',
                        'exit_code' => $exitCode,
                    ],
                );

                return [
                    'success' => false,
                    'status' => $classification['reason'] ?? 'agent_request_failed',
                    'message' => $detail ?: 'Agent credential test gagal.',
                    'exit_code' => $exitCode,
                ];
            }

            app(AgentAiCredentialPool::class)->reportSuccess($credential->id, auth()->id());

            // A newly imported Codex session stays out of the rotation pool until
            // this explicit live image smoke test proves AgentKit compatibility.
            if ($credential->status === 'pending_validation') {
                $credential->forceFill([
                    'is_active' => true,
                    'status' => 'active',
                    'cooldown_until' => null,
                    'last_error_type' => null,
                    'last_error' => null,
                ])->save();
            }

            $this->activity->success(
                action: 'agent_credential_test',
                category: 'api',
                title: 'Agent credential test berhasil.',
                description: 'Credential berhasil digunakan untuk live smoke test.',
                metadata: [
                    'provider' => $this->name(),
                    'credential_id' => $credential->id,
                    'credential_name' => $credential->name,
                    'model' => 'gpt-image-2.5-sunburst',
                ],
            );

            return [
                'success' => true,
                'status' => 'active',
                'message' => 'Agent credential berhasil digunakan untuk live smoke test.',
                'exit_code' => 0,
            ];
        } finally {
            if (is_file($promptFile)) {
                @unlink($promptFile);
            }

            if (is_file($output)) {
                @unlink($output);
            }
        }
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

        // Provider-level policy injection: keep branding and Template identity locked
        // even when a caller changes its orchestration or builds a provider request
        // through a different workflow.
        $prompt = $request->prompt;
        $roles = array_map(
            static fn ($reference): string => strtolower(trim((string) $reference->role)),
            $request->references,
        );
        $hasStoreLogo = in_array('store_logo', $roles, true)
            || in_array('logo', $roles, true)
            || in_array('branding', $roles, true);
        $hasTemplate = in_array('template', $roles, true)
            || in_array('layout', $roles, true)
            || in_array('template_master', $roles, true);
        $hasInstalled = in_array('installed', $roles, true)
            || in_array('installed_reference', $roles, true);

        $prompt .= "\n\nAGENTKIT CREATIVE PRODUCT-POSTER OVERRIDE — HIGHEST PRIORITY:\n"
            . "- REFERENCE ROLE CONTRACT: role identity is the authoritative product/package reference; role layout is a visual-style and Store-template reference, not a poster to copy pixel-for-pixel; role logo is the ONLY source of the official Store logo; role general is installed/use context only when supplied.\n"
            . "- CREATIVE ART DIRECTION: design a fresh, original, premium, high-impact marketplace product poster. Use the product photo to understand the real item, packaging, colors, shape, and details, but do NOT copy its original photo composition, background, promotional layout, badges, typography, or surrounding scene. Upgrade it with a more polished, professional and eye-catching concept.\n"
            . "- RICH VISUALS, NEVER PLAIN: use the canvas confidently with strong hierarchy and balanced, dense composition. Invent suitable visual elements such as a dramatic studio/garage background, red/black or product-matching accent colors, rim lighting, glow, speed lines, technical frames, layered panels, product podium, subtle textures, benefit icons, headline treatments, and a clear footer. Avoid empty backgrounds and generic minimal posters.\n"
            . "- AI MAY IDEATE MARKETING DECORATIONS: create tasteful badges and callouts such as TOP BRAND, BEST SELLER, QUALITY PICK, FAST SHIPPING, COD, or product benefits when they fit the design. Keep text short, readable, and relevant. Do not invent exact prices, numeric discounts, technical specifications, compatibility, certifications, guarantees, or factual claims that cannot be verified from the request/reference. Treat promotional badges as editable design ideas, not proof of real-world status.\n"
            . "- TEMPLATE INFLUENCE, NOT COPYING: carry over the selected Template's brand feel, palette, typography direction, and premium marketplace intent, while allowing a new composition and new decorative ideas for each product. Do not rigidly duplicate the Template example poster or copy its example product. Keep a coherent recognizable style across generations for the same Store.\n"
            . "- EXACT USER TITLE: if a custom title is supplied, use that wording as the main headline, preserving spelling and wording. Design around it; do not replace it with a different headline.\n"
            . "- ABSOLUTE LOGO SOURCE FIREWALL: the dedicated official Store logo reference is the ONLY source for the Store logo. Use the supplied logo artwork itself; never extract, trace, redraw, approximate, typeset, reconstruct, or substitute it using text from the product photo, installed photo, or Template. Manufacturer branding may remain only as part of the real physical product/packaging.\n"
            . ($hasStoreLogo
                ? "- REQUIRED LOGO: visibly include the official Store logo as actual artwork, crisp and undistorted. Do not replace it with the shop name typed as text. Keep Store logo identity consistent across every generation; placement may adapt to the new composition.\n"
                : "- REQUIRED LOGO INPUT IS MISSING: do not invent a logo; generation must stop until the official Store logo is attached.\n")
            . "- MODE LOCK: if an installed reference is present, use it to represent genuine installation/use and preserve the product's visible real-world details, while creatively integrating the product packaging when useful. If no installed reference is present, do not invent an installed-on-motorcycle view; make the product/package the hero and create an original studio-style product scene.\n"
            . "- OUTPUT CANVAS: generate a square 1024x1024 composition. Fill the canvas deliberately; keep the official Store logo and main headline clear, and do not crop important product details or essential text.\n"
            . "- Product identity, exact user title, and official Store logo must remain faithful. All other layout and decorative design may be creatively upgraded. Never add duplicate Store logos or fake alternative wordmarks.";

        $negativePrompt = trim((string) ($request->metadata['negative_prompt'] ?? ''));
        if ($negativePrompt !== '') {
            $prompt .= "\n\nAvoid: " . $negativePrompt;
        }

        return $prompt;
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
