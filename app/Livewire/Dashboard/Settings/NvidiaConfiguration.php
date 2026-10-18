<?php

namespace App\Livewire\Dashboard\Settings;

use App\Models\ActivityLog;
use App\Models\NvidiaApiSetting;
use App\Services\ActivityLogService;
use App\Services\NvidiaImageRequestException;
use App\Services\NvidiaImageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class NvidiaConfiguration extends Component
{
    use WithFileUploads;

    public string $apiKeyInput = '';
    public string $model = 'black-forest-labs/flux.3-image-klein';
    public string $prompt = 'Create a premium Shopee product advertisement for a motorcycle spare part. Preserve the real product shape, proportions, material, color, and details. Use a charcoal-black background, deep red accents, metallic highlights, dramatic automotive studio lighting, and a clean 4:5 commercial layout.';
    public $referenceImage = null;
    public bool $configured = false;
    public string $maskedKey = '';
    public string $connectionStatus = 'not_tested';
    public ?string $lastTestedAt = null;
    public bool $isTesting = false;
    public ?string $testImageUrl = null;
    public ?string $testImagePath = null;
    public ?string $testMessage = null;
    public string $testStatus = 'idle';
    public ?int $testHttpStatus = null;
    public ?int $testDurationMs = null;

    public function mount(): void
    {
        $setting = $this->setting();

        if ($setting) {
            $this->configured = filled($setting->api_key);
            $this->maskedKey = $this->configured ? $this->maskKey($setting->api_key) : '';
            $this->model = $setting->default_model ?: $this->model;
            $this->connectionStatus = $setting->last_test_status ?: 'not_tested';
            $this->lastTestedAt = $setting->last_tested_at?->diffForHumans();
        }
    }

    public function saveConfiguration(): void
    {
        $this->validate([
            'apiKeyInput' => [$this->configured ? 'nullable' : 'required', 'string', 'min:10', 'max:500'],
            'model' => ['required', 'in:black-forest-labs/flux.3-image-klein,black-forest-labs/flux.2-klein-4b,black-forest-labs/flux.1-kontext-dev'],
        ]);

        $setting = $this->setting() ?? new NvidiaApiSetting(['user_id' => Auth::id()]);
        if (filled(trim($this->apiKeyInput))) {
            $setting->api_key = trim($this->apiKeyInput);
        }
        $setting->user_id = Auth::id();
        $setting->default_model = $this->model;
        $setting->is_enabled = true;
        $setting->save();

        $this->apiKeyInput = '';
        $this->configured = filled($setting->api_key);
        $this->maskedKey = $this->configured ? $this->maskKey($setting->api_key) : '';
        $this->writeLog(
            'nvidia_api_key_saved',
            'NVIDIA API configuration saved',
            'NVIDIA API key disimpan secara terenkripsi. Nilai credential tidak dicatat ke log.',
            'success',
            ['provider' => 'nvidia', 'model' => $this->model],
        );
        $this->dispatch('toast', type: 'success', title: 'NVIDIA configuration saved', message: 'API key tersimpan secara terenkripsi.');
    }

    public function removeApiKey(): void
    {
        $setting = $this->setting();
        if ($setting) {
            $setting->delete();
        }

        $this->configured = false;
        $this->maskedKey = '';
        $this->apiKeyInput = '';
        $this->connectionStatus = 'not_configured';
        $this->lastTestedAt = null;
        $this->writeLog('nvidia_api_key_removed', 'NVIDIA API key removed', 'Credential NVIDIA dihapus dari konfigurasi.', 'warning', ['provider' => 'nvidia']);
        $this->dispatch('toast', type: 'success', title: 'API key removed', message: 'Credential NVIDIA berhasil dihapus.');
    }

    public function testConnection(): void
    {
        $setting = $this->setting();
        if (! $setting || ! filled($setting->api_key)) {
            $this->dispatch('toast', type: 'error', title: 'API key belum tersedia', message: 'Simpan NVIDIA API key terlebih dahulu.');
            return;
        }

        $started = microtime(true);
        $this->testStatus = 'processing';
        $this->testMessage = 'Menjalankan tes generasi gambar…';
        try {
            // A small real generation is the most reliable hosted-endpoint check.
            $result = app(NvidiaImageService::class)->generate(
                $setting->api_key,
                $this->model,
                'A simple premium studio product photograph of a small metallic motorcycle spare part on a clean dark charcoal background.',
            );
            $decoded = base64_decode($result['image'], true);
            $this->testHttpStatus = $result['http_status'];
            $this->testDurationMs = (int) round((microtime(true) - $started) * 1000);
            $setting->forceFill(['last_tested_at' => now(), 'last_test_status' => 'success'])->save();
            $this->connectionStatus = 'success';
            $this->testStatus = 'success';
            $this->lastTestedAt = now()->diffForHumans();
            $this->writeLog('nvidia_connection_test', 'NVIDIA generation test succeeded', 'Tes koneksi berhasil melalui image generation.', 'success', [
                'provider' => 'nvidia', 'model' => $this->model, 'http_status' => $this->testHttpStatus,
                'duration_ms' => $this->testDurationMs, 'test_type' => 'connection',
            ], $this->testDurationMs, $this->testHttpStatus);
            $this->testMessage = 'NVIDIA berhasil memproses request dan mengembalikan gambar.';
            $this->dispatch('toast', type: 'success', title: 'NVIDIA connection healthy', message: 'Endpoint berhasil menghasilkan gambar.');
        } catch (Throwable $e) {
            $this->testHttpStatus = $e instanceof NvidiaImageRequestException ? $e->httpStatus : null;
            $this->testDurationMs = (int) round((microtime(true) - $started) * 1000);
            $setting->forceFill(['last_tested_at' => now(), 'last_test_status' => 'error'])->save();
            $this->connectionStatus = 'error';
            $this->testStatus = 'error';
            $this->lastTestedAt = now()->diffForHumans();
            $message = $this->safeError($e);
            $this->writeLog('nvidia_connection_test', 'NVIDIA generation test failed', $message, 'error', [
                'provider' => 'nvidia', 'model' => $this->model, 'http_status' => $this->testHttpStatus,
                'duration_ms' => $this->testDurationMs, 'exception' => get_class($e), 'test_type' => 'connection',
            ], $this->testDurationMs, $this->testHttpStatus);
            $this->testMessage = $message;
            $this->dispatch('toast', type: 'error', title: 'NVIDIA test failed', message: $message);
        }
    }

    public function generateTestImage(): void
    {
        $this->validate([
            'prompt' => ['required', 'string', 'min:8', 'max:10000'],
            'model' => ['required', 'in:black-forest-labs/flux.3-image-klein,black-forest-labs/flux.2-klein-4b,black-forest-labs/flux.1-kontext-dev'],
            'referenceImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $setting = $this->setting();
        if (! $setting || ! filled($setting->api_key)) {
            $this->testStatus = 'error';
            $this->testMessage = 'Simpan NVIDIA API key terlebih dahulu.';
            return;
        }

        $this->isTesting = true;
        $this->testStatus = 'processing';
        $this->testMessage = 'Mengirim prompt ke NVIDIA…';
        $started = microtime(true);
        $httpStatus = null;

        try {
            $imageDataUri = null;
            if ($this->referenceImage) {
                $mime = $this->referenceImage->getMimeType() ?: 'image/jpeg';
                $imageDataUri = 'data:' . $mime . ';base64,' . base64_encode($this->referenceImage->get());
            }

            $result = app(NvidiaImageService::class)->generate(
                $setting->api_key,
                $this->model,
                $this->prompt,
                $imageDataUri,
            );
            $httpStatus = $result['http_status'];
            $bytes = base64_decode($result['image'], true);
            if ($bytes === false || $bytes === '') {
                throw new \RuntimeException('Respons NVIDIA berisi gambar yang tidak valid.');
            }

            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'image/png';
            $extension = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                default => 'png',
            };
            $path = 'nvidia-tests/' . Auth::id() . '/' . now()->format('Ymd-His') . '-' . \Illuminate\Support\Str::random(8) . '.' . $extension;
            Storage::disk('public')->put($path, $bytes);
            $this->testImagePath = $path;
            $this->testImageUrl = Storage::disk('public')->url($path);
            $this->testStatus = 'success';
            $this->testMessage = 'Gambar berhasil dibuat. Periksa hasil dan log request di bawah konfigurasi NVIDIA.';
            $this->testHttpStatus = $httpStatus;
            $this->testDurationMs = (int) round((microtime(true) - $started) * 1000);
            $setting->forceFill(['last_tested_at' => now(), 'last_test_status' => 'success', 'default_model' => $this->model])->save();

            $this->writeLog('nvidia_test_image_generated', 'NVIDIA test image generated', 'Web Playground menghasilkan gambar NVIDIA.', 'success', [
                'provider' => 'nvidia', 'model' => $this->model, 'http_status' => $httpStatus,
                'duration_ms' => $this->testDurationMs, 'has_reference_image' => (bool) $imageDataUri,
                'output_path' => $path, 'prompt_length' => mb_strlen($this->prompt),
            ], $this->testDurationMs, $httpStatus);
        } catch (Throwable $e) {
            $this->testStatus = 'error';
            $this->testHttpStatus = $e instanceof NvidiaImageRequestException ? $e->httpStatus : null;
            $this->testDurationMs = (int) round((microtime(true) - $started) * 1000);
            $this->testMessage = $this->safeError($e);
            $this->writeLog('nvidia_test_image_failed', 'NVIDIA test image generation failed', $this->testMessage, 'error', [
                'provider' => 'nvidia', 'model' => $this->model, 'http_status' => $this->testHttpStatus,
                'duration_ms' => $this->testDurationMs, 'exception' => get_class($e),
                'has_reference_image' => (bool) $this->referenceImage, 'prompt_length' => mb_strlen($this->prompt),
            ], $this->testDurationMs, $this->testHttpStatus);
        } finally {
            $this->isTesting = false;
            $this->referenceImage = null;
        }
    }

    public function recentNvidiaLogs(): array
    {
        return ActivityLog::query()
            ->where('user_id', Auth::id())
            ->whereIn('action', [
                'nvidia_api_key_saved', 'nvidia_api_key_removed', 'nvidia_connection_test',
                'nvidia_test_image_generated', 'nvidia_test_image_failed',
            ])
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'title' => $log->title,
                'description' => $log->description,
                'status' => $log->status,
                'created_at' => $log->created_at?->format('d M Y, H:i:s'),
                'metadata' => is_array($log->metadata) ? $log->metadata : [],
            ])->all();
    }

    private function setting(): ?NvidiaApiSetting
    {
        return NvidiaApiSetting::query()->where('user_id', Auth::id())->first();
    }

    private function maskKey(string $key): string
    {
        return strlen($key) < 9 ? str_repeat('•', strlen($key)) : substr($key, 0, 4) . str_repeat('•', max(4, strlen($key) - 8)) . substr($key, -4);
    }

    private function safeError(Throwable $e): string
    {
        $message = trim($e->getMessage());
        if (str_contains(strtolower($message), 'could not resolve') || str_contains(strtolower($message), 'getaddrinfo')) {
            return 'Server tidak dapat menjangkau host NVIDIA. Periksa koneksi internet dan DNS VPS.';
        }
        if (str_contains(strtolower($message), 'timed out') || str_contains(strtolower($message), 'timeout')) {
            return 'Request NVIDIA timeout. Model mungkin sedang sibuk; coba lagi beberapa saat.';
        }
        return mb_substr($message !== '' ? $message : 'Request NVIDIA gagal.', 0, 1200);
    }

    private function writeLog(
        string $action,
        string $title,
        string $description,
        string $status,
        array $metadata = [],
        ?int $durationMs = null,
        ?int $httpStatus = null,
    ): void {
        app(ActivityLogService::class)->log(
            action: $action,
            category: 'api',
            status: $status,
            title: $title,
            description: $description,
            metadata: $metadata,
            durationMs: $durationMs,
            httpStatus: $httpStatus,
        );
    }

    public function render()
    {
        return view('livewire.dashboard.settings.nvidia-configuration', [
            'models' => app(NvidiaImageService::class)->models(),
            'logs' => $this->recentNvidiaLogs(),
        ]);
    }
}
