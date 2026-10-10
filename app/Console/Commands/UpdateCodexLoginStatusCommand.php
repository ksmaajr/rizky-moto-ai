<?php

namespace App\\Console\\Commands;

use App\\Models\\User;
use Illuminate\\Console\\Command;
use Illuminate\\Support\\Facades\\Cache;

class UpdateCodexLoginStatusCommand extends Command
{
    protected $signature = 'agent-ai:codex-login-status
                            {--user-id= : Laravel user ID}
                            {--session-id= : Random ID for the current login attempt}
                            {--state= : Login state}
                            {--message= : Human-readable status message}';

    protected $description = 'Update the shared status of a local PowerShell Codex login flow.';

    public function handle(): int
    {
        $userId = filter_var($this->option('user-id'), FILTER_VALIDATE_INT);
        $sessionId = trim((string) $this->option('session-id'));
        $state = trim((string) $this->option('state'));
        $message = mb_substr(trim((string) $this->option('message')), 0, 500);

        if (! $userId || $userId < 1 || ! User::query()->whereKey($userId)->exists()) {
            $this->error('User pemilik login tidak valid.');
            return self::FAILURE;
        }

        if (! preg_match('/^[a-f0-9]{32}$/', $sessionId)) {
            $this->error('Session ID login tidak valid.');
            return self::FAILURE;
        }

        if (! in_array($state, ['starting', 'waiting_for_login', 'importing', 'completed', 'failed'], true)) {
            $this->error('State login tidak valid.');
            return self::FAILURE;
        }

        $key = 'agentkit:codex-login:' . $userId;
        $current = Cache::get($key, []);

        // An older PowerShell window must never overwrite the status of a newer
        // Add Account attempt for the same user.
        if (! is_array($current) || ! hash_equals((string) ($current['session_id'] ?? ''), $sessionId)) {
            $this->warn('Status login lama diabaikan karena sesi sudah berubah.');
            return self::SUCCESS;
        }

        Cache::put($key, [
            'session_id' => $sessionId,
            'state' => $state,
            'message' => $message,
            'updated_at' => now()->toIso8601String(),
        ], now()->addMinutes(30));

        return self::SUCCESS;
    }
}
