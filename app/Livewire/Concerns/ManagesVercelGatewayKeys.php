<?php

namespace App\Livewire\Concerns;

use App\Models\VercelGatewayApiKey;
use App\Models\VercelGatewayApiKeyLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

trait ManagesVercelGatewayKeys
{
    public string $newVercelKeyName = '';
    public string $newVercelApiKey = '';
    public array $vercelGatewayLogs = [];

    public function getVercelGatewayKeysProperty(): array
    {
        return VercelGatewayApiKey::query()
            ->where('user_id', auth()->id())
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->map(fn (VercelGatewayApiKey $key) => [
                'id' => $key->id,
                'name' => $key->name,
                'status' => $this->effectiveVercelKeyStatus($key),
                'masked_key' => $key->masked_key,
                'request_count' => $key->request_count,
                'success_count' => $key->success_count,
                'failure_count' => $key->failure_count,
                'last_error_type' => $key->last_error_type,
                'last_error' => $key->last_error,
                'last_http_status' => $key->last_http_status,
                'last_used_at' => $key->last_used_at?->diffForHumans(),
            ])
            ->all();
    }

    private function effectiveVercelKeyStatus(VercelGatewayApiKey $key): string
    {
        if (! $key->is_active || $key->status === 'disabled') {
            return 'disabled';
        }

        if ($key->cooldown_until?->isFuture()) {
            return 'rate_limited';
        }

        return $key->status ?: 'active';
    }

    public function addVercelGatewayKey(): void
    {
        $this->validate([
            'newVercelKeyName' => ['required', 'string', 'max:120'],
            'newVercelApiKey' => ['required', 'string', 'max:1000'],
        ], [
            'newVercelKeyName.required' => 'Nama key wajib diisi.',
            'newVercelApiKey.required' => 'Vercel API key wajib diisi.',
        ]);

        $name = trim($this->newVercelKeyName);
        $apiKey = trim($this->newVercelApiKey);

        if ($apiKey === '') {
            throw ValidationException::withMessages([
                'newVercelApiKey' => 'Vercel API key wajib diisi.',
            ]);
        }

        $keys = VercelGatewayApiKey::query()
            ->where('user_id', auth()->id())
            ->get();

        $exists = $keys->contains(
            fn (VercelGatewayApiKey $item) => hash_equals((string) $item->api_key, $apiKey)
        );

        if ($exists) {
            throw ValidationException::withMessages([
                'newVercelApiKey' => 'API key tersebut sudah ada di pool.',
            ]);
        }

        $key = VercelGatewayApiKey::create([
            'user_id' => auth()->id(),
            'name' => $name,
            'api_key' => $apiKey,
            'status' => 'active',
            'is_active' => true,
            'priority' => ((int) ($keys->max('priority') ?? 0)) + 1,
        ]);

        $this->newVercelKeyName = '';
        $this->newVercelApiKey = '';

        $this->dispatch('vercel-key-added', id: $key->id);
        $this->dispatch('toast', type: 'success', title: 'API key ditambahkan', message: "{$name} berhasil masuk ke Vercel API Key Pool.");
        $this->loadVercelGatewayLogs();
    }

    public function removeVercelGatewayKey(int $id): void
    {
        $key = $this->ownedVercelGatewayKey($id);
        if (! $key) {
            return;
        }

        $keyName = $key->name;
        $key->delete();

        $this->dispatch('vercel-key-removed', id: $id);
        $this->dispatch('toast', type: 'success', title: 'API key dihapus', message: "{$keyName} sudah dihapus dari pool.");
        $this->loadVercelGatewayLogs();
    }

    public function toggleVercelGatewayKey(int $id): void
    {
        $key = $this->ownedVercelGatewayKey($id);
        if (! $key) {
            return;
        }

        $key->is_active = ! $key->is_active;
        $key->status = $key->is_active ? 'active' : 'disabled';
        $key->cooldown_until = null;
        $key->save();

        $this->dispatch('toast',
            type: 'success',
            title: $key->is_active ? 'API key diaktifkan' : 'API key dinonaktifkan',
            message: $key->name . ($key->is_active ? ' kembali tersedia untuk generator.' : ' tidak akan dipakai generator.')
        );
    }

    public function resetVercelGatewayKey(int $id): void
    {
        $key = $this->ownedVercelGatewayKey($id);
        if (! $key) {
            return;
        }

        $key->update([
            'status' => $key->is_active ? 'active' : 'disabled',
            'cooldown_until' => null,
            'last_error_type' => null,
            'last_error' => null,
            'last_http_status' => null,
        ]);

        $this->dispatch('toast', type: 'success', title: 'Status di-reset', message: $key->name . ' siap digunakan kembali.');
    }

