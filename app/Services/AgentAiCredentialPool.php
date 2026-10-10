<?php

namespace App\Services;

use App\Models\AgentAiCredential;
use Illuminate\Support\Facades\DB;

final class AgentAiCredentialPool
{
    private const RATE_LIMIT_COOLDOWN_SECONDS = 300;
    private const BACKEND_ERROR_COOLDOWN_SECONDS = 30;

    /**
     * The lock covers only credential acquisition. It does not serialize
     * the actual Agent invocation, so multiple workers can run concurrently.
     *
     * @return array{id:int,key:string,name:string,source:string}|null
     */
    public function acquire(?int $userId, array $excludeIds = []): ?array
    {
        return DB::transaction(function () use ($userId, $excludeIds) {
            $query = AgentAiCredential::query()
                ->where('is_active', true)
                ->whereNotIn('status', ['disabled', 'invalid', 'exhausted'])
                ->where(function ($q) {
                    $q->whereNull('cooldown_until')
                        ->orWhere('cooldown_until', '<=', now());
                })
                ->orderBy('last_used_at')
                ->orderBy('id')
                ->lockForUpdate();

            if ($userId !== null) {
                $query->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                        ->orWhereNull('user_id');
                });
            }

            if ($excludeIds !== []) {
                $query->whereNotIn('id', array_map('intval', $excludeIds));
            }

            $row = $query->first();

            if (! $row) {
                return null;
            }

            $row->forceFill([
                'last_used_at' => now(),
                'status' => 'active',
                'cooldown_until' => null,
                'request_count' => DB::raw('request_count + 1'),
            ])->save();

            return [
                'id' => $row->id,
                'key' => $row->access_token,
                'name' => $row->name,
                'source' => 'pool',
            ];
        });
    }

    public function totalCount(?int $userId): int
    {
        return $this->scoped($userId)->count();
    }

    public function activeCount(?int $userId): int
    {
        return $this->scoped($userId)
            ->where('is_active', true)
            ->whereNotIn('status', ['disabled', 'invalid', 'exhausted'])
            ->where(function ($q) {
                $q->whereNull('cooldown_until')
                    ->orWhere('cooldown_until', '<=', now());
            })
            ->count();
    }

    public function hasAvailableCredential(?int $userId): bool
    {
        return $this->activeCount($userId) > 0;
    }

    public function reportSuccess(?int $id, ?int $userId = null): void
    {
        if (! $id) {
            return;
        }

        $query = AgentAiCredential::query()->whereKey($id);

        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            });
        }

        $query->update([
            'status' => 'active',
            'cooldown_until' => null,
            'last_success_at' => now(),
            'last_error_type' => null,
            'last_error' => null,
            'last_exit_code' => null,
            'success_count' => DB::raw('success_count + 1'),
            'updated_at' => now(),
        ]);
    }

    public function reportFailure(?int $id, ?int $userId, string $reason, ?int $exitCode = null): array
    {
        if (! $id) {
            return [
                'status' => 'failed',
                'retry' => false,
                'seconds' => 0,
                'reason' => $reason,
            ];
        }

        $query = AgentAiCredential::query()->whereKey($id);

        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            });
        }

        $credential = (clone $query)->first();

        if (! $credential) {
            return [
                'status' => 'failed',
                'retry' => false,
                'seconds' => 0,
                'reason' => 'credential_not_found',
            ];
        }

        $classification = $this->classifyFailure($reason, $exitCode);

        // A detected provider rate limit starts this credential's configured
        // cooldown from the detection timestamp, not from when the drawer opens.
        if (($classification['reason'] ?? null) === 'rate_limited') {
            $minutes = max(1, min(10080, (int) ($credential->cooldown_duration_minutes ?: 1440)));
            $classification['seconds'] = $minutes * 60;
        }

        $updates = [
            'failure_count' => DB::raw('failure_count + 1'),
            'last_failure_at' => now(),
            'last_error_type' => $classification['reason'],
            'last_error' => mb_substr($reason, 0, 2000),
            'last_exit_code' => $exitCode,
            'updated_at' => now(),
        ];

        if ($classification['status'] === 'cooldown') {
            $updates['status'] = 'cooldown';
            $updates['cooldown_until'] = now()->addSeconds($classification['seconds']);
        } elseif ($classification['status'] === 'invalid') {
            $updates['status'] = 'invalid';
            $updates['cooldown_until'] = null;
        } elseif ($classification['status'] === 'exhausted') {
            $updates['status'] = 'exhausted';
            $updates['cooldown_until'] = null;
        } elseif (($classification['reason'] ?? null) === 'provider_access_denied') {
            // Keep a previously validated credential active. Endpoint/model
            // permission failures are diagnostic, not proof of invalid auth.
            $updates['status'] = $credential->is_active ? 'active' : 'pending_validation';
            $updates['cooldown_until'] = null;
        } else {
            $updates['status'] = 'error';
            $updates['cooldown_until'] = null;
        }

        $query->update($updates);

        return $classification;
    }

    public function classifyFailure(string $reason, ?int $exitCode = null): array
    {
        $body = strtolower($reason);

        if (
            str_contains($body, '401')
            || str_contains($body, 'unauthorized')
            || str_contains($body, 'invalid token')
        ) {
            return [
                'status' => 'invalid',
                'retry' => true,
                'seconds' => 0,
                'reason' => 'invalid_credential',
            ];
        }

        // HTTP 403 means the request was forbidden, not necessarily that the
        // OAuth token is invalid. Keep the credential out of the invalid bucket
        // so account-level/endpoint permissions do not poison the token pool.
        if (str_contains($body, '403') || str_contains($body, 'forbidden')) {
            return [
                'status' => 'failed',
                'retry' => false,
                'seconds' => 0,
                'reason' => 'provider_access_denied',
            ];
        }

        if (
            str_contains($body, '429')
            || str_contains($body, 'rate limit')
            || str_contains($body, 'too many')
        ) {
            return [
                'status' => 'cooldown',
                'retry' => true,
                'seconds' => self::RATE_LIMIT_COOLDOWN_SECONDS,
                'reason' => 'rate_limited',
            ];
        }

        if ($exitCode !== null && $exitCode >= 70) {
            return [
                'status' => 'cooldown',
                'retry' => true,
                'seconds' => self::BACKEND_ERROR_COOLDOWN_SECONDS,
                'reason' => 'agent_backend_error',
            ];
        }

        return [
            'status' => 'failed',
            'retry' => false,
            'seconds' => 0,
            'reason' => 'agent_request_failed',
        ];
    }

    private function scoped(?int $userId)
    {
        $query = AgentAiCredential::query();

        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereNull('user_id');
            });
        }

        return $query;
    }
}
