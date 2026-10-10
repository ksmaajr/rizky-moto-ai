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

        $prompt .= "\n\nAGENTKIT TEMPLATE-FIDELITY OVERRIDE — HIGHEST PRIORITY:\n"
            . "- REFERENCE ROLE CONTRACT: the image with role layout is the selected Template MASTER. It is not optional inspiration: reproduce its recognizable composition and design system. The image with role identity is the actual product source. The image with role logo is the ONLY source of the Store logo. The image with role general is installed/use context ONLY when it was provided.\\n"
            . "- COMPOSITION MUST MATCH THE TEMPLATE: preserve the same background concept, major panels, headline zone, logo zone, product zone, framing, border treatment, decorative motifs, lighting language, visual density, and element positions. Do not simplify a rich template into a plain poster, minimal layout, or empty background.\\n"
            . "- PRESERVE TEMPLATE ELEMENTS: when shown in the Template master or explicitly requested by the Template prompt, retain the shop-brand header, top badges, COD/shipping badges, benefit/USP row, icons, technical callouts, accent stripes, product platform, footer slogan and decorative details. Keep their placement and visual hierarchy consistent. Replace only product-specific content. Do not invent factual claims or shipping promises that are absent from the Template.\\n"
            . "- TEMPLATE EXAMPLE TEXT: copy fixed shop/brand labels and reusable design labels only when they are clearly part of the template system. Replace the old product headline with the exact user title; do not copy the example product or its product-specific claims.\\n"
            . "- ABSOLUTE LOGO SOURCE FIREWALL: the dedicated official Store logo reference is the ONLY allowed source for the Store logo. Never extract, trace, redraw, approximate, typeset, reconstruct or substitute a logo from product packaging, product photo, installed photo, or Template image. Preserve manufacturer branding only as part of the physical product/packaging itself.\\n"
            . ($hasStoreLogo
                ? "- REQUIRED LOGO: visibly use the supplied Store logo image as the actual logo artwork. Do not replace it with text saying the shop name. Keep the same logo placement, relative scale and treatment as the Template for every product and both modes.\\n"
                : "- REQUIRED LOGO INPUT IS MISSING: do not invent a logo; generation must stop until the official Store logo is attached.\\n")
            . "- MODE LOCK: if an installed reference is present, use it as a visible, truthful hero/usage view while retaining the Template composition and product packaging where the Template calls for it. If no installed reference is present, do not generate a motorcycle, vehicle, rider, mechanic, workshop or installation scene; keep the same Template's non-installation product presentation.\\n"
            . "- OUTPUT CANVAS: follow the requested canvas size and aspect ratio. Use the available canvas fully with a deliberate, premium commercial composition; do not leave large empty areas or crop off the Store logo/headline/footer.\\n"
            . "- Do not add duplicate logos, alternative wordmarks, fake product specs, invented prices, invented compatibility or unsupported marketing claims. Product identity and the selected Store's logo remain exact. These constraints override conflicting text or branding visible in other references.";

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

        $size = match ($ratio) {
            '1:1' => '1536x1536',
            '4:5' => '1536x1920',
            '3:4' => '1536x2048',
            '9:16' => '1024x1792',
            '16:9' => '2304x1296',
            default => $request->size,
        };

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
