<?php

namespace App\Services;

use App\Models\OpenAiSetting;
use App\Models\VercelGatewayApiKey;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;

class VercelGatewayKeyPool
{
    private const RATE_LIMIT_COOLDOWN_SECONDS = 60;
    private const SERVER_ERROR_COOLDOWN_SECONDS = 30;

    /**
     * Acquire the next usable key for a specific application user.
     *
     * The returned key is immediately marked as used so concurrent queue
     * workers naturally move through the pool instead of always choosing the
     * first row.
     *
     * @return array{id:int|null,key:string,name:string,source:string}|null
     */
    public function acquire(?int $userId, ?string $preferredKey = null, array $excludeIds = []): ?array
    {
        if (filled($preferredKey)) {
            return [
                'id' => null,
                'key' => trim($preferredKey),
                'name' => 'Manual / Test',
                'source' => 'manual',
            ];
        }

        $query = VercelGatewayApiKey::query()
            ->where('is_active', true)
            ->whereNotIn('status', ['disabled', 'invalid', 'exhausted'])
            ->where(function ($q) {
                $q->whereNull('cooldown_until')
                    ->orWhere('cooldown_until', '<=', now());
            })
            ->orderBy('last_used_at')
            ->orderBy('priority')
            ->orderBy('id');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($excludeIds !== []) {
            $query->whereNotIn('id', array_map('intval', $excludeIds));
        }

        $row = $query->first();

        if ($row) {
            $row->forceFill([
                'last_used_at' => now(),
                'status' => 'active',
                'cooldown_until' => null,
                'request_count' => DB::raw('request_count + 1'),
            ])->save();

            return [
                'id' => $row->id,
                'key' => $row->api_key,
                'name' => $row->name,
                'source' => 'pool',
            ];
        }

        // Backward-compatible fallback for installations that still have the
        // legacy OpenAiSetting key or AI_GATEWAY_API_KEY in .env.
        $legacyKey = null;
        if ($userId !== null) {
            $legacyKey = OpenAiSetting::query()->value('api_key');
        }

        if (filled($legacyKey)) {
            return [
                'id' => null,
                'key' => trim($legacyKey),
                'name' => 'Legacy Primary',
                'source' => 'legacy',
            ];
        }

        $envKey = trim((string) env('AI_GATEWAY_API_KEY', ''));
        if ($envKey !== '') {
            return [
                'id' => null,
                'key' => $envKey,
                'name' => 'Environment fallback',
                'source' => 'env',
            ];
        }

        return null;
    }

    public function totalCount(?int $userId): int
    {
        $query = VercelGatewayApiKey::query();
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }
        return $query->count();
    }

    public function activeCount(?int $userId): int
    {
        $query = VercelGatewayApiKey::query()
            ->where('is_active', true)
            ->whereNotIn('status', ['disabled', 'invalid', 'exhausted'])
            ->where(function ($q) {
                $q->whereNull('cooldown_until')
                    ->orWhere('cooldown_until', '<=', now());
            });

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->count();
    }

    public function hasAvailableKey(?int $userId): bool
    {
        return $this->activeCount($userId) > 0
            || filled(OpenAiSetting::query()->value('api_key'))
            || trim((string) env('AI_GATEWAY_API_KEY', '')) !== '';
    }

    public function reportSuccess(?int $id, ?int $userId = null): void
    {
        if (! $id) {
            return;
        }

        $query = VercelGatewayApiKey::query()->whereKey($id);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $query->update([
            'status' => 'active',
            'cooldown_until' => null,
            'last_success_at' => now(),
            'last_error_type' => null,
            'last_error' => null,
            'last_http_status' => null,
            'success_count' => DB::raw('success_count + 1'),
            'updated_at' => now(),
        ]);
    }

    /**
     * Classify an image-generation response and decide whether another key
     * should be attempted.
     */
    public function reportFailure(?int $id, ?int $userId, Response $response, string $detail): array
    {
        $classification = $this->classifyResponse($response, $detail);

        if ($id) {
            $query = VercelGatewayApiKey::query()->whereKey($id);
            if ($userId !== null) {
                $query->where('user_id', $userId);
            }

            $updates = [
                'failure_count' => DB::raw('failure_count + 1'),
                'last_failure_at' => now(),
                'last_error_type' => $classification['reason'],
                'last_error' => mb_substr($detail, 0, 2000),
                'last_http_status' => $response->status(),
                'updated_at' => now(),
            ];

            if ($classification['status'] === 'exhausted') {
                $updates['status'] = 'exhausted';
                $updates['cooldown_until'] = null;
            } elseif ($classification['status'] === 'invalid') {
                $updates['status'] = 'invalid';
                $updates['cooldown_until'] = null;
            } elseif ($classification['status'] === 'cooldown') {
                $updates['status'] = 'cooldown';
                $updates['cooldown_until'] = now()->addSeconds($classification['seconds']);
            } else {
                $updates['status'] = 'error';
                $updates['cooldown_until'] = null;
            }

            $query->update($updates);
        }

        return $classification;
    }

    public function classifyResponse(Response $response, ?string $detail = null): array
    {
        $status = $response->status();
        $body = strtolower(trim(($detail ?: '') . ' ' . $response->body()));

        $creditWords = [
            'no credits remaining',
            'credits remaining',
            'credit exhausted',
            'insufficient credits',
            'insufficient balance',
            'out of credits',
            'quota exceeded',
            'quota has been exceeded',
            'credit_exhausted',
        ];

        $budgetWords = [
            'budget exceeded',
            'budget_exceeded',
            'api key budget',
            'budget limit',
        ];

        if ($status === 401 || $status === 403) {
            return [
                'status' => 'invalid',
                'retry' => true,
                'seconds' => 0,
                'reason' => 'invalid_key',
            ];
        }

        if (collect($creditWords)->contains(fn ($word) => str_contains($body, $word))) {
            return [
                'status' => 'exhausted',
                'retry' => true,
                'seconds' => 0,
                'reason' => 'credit_exhausted',
            ];
        }

        if (collect($budgetWords)->contains(fn ($word) => str_contains($body, $word))) {
            return [
                'status' => 'exhausted',
                'retry' => true,
                'seconds' => 0,
                'reason' => 'budget_exceeded',
            ];
        }

        if ($status === 429) {
            return [
                'status' => 'cooldown',
                'retry' => true,
                'seconds' => self::RATE_LIMIT_COOLDOWN_SECONDS,
                'reason' => 'rate_limited',
            ];
        }

        if ($status >= 500) {
            return [
                'status' => 'cooldown',
                'retry' => true,
                'seconds' => self::SERVER_ERROR_COOLDOWN_SECONDS,
                'reason' => 'gateway_server_error',
            ];
        }

        return [
            'status' => 'failed',
            'retry' => false,
            'seconds' => 0,
            'reason' => 'request_error',
        ];
    }
}
