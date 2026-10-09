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

        $credential = $this->credentialPool->acquire($request->userId);

        if (! $credential) {
            throw new RuntimeException(
                'Belum ada Agent AI credential aktif. Buka Settings → AI Provider → Agent AI.'
            );
        }

        $output = storage_path(
            'app/agent-ai/' .
            ($request->generationId ?: uniqid('generation-', true)) .
            '-' . uniqid('', true) . '.png'
        );
        $promptFile = null;

        try {
            $directory = dirname($output);

            if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException('Folder Agent AI output tidak dapat dibuat.');
            }

            $model = str_starts_with(strtolower($request->model), 'openai/')
                ? substr($request->model, 7)
                : $request->model;

            $promptFile = $this->promptFile($request);

            $arguments = [
                $this->binary(),
                '-m',
                $this->module(),
                '--prompt-file', $promptFile,
                '--live',
                '--auth-provider', 'env',
                '--image-model', $model,
                '--quality', $request->quality,
                '--size', $request->size,
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
                    'Invocation menggunakan credential %s untuk model %s.',
                    $credential['name'],
                    $model
                ),
                metadata: [
                    'provider' => $this->name(),
                    'model' => $model,
                    'generation_id' => $request->generationId,
                    'agent_credential_id' => $credential['id'],
                    'agent_credential_name' => $credential['name'],
                    'image_count' => 1,
                    'invocation_mode' => 'one_invocation_one_image',
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

                $this->credentialPool->reportFailure(
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
                        'exit_code' => $exitCode,
                    ],
                );

                throw new RuntimeException($detail);
            }

            $binary = file_get_contents($output);

            if ($binary === false || $binary === '') {
                $this->credentialPool->reportFailure(
                    $credential['id'],
                    $request->userId,
                    'Agent AI output image tidak dapat dibaca.',
                    $exitCode,
                );

                throw new RuntimeException('Agent AI output image tidak dapat dibaca.');
            }

            $this->credentialPool->reportSuccess($credential['id'], $request->userId);

            $this->activity->success(
                action: 'ai_provider_request',
                category: 'api',
                title: 'Agent AI request berhasil.',
                description: 'Agent backend mengembalikan satu image yang tervalidasi.',
                metadata: [
                    'provider' => $this->name(),
                    'model' => $model,
                    'generation_id' => $request->generationId,
                    'agent_credential_id' => $credential['id'],
                    'agent_credential_name' => $credential['name'],
                    'image_count' => 1,
                    'invocation_mode' => 'one_invocation_one_image',
                ],
            );

            return new ImageGenerationResult(
                images: [[
                    'b64_json' => base64_encode($binary),
                    'url' => null,
                    'provider' => $this->name(),
                    'model' => $model,
                ]],
                provider: $this->name(),
                model: $model,
                metadata: [
                    'agent_credential_id' => $credential['id'],
                    'agent_credential_name' => $credential['name'],
                    'agent_credential_source' => $credential['source'],
                    'http_status' => 200,
                ],
            );
        } finally {
            if ($promptFile && is_file($promptFile)) {
                @unlink($promptFile);
            }

            if (is_file($output)) {
                @unlink($output);
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

        $prompt .= "\n\nPROVIDER-LEVEL LOCKED BRANDING AND TEMPLATE POLICY — HIGHEST PRIORITY:\n"
            . "- The selected Template's prompt and visual reference define one fixed reusable design system. Keep the same composition, layout zones, visual hierarchy, typography placement, color palette, background treatment, graphic motifs, badges/callouts, spacing and overall art direction for every product generated with this Template.\n"
            . "- Product category, product packaging, product color, and whether an installed-motorcycle reference is present MUST NOT redesign or randomize the Template. Adapt only the product-specific content and the factual installed view when one is supplied.\n"
            . "- Do not copy the example product or store logo embedded in the Template reference. The Template image is a layout/style authority only; the selected Store's own logo reference is the sole authority for store branding.\n"
            . ($hasTemplate
                ? "- A Template/layout reference is attached. Preserve its design system consistently; do not create a new layout for another product.\n"
                : "- No Template image is attached. Follow the selected Template prompt as the fixed design system and do not invent a different style between products.\n")
            . ($hasStoreLogo
                ? "- OFFICIAL STORE LOGO IS ATTACHED AS A DEDICATED REFERENCE. It is mandatory and must be used as the store logo in the final image. Match the exact supplied artwork, wordmark, lettering, icon, colors, proportions and spacing. Never redraw, retype, approximate, stylize, replace, merge or hallucinate the logo. Never use a logo from the Template, product packaging or installed photo as a substitute.\n"
                . "- Place the official Store logo in a clear, intentional branding position consistent with the selected Template prompt and the same Template's prior design logic. Its position may be chosen to fit the composition, but the branding treatment must remain consistent across products and both generation modes.\n"
                : "- No readable Store logo reference was supplied. Do not fabricate, guess, or substitute a store logo.\n")
            . ($hasInstalled
                ? "- An installed/in-use reference is present. Use it only for factual fitment and usage context; it must not override the Template design system or Store logo.\n"
                : "- No installed/in-use reference is present. Do not invent an installation scene; the absence of that photo must not change the Template design system or Store branding rules.\n")
            . "- These rules are mandatory for every provider invocation and override conflicting creative suggestions. Preserve product identity while keeping Template design and Store branding locked.";

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
