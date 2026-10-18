<?php

namespace App\Services;

use App\Models\NvidiaApiKey;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;

final class NvidiaApiKeyPool
{
    public function acquire(?int $userId, array $excludeIds = []): ?array
    {
        $row = DB::transaction(function () use ($userId, $excludeIds) {
            $query = NvidiaApiKey::query()
                ->where('is_active', true)
                ->whereNotIn('status', ['disabled', 'invalid', 'exhausted'])
                ->where(fn ($q) => $q->whereNull('cooldown_until')->orWhere('cooldown_until', '<=', now()))
                ->orderBy('last_used_at')
                ->orderBy('priority')
                ->orderBy('id')
                ->lockForUpdate();

            if ($userId !== null) {
                $query->where(fn ($q) => $q->where('user_id', $userId)->orWhereNull('user_id'));
            }
            if ($excludeIds !== []) $query->whereNotIn('id', array_map('intval', $excludeIds));

            $key = $query->first();
            if (! $key) return null;

            $key->forceFill([
                'last_used_at' => now(),
                'status' => 'active',
                'cooldown_until' => null,
                'request_count' => DB::raw('request_count + 1'),
            ])->save();

            return ['id' => $key->id, 'key' => $key->api_key, 'name' => $key->name, 'source' => 'pool'];
        });

        if ($row) return $row;

        // Backward compatibility: the original single NVIDIA key remains usable
        // until administrators move it into the new pool.
        $legacy = \App\Models\AiProviderSetting::query()
            ->where(fn ($q) => $q->where('user_id', $userId)->orWhereNull('user_id'))
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$userId])
            ->first();

        $key = trim((string) ($legacy?->nvidia_api_key ?? ''));
        return $key !== '' ? ['id' => null, 'key' => $key, 'name' => 'Legacy NVIDIA Key', 'source' => 'legacy'] : null;
    }

    public function totalCount(?int $userId): int
    {
        return NvidiaApiKey::query()
            ->when($userId !== null, fn ($q) => $q->where(fn ($w) => $w->where('user_id', $userId)->orWhereNull('user_id')))
            ->count();
    }

    public function activeCount(?int $userId): int
    {
        return NvidiaApiKey::query()
            ->where('is_active', true)
            ->whereNotIn('status', ['disabled', 'invalid', 'exhausted'])
            ->where(fn ($q) => $q->whereNull('cooldown_until')->orWhere('cooldown_until', '<=', now()))
            ->when($userId !== null, fn ($q) => $q->where(fn ($w) => $w->where('user_id', $userId)->orWhereNull('user_id')))
            ->count();
    }

    public function reportSuccess(?int $id, ?int $userId): void
    {
        if (!$id) return;
        NvidiaApiKey::query()->whereKey($id)
            ->when($userId !== null, fn ($q) => $q->where(fn ($w) => $w->where('user_id', $userId)->orWhereNull('user_id')))
            ->update([
                'status' => 'active', 'cooldown_until' => null, 'last_success_at' => now(),
                'last_error_type' => null, 'last_error' => null, 'last_http_status' => null,
                'success_count' => DB::raw('success_count + 1'), 'updated_at' => now(),
            ]);
    }

    public function reportFailure(?int $id, ?int $userId, Response $response, string $detail): array
    {
        $status = $response->status();
        $body = strtolower($detail . ' ' . $response->body());
        $credit = ['no credits remaining', 'credit exhausted', 'insufficient credits', 'insufficient balance', 'out of credits', 'quota exceeded', 'credit_exhausted', 'billing'];
        $invalid = $status === 401 || $status === 403;
        $exhausted = collect($credit)->contains(fn ($word) => str_contains($body, $word));
        $rateLimited = $status === 429;
        $serverError = $status >= 500;

        $classification = $invalid
            ? ['status' => 'invalid', 'retry' => true, 'reason' => 'invalid_key', 'seconds' => 0]
            : ($exhausted
                ? ['status' => 'exhausted', 'retry' => true, 'reason' => 'credit_exhausted', 'seconds' => 0]
                : ($rateLimited
                    ? ['status' => 'cooldown', 'retry' => true, 'reason' => 'rate_limited', 'seconds' => 60]
                    : ($serverError
                        ? ['status' => 'cooldown', 'retry' => true, 'reason' => 'provider_server_error', 'seconds' => 30]
                        : ['status' => 'error', 'retry' => false, 'reason' => 'request_error', 'seconds' => 0]));

        if ($id) {
            $updates = [
                'failure_count' => DB::raw('failure_count + 1'), 'last_failure_at' => now(),
                'last_error_type' => $classification['reason'], 'last_error' => mb_substr($detail, 0, 2000),
                'last_http_status' => $status, 'updated_at' => now(),
                'status' => $classification['status'],
                'cooldown_until' => $classification['seconds'] > 0 ? now()->addSeconds($classification['seconds']) : null,
            ];
            NvidiaApiKey::query()->whereKey($id)
                ->when($userId !== null, fn ($q) => $q->where(fn ($w) => $w->where('user_id', $userId)->orWhereNull('user_id')))
                ->update($updates);
        }

        return $classification;
    }
}
