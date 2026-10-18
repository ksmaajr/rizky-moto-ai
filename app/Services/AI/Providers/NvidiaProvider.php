<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ImageProviderInterface;
use App\Services\AI\DTO\ImageGenerationRequest;
use App\Services\AI\DTO\ImageGenerationResult;
use App\Services\ActivityLogService;
use App\Services\NvidiaApiKeyPool;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class NvidiaProvider implements ImageProviderInterface
{
    private const ENDPOINTS = [
        'black-forest-labs/flux.2-klein-4b' => 'https://ai.api.nvidia.com/v1/genai/black-forest-labs/flux.2-klein-4b',
        'black-forest-labs/flux.3-image-klein' => 'https://ai.api.nvidia.com/v1/genai/black-forest-labs/flux-3-image-klein',
    ];

    public function __construct(
        private readonly ActivityLogService $activity,
        private readonly NvidiaApiKeyPool $apiPool,
    )
    {
    }

    public function name(): string
    {
        return 'nvidia';
    }

    public function supportsModel(string $model): bool
    {
        return array_key_exists(strtolower(trim($model)), self::ENDPOINTS);
    }

    public function generate(ImageGenerationRequest $request): ImageGenerationResult
    {
        $model = strtolower(trim($request->model));
        $endpoint = self::ENDPOINTS[$model] ?? null;
        if (! $endpoint) {
            throw new RuntimeException('Model NVIDIA tidak didukung: ' . $request->model);
        }

        $this->assertStoreLogoReference($request);
        $attemptedIds = [];
        $maxAttempts = max(1, $this->apiPool->totalCount($request->userId));

        $references = [];
        foreach ($request->references as $reference) {
            if (! is_readable($reference->path)) {
                throw new RuntimeException('Reference image tidak dapat dibaca: ' . basename($reference->path));
            }
            $binary = file_get_contents($reference->path);
            if ($binary === false) {
                throw new RuntimeException('Reference image gagal dibaca: ' . basename($reference->path));
            }
            $mime = mime_content_type($reference->path) ?: 'image/png';
            if (! str_starts_with($mime, 'image/')) {
                throw new RuntimeException('File reference bukan gambar yang valid.');
            }
            $references[] = [
                'role' => strtolower(trim((string) $reference->role)),
                'data' => 'data:' . $mime . ';base64,' . base64_encode($binary),
            ];
        }

        // NVIDIA's hosted preview API currently documents a single image input for
        // editing. Build one clearly labeled contact sheet so all canonical roles
        // survive transport without pretending the endpoint accepts multipart arrays.
        $reference = $this->makeReferenceBoard($references);
        try {
            $prompt = $this->lockedPrompt($request);
        } catch (Throwable $e) {
            @unlink($reference['path']);
            throw $e;
        }

        $promptLimit = $model === 'black-forest-labs/flux.3-image-klein' ? 2000 : 10000;
        $promptLength = mb_strlen($prompt);
        if ($promptLength > $promptLimit) {
            @unlink($reference['path']);
            throw new RuntimeException(sprintf(
                'Prompt NVIDIA %s berjumlah %d karakter, sedangkan batas endpoint adalah %d. Prompt Template tidak dipotong otomatis; ringkas instruksi Template atau pilih model dengan batas lebih tinggi.',
                $model,
                $promptLength,
                $promptLimit,
            ));
        }

        $payload = [
            'mode' => 'Image Editing',
            'prompt' => $prompt,
            'image' => $reference['data'],
            'height' => 1024,
            'width' => 1024,
            'seed' => 0,
        ];
        if ($model === 'black-forest-labs/flux.2-klein-4b') {
            $payload['steps'] = 4;
            $payload['cfg_scale'] = 0;
        } else {
            $payload['steps'] = 50;
            $payload['cfg_scale'] = 3.5;
        }

        $started = microtime(true);
        try {
            $response = null;
            $credential = null;
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $credential = $this->apiPool->acquire($request->userId, $attemptedIds);
                if (! $credential) {
                    break;
                }
                if ($credential['id'] !== null) {
                    $attemptedIds[] = $credential['id'];
                }

                $this->activity->processing(
                    action: 'ai_provider_request',
                    category: 'api',
                    title: 'NVIDIA NIM request dimulai.',
                    description: sprintf('Attempt %d menggunakan credential %s untuk model %s.', $attempt, $credential['name'], $model),
                    metadata: [
                        'provider' => $this->name(),
                        'model' => $model,
                        'generation_id' => $request->generationId,
                        'user_id' => $request->userId,
                        'nvidia_key_id' => $credential['id'],
                        'credential_name' => $credential['name'],
                        'reference_count' => count($references),
                        'transport' => 'single_labeled_reference_board',
                        'attempt' => $attempt,
                    ],
                );

                $response = Http::withToken($credential['key'])
                    ->acceptJson()
                    ->asJson()
                    ->connectTimeout(15)
                    ->timeout((int) config('services.nvidia.timeout', 240))
                    ->post($endpoint, $payload);

                if ($response->successful()) {
                    break;
                }

                $message = trim((string) ($response->json('detail') ?? $response->json('error.message') ?? $response->body()));
                $message = mb_substr($message !== '' ? $message : 'NVIDIA NIM request gagal.', 0, 1200);
                $classification = $this->apiPool->reportFailure($credential['id'], $request->userId, $response, $message);

                $this->activity->error(
                    action: 'ai_provider_request',
                    category: 'api',
                    title: 'NVIDIA NIM credential request gagal.',
                    description: $credential['name'] . ': ' . $message,
                    metadata: [
                        'provider' => $this->name(),
                        'model' => $model,
                        'generation_id' => $request->generationId,
                        'nvidia_key_id' => $credential['id'],
                        'credential_name' => $credential['name'],
                        'http_status' => $response->status(),
                        'error_type' => $classification['reason'],
                        'retry' => $classification['retry'],
                        'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                    ],
                    httpStatus: $response->status(),
                );

                if (! $classification['retry'] || $attempt >= $maxAttempts) {
                    throw new RuntimeException('NVIDIA NIM HTTP ' . $response->status() . ': ' . $message);
                }
            }

            if (! $response || ! $response->successful()) {
                throw new RuntimeException('NVIDIA API key pool tidak memiliki credential aktif yang dapat digunakan.');
            }

            $json = $response->json();
            $images = $this->normalizeImages(is_array($json) ? $json : []);
            if ($images === []) {
                throw new RuntimeException('NVIDIA NIM merespons sukses tetapi tidak mengembalikan data gambar yang dikenali.');
            }

            $this->apiPool->reportSuccess($credential['id'] ?? null, $request->userId);

            $this->activity->success(
                action: 'ai_provider_request',
                category: 'api',
                title: 'NVIDIA NIM request berhasil.',
                description: 'NVIDIA NIM mengembalikan ' . count($images) . ' gambar.',
                metadata: [
                    'provider' => $this->name(),
                    'model' => $model,
                    'generation_id' => $request->generationId,
                    'http_status' => $response->status(),
                    'image_count' => count($images),
                    'nvidia_key_id' => $credential['id'] ?? null,
                    'credential_name' => $credential['name'] ?? null,
                    'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                ],
                httpStatus: $response->status(),
            );

            return new ImageGenerationResult(
                images: $images,
                provider: $this->name(),
                model: $model,
                metadata: ['http_status' => $response->status(), 'duration_ms' => (int) round((microtime(true) - $started) * 1000)],
            );
        } catch (Throwable $e) {
            if (! $e instanceof RuntimeException || ! str_starts_with($e->getMessage(), 'NVIDIA NIM HTTP ')) {
                $this->activity->error(
                    action: 'ai_provider_request',
                    category: 'api',
                    title: 'NVIDIA NIM request exception.',
                    description: mb_substr($e->getMessage(), 0, 1000),
                    metadata: ['provider' => $this->name(), 'model' => $model, 'generation_id' => $request->generationId, 'exception' => get_class($e)],
                );
            }
            throw $e;
        } finally {
            if (is_file($reference['path'])) {
                @unlink($reference['path']);
            }
        }
    }

    private function assertStoreLogoReference(ImageGenerationRequest $request): void
    {
        foreach ($request->references as $reference) {
            if (in_array(strtolower(trim((string) $reference->role)), ['store_logo', 'logo', 'branding'], true) && is_readable($reference->path)) {
                return;
            }
        }
        throw new RuntimeException('Generation dibatalkan: logo resmi Store dari Template tidak tersedia.');
    }

    private function lockedPrompt(ImageGenerationRequest $request): string
    {
        $roles = array_map(static fn ($reference) => strtolower(trim((string) $reference->role)), $request->references);
        $hasInstalled = count(array_intersect($roles, ['installed', 'installed_reference'])) > 0;

        // FLUX.3 Image Klein hosted preview has a strict 2,000-character prompt
        // ceiling. The shared generator prompt is intentionally comprehensive,
        // so distill it for this endpoint while preserving template direction,
        // any exact custom title, product identity, and store-logo rules.
        if (strtolower(trim($request->model)) === 'black-forest-labs/flux.3-image-klein') {
            return $this->compactFluxKleinPrompt($request, $hasInstalled);
        }

        return $request->prompt
            . "\n\nNVIDIA IMAGE EDITING POLICY — mandatory:"
            . "\n- Preserve the real product identity, package shape, color, geometry, and visible details from the product reference."
            . "\n- The dedicated store_logo reference is the ONLY authority for the shop logo. Never copy or redraw a logo from product, installed, or template imagery."
            . "\n- Use the provided reference board as labeled guidance: PRODUCT = exact item identity; TEMPLATE = style direction; INSTALLED = real installation context; STORE LOGO = official branding artwork."
            . "\n- " . ($hasInstalled ? 'Use installed-reference guidance for plausible fitment while preserving the product.' : 'Do not invent an installed-on-motorcycle scene because no installed reference was supplied.')
            . "\n- Create a premium, original square marketplace product poster. Keep headline and Store logo legible; do not invent specifications, prices, or compatibility claims.";
    }

    private function compactFluxKleinPrompt(ImageGenerationRequest $request, bool $hasInstalled): string
    {
        // FLUX.3 Klein has a strict 2,000-character request limit. The selected
        // Template prompt is authoritative and must never be truncated.
        $templateDirection = '';
        if (preg_match('/TEMPLATE PROMPT — mandatory visual direction:\\s*(.*?)\\s*(?=\\R+TITLE:)/su', $request->prompt, $matches)) {
            $templateDirection = trim($matches[1]);
        }

        $titleInstruction = '';
        if (preg_match('/(?:^|\\R+)TITLE:\\s*(.*?)\\s*(?=\\R+BRAND CONTEXT:)/su', $request->prompt, $matches)) {
            $titleInstruction = trim($matches[1]);
        }

        if ($templateDirection === '') {
            throw new RuntimeException('Prompt dari Template terpilih tidak ditemukan. Periksa prompt Template sebelum generate NVIDIA.');
        }

        // The template prompt is already nearly the full budget in some stores.
        // Do not append repeated generic policy paragraphs. Reference roles are
        // also explicitly labeled on the image board; add the custom title only
        // when it fits without altering the selected Template prompt.
        $prompt = $templateDirection;
        $titleRule = $titleInstruction !== '' ? "\nTITLE (preserve exactly): " . $titleInstruction : '';
        if ($titleRule !== '' && mb_strlen($prompt . $titleRule) <= 2000) {
            $prompt .= $titleRule;
        }

        if (mb_strlen($prompt) > 2000) {
            throw new RuntimeException(
                'Prompt Template terpilih sendiri melebihi batas 2.000 karakter NVIDIA FLUX.3 Klein ('
                . mb_strlen($prompt) . ' karakter). Ringkas prompt pada Template atau pilih model NVIDIA dengan batas lebih tinggi.'
            );
        }

        return $prompt;
    }

    /**
     * The hosted NVIDIA trial endpoints document one image input. A labeled board
     * prevents silently dropping template/product/logo roles. The board uses GD.
     * @param array<int,array{role:string,data:string}> $references
     * @return array{path:string,data:string}
     */
    private function makeReferenceBoard(array $references): array
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('PHP GD diperlukan untuk menggabungkan referensi gambar NVIDIA.');
        }

        $dir = storage_path('app/nvidia-reference-boards');
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('Folder reference board NVIDIA tidak dapat dibuat.');
        }
        $path = $dir . '/board-' . uniqid('', true) . '.png';
        $canvas = imagecreatetruecolor(1600, 1200);
        $bg = imagecolorallocate($canvas, 20, 23, 28);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $muted = imagecolorallocate($canvas, 185, 193, 202);
        $panel = imagecolorallocate($canvas, 245, 245, 245);
        imagefill($canvas, 0, 0, $bg);
        imagestring($canvas, 5, 24, 18, 'REFERENCE BOARD — USE EACH LABELED ROLE AS AUTHORITY', $white);

        $cells = [[24, 58], [812, 58], [24, 608], [812, 608]];
        foreach ($cells as $i => [$x, $y]) {
            $w = 764; $h = 510;
            imagefilledrectangle($canvas, $x, $y, $x + $w, $y + $h, $panel);
            if (! isset($references[$i])) {
                continue;
            }
            $ref = $references[$i];
            $raw = base64_decode(substr($ref['data'], strpos($ref['data'], ',') + 1), true);
            $source = is_string($raw) ? @imagecreatefromstring($raw) : false;
            if (! $source) {
                continue;
            }
            $label = strtoupper(str_replace('_', ' ', $ref['role']));
            $labelBg = imagecolorallocate($canvas, 12, 15, 19);
            imagefilledrectangle($canvas, $x, $y, $x + $w, $y + 38, $labelBg);
            imagestring($canvas, 5, $x + 12, $y + 11, $label, $white);
            $sw = imagesx($source); $sh = imagesy($source);
            $scale = min(($w - 20) / max(1, $sw), ($h - 52) / max(1, $sh));
            $dw = max(1, (int) ($sw * $scale)); $dh = max(1, (int) ($sh * $scale));
            imagecopyresampled($canvas, $source, $x + (int) (($w - $dw) / 2), $y + 44 + (int) (($h - 52 - $dh) / 2), 0, 0, $dw, $dh, $sw, $sh);
            imagedestroy($source);
        }
        if (! imagepng($canvas, $path, 6)) {
            imagedestroy($canvas);
            throw new RuntimeException('Gagal membuat reference board NVIDIA.');
        }
        imagedestroy($canvas);
        $binary = file_get_contents($path);
        if ($binary === false) {
            @unlink($path);
            throw new RuntimeException('Reference board NVIDIA tidak dapat dibaca.');
        }
        return ['path' => $path, 'data' => 'data:image/png;base64,' . base64_encode($binary)];
    }

    private function normalizeImages(array $response): array
    {
        $items = $response['artifacts'] ?? $response['data'] ?? $response['images'] ?? [];
        if (! is_array($items)) {
            return [];
        }
        $images = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $base64 = $item['base64'] ?? $item['b64_json'] ?? $item['image'] ?? null;
            if (is_string($base64) && $base64 !== '') {
                if (str_starts_with($base64, 'data:image/') && str_contains($base64, ',')) {
                    $base64 = substr($base64, strpos($base64, ',') + 1);
                }
                $images[] = ['b64_json' => $base64, 'url' => null, 'provider' => $this->name()];
            } elseif (is_string($item['url'] ?? null) && $item['url'] !== '') {
                $images[] = ['url' => $item['url'], 'b64_json' => null, 'provider' => $this->name()];
            }
        }
        return $images;
    }
}