    public function testVercelGatewayKey(int $id): void
    {
        $key = $this->ownedVercelGatewayKey($id);
        if (! $key) {
            return;
        }

        $this->runVercelGatewayKeyTest($key);
    }

    public function testAllVercelGatewayKeys(): void
    {
        $keys = VercelGatewayApiKey::query()
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        if ($keys->isEmpty()) {
            $this->dispatch('toast', type: 'warning', title: 'Belum ada API key', message: 'Tambahkan minimal satu Vercel API key terlebih dahulu.');
            return;
        }

        $success = 0;
        foreach ($keys as $key) {
            $result = $this->runVercelGatewayKeyTest($key, false);
            if (($result['status'] ?? 'error') === 'success') {
                $success++;
            }
        }

        $this->loadVercelGatewayLogs();

        $type = $success === $keys->count()
            ? 'success'
            : ($success > 0 ? 'warning' : 'error');

        $this->dispatch(
            'toast',
            type: $type,
            title: 'Gateway health check selesai',
            message: "{$success}/{$keys->count()} API key berhasil terhubung."
        );
    }

    private function runVercelGatewayKeyTest(VercelGatewayApiKey $key, bool $notify = true): array
    {
        $started = microtime(true);

        $key->increment('request_count');
        $key->update(['last_used_at' => now()]);

        try {
            /*
             * IMPORTANT:
             * This test must talk to Vercel AI Gateway directly.
             * Never route a Vercel `vck_...` key through api.openai.com.
             */
            $response = Http::acceptJson()
                ->withToken($key->api_key)
                ->connectTimeout(8)
                ->timeout(20)
                ->get('https://ai-gateway.vercel.sh/v1/models');

            $duration = (int) round((microtime(true) - $started) * 1000);
            $httpStatus = $response->status();

            $message = $response->json('error.message');
            $message = is_string($message) && trim($message) !== ''
                ? trim($message)
                : '';

            $detail = trim((string) $response->body());
            if (mb_strlen($detail) > 2000) {
                $detail = mb_substr($detail, 0, 2000);
            }

            if ($response->successful()) {
                $models = $response->json('data', []);
                $modelCount = is_array($models) ? count($models) : 0;

                $status = 'success';
                $message = 'Vercel AI Gateway API key terhubung.';
                $detail = "GET /v1/models berhasil. {$modelCount} model tersedia.";
                $errorType = null;

                $key->update([
                    'status' => 'active',
                    'success_count' => $key->success_count + 1,
                    'last_success_at' => now(),
                    'last_error_type' => null,
                    'last_error' => null,
                    'last_http_status' => $httpStatus,
                    'cooldown_until' => null,
                ]);
            } else {
                $status = 'error';

                $errorType = $this->classifyVercelGatewayError(
                    ($message ?: 'Vercel AI Gateway connection test failed.') . ' ' . $detail
                );

                $friendlyMessage = match ($errorType) {
                    'invalid_key' => 'Vercel AI Gateway API key tidak valid atau tidak memiliki akses.',
                    'credit_exhausted' => 'Vercel AI Gateway credits/quota sudah habis.',
                    'budget_exceeded' => 'Budget API key Vercel sudah mencapai batas.',
                    'rate_limited' => 'Vercel AI Gateway sedang rate limit. Coba lagi setelah cooldown.',
                    default => 'Vercel AI Gateway connection test gagal.',
                };

                $message = $friendlyMessage;

                $key->update([
                    'status' => in_array($errorType, ['credit_exhausted', 'budget_exceeded'], true)
                        ? 'exhausted'
                        : ($errorType === 'invalid_key'
                            ? 'invalid'
                            : ($errorType === 'rate_limited' ? 'rate_limited' : 'error')),
                    'failure_count' => $key->failure_count + 1,
                    'last_failure_at' => now(),
                    'last_error_type' => $errorType,
                    'last_error' => mb_substr(
                        $message . ($detail !== '' ? ' ' . $detail : ''),
                        0,
                        2000
                    ),
                    'last_http_status' => $httpStatus,
                    'cooldown_until' => $errorType === 'rate_limited'
                        ? now()->addMinute()
                        : null,
                ]);
            }

            VercelGatewayApiKeyLog::create([
                'vercel_gateway_api_key_id' => $key->id,
                'user_id' => auth()->id(),
                'status' => $status,
                'message' => $message,
                'detail' => $detail,
                'http_status' => $httpStatus,
                'duration_ms' => $duration,
                'error_type' => $errorType,
                'tested_at' => now(),
            ]);

            $this->loadVercelGatewayLogs();

            if ($notify) {
                $this->dispatch(
                    'toast',
                    type: $status === 'success' ? 'success' : 'error',
                    title: $status === 'success' ? 'API key connected' : 'API key test gagal',
                    message: $key->name . ': ' . $message
                );
            }

            return [
                'status' => $status,
                'message' => $message,
                'detail' => $detail,
                'http_status' => $httpStatus,
            ];
        } catch (\Throwable $e) {
            report($e);

            $duration = (int) round((microtime(true) - $started) * 1000);
            $errorType = $this->classifyVercelGatewayError($e->getMessage());

            $key->update([
                'status' => in_array($errorType, ['credit_exhausted', 'budget_exceeded'], true)
                    ? 'exhausted'
                    : ($errorType === 'rate_limited' ? 'rate_limited' : 'error'),
                'failure_count' => $key->failure_count + 1,
                'last_failure_at' => now(),
                'last_error_type' => $errorType,
                'last_error' => mb_substr($e->getMessage(), 0, 2000),
                'last_http_status' => null,
                'cooldown_until' => $errorType === 'rate_limited' ? now()->addMinute() : null,
            ]);

            VercelGatewayApiKeyLog::create([
                'vercel_gateway_api_key_id' => $key->id,
                'user_id' => auth()->id(),
                'status' => 'error',
                'message' => 'Test koneksi Vercel AI Gateway gagal.',
                'detail' => $e->getMessage(),
                'duration_ms' => $duration,
                'error_type' => $errorType,
                'tested_at' => now(),
            ]);

            $this->loadVercelGatewayLogs();

            if ($notify) {
                $this->dispatch(
                    'toast',
                    type: 'error',
                    title: 'API key test gagal',
                    message: $key->name . ': ' . $e->getMessage()
                );
            }

            return [
                'status' => 'error',
                'message' => 'Test koneksi Vercel AI Gateway gagal.',
                'detail' => $e->getMessage(),
            ];
        }
    }

    private function classifyVercelGatewayError(string $text): string
    {
        $text = strtolower($text);

        return match (true) {
            str_contains($text, 'no credits'),
            str_contains($text, 'credits remaining'),
            str_contains($text, 'credit exhausted'),
            str_contains($text, 'credit_exhausted'),
            str_contains($text, 'insufficient credits'),
            str_contains($text, 'quota exceeded') => 'credit_exhausted',
            str_contains($text, 'budget exceeded'),
            str_contains($text, 'budget_exceeded'),
            str_contains($text, 'budget limit') => 'budget_exceeded',
            str_contains($text, 'rate limit'),
            str_contains($text, 'rate_limited'),
            str_contains($text, 'too many requests') => 'rate_limited',
            str_contains($text, 'invalid api key'),
            str_contains($text, 'unauthorized'),
            str_contains($text, 'authentication') => 'invalid_key',
            default => 'connection_error',
        };
    }

    private function ownedVercelGatewayKey(int $id): ?VercelGatewayApiKey
    {
        return VercelGatewayApiKey::query()
            ->where('user_id', auth()->id())
            ->find($id);
    }

    public function loadVercelGatewayLogs(): void
    {
        $this->vercelGatewayLogs = VercelGatewayApiKeyLog::query()
            ->where('user_id', auth()->id())
            ->with('key:id,name,api_key')
            ->latest('tested_at')
            ->limit(50)
            ->get()
            ->map(fn (VercelGatewayApiKeyLog $log) => [
                'id' => $log->id,
                'status' => $log->status ?: 'info',
                'key_name' => $log->key?->name ?? 'Deleted key',
                'masked_key' => $log->key?->masked_key ?? 'vck_••••••••',
                'message' => $log->message,
                'detail' => $log->detail,
                'http_status' => $log->http_status,
                'duration_ms' => $log->duration_ms,
                'error_type' => $log->error_type,
                'tested_at' => $log->tested_at?->format('d M Y, H:i:s'),
                'created_at' => $log->created_at?->format('d M Y, H:i:s'),
            ])
            ->all();
    }

    public function clearVercelGatewayLogs(): void
    {
        VercelGatewayApiKeyLog::query()
            ->where('user_id', auth()->id())
            ->delete();

        $this->vercelGatewayLogs = [];
        $this->dispatch('toast', type: 'success', title: 'Activity dibersihkan', message: 'Riwayat aktivitas API key sudah dihapus.');
    }
}
