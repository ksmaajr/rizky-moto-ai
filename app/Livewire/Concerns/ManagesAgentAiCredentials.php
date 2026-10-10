<?php

namespace App\Livewire\Concerns;

use App\Models\AgentAiCredential;
use App\Services\AI\Providers\AgentKitProvider;
use App\Services\AgentKitWorkerManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

trait ManagesAgentAiCredentials
{
    public string $newAgentCredentialName = '';
    public string $newAgentCredentialToken = '';
    public bool $codexCliAvailable = false;
    public bool $codexLoginInProgress = false;
    public string $codexLoginState = 'idle';
    public string $codexLoginMessage = '';
    public bool $showAgentCredentialForm = false;
    public array $agentAiCredentials = [];
    public int $agentAiCredentialCount = 0;
    public int $agentAiActiveCredentialCount = 0;
    public array $agentAiRuntimeStatus = [];
    public bool $agentAiRuntimeBusy = false;
    public bool $agentCredentialMonitoringOpen = false;
    public array $selectedAgentCredentialMonitoring = [];
    public array $agentCredentialMonitoringLogs = [];
    public int $agentCredentialCooldownMinutes = 1440;

    public function loadAgentAiCredentials(): void
    {
        $this->refreshCodexLoginStatus();
        $this->codexCliAvailable = $this->codexCliIsAvailable();
        $this->refreshAgentAiRuntimeStatus();

        $query = AgentAiCredential::query()
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereNull('user_id');
            })
            ->orderBy('last_used_at')
            ->orderBy('id');

        $rows = $query->get();

        $this->agentAiCredentials = $rows->map(fn (AgentAiCredential $credential): array => [
            'id' => $credential->id,
            'name' => $credential->name,
            'status' => $credential->status,
            'is_active' => (bool) $credential->is_active,
            'request_count' => (int) $credential->request_count,
            'success_count' => (int) $credential->success_count,
            'failure_count' => (int) $credential->failure_count,
            'last_used_at' => $credential->last_used_at?->diffForHumans(),
            'last_success_at' => $credential->last_success_at?->diffForHumans(),
            'last_failure_at' => $credential->last_failure_at?->diffForHumans(),
            'last_error_type' => $credential->last_error_type,
            'cooldown_until' => $credential->cooldown_until?->toIso8601String(),
        ])->all();

        $this->agentAiCredentialCount = count($this->agentAiCredentials);
        $this->agentAiActiveCredentialCount = collect($this->agentAiCredentials)
            ->where('is_active', true)
            ->whereNotIn('status', ['disabled', 'invalid', 'exhausted'])
            ->count();
    }

    public function openAgentCredentialMonitoring(int $credentialId): void
    {
        $credential = $this->agentCredentialForCurrentUser($credentialId);

        if (! $credential) {
            $this->dispatch('toast', type: 'error', title: 'Account tidak ditemukan', message: 'Credential tidak tersedia atau aksesnya tidak diizinkan.');
            return;
        }

        $this->agentCredentialMonitoringOpen = true;
        $this->refreshAgentCredentialMonitoring($credentialId);
    }

    public function refreshAgentCredentialMonitoring(?int $credentialId = null): void
    {
        $credentialId ??= (int) ($this->selectedAgentCredentialMonitoring['id'] ?? 0);

        if ($credentialId <= 0 || ! $this->agentCredentialMonitoringOpen) {
            return;
        }

        $credential = $this->agentCredentialForCurrentUser($credentialId);

        if (! $credential) {
            $this->closeAgentCredentialMonitoring();
            return;
        }

        // Backfill a timer for rate-limit events recorded before the configurable
        // duration existed. The countdown is anchored to last_failure_at.
        if ($credential->last_error_type === 'rate_limited' && ! $credential->cooldown_until && $credential->last_failure_at) {
            $durationMinutes = max(1, min(10080, (int) ($credential->cooldown_duration_minutes ?: 1440)));
            $until = $credential->last_failure_at->copy()->addMinutes($durationMinutes);
            $credential->forceFill([
                'status' => $until->isFuture() ? 'cooldown' : 'error',
                'cooldown_until' => $until,
            ])->save();
            $credential->refresh();
        }

        $this->agentCredentialCooldownMinutes = max(1, min(10080, (int) ($credential->cooldown_duration_minutes ?: 1440)));

        $this->selectedAgentCredentialMonitoring = [
            'id' => $credential->id,
            'name' => $credential->name,
            'status' => $credential->status,
            'is_active' => (bool) $credential->is_active,
            'request_count' => (int) $credential->request_count,
            'success_count' => (int) $credential->success_count,
            'failure_count' => (int) $credential->failure_count,
            'last_used_at' => $credential->last_used_at?->toIso8601String(),
            'last_success_at' => $credential->last_success_at?->toIso8601String(),
            'last_failure_at' => $credential->last_failure_at?->toIso8601String(),
            'cooldown_until' => $credential->cooldown_until?->toIso8601String(),
            'cooldown_duration_minutes' => (int) ($credential->cooldown_duration_minutes ?: 1440),
            'last_error_type' => $credential->last_error_type,
            'last_error' => $credential->last_error,
            'last_exit_code' => $credential->last_exit_code,
            'created_at' => $credential->created_at?->toIso8601String(),
        ];

        $this->agentCredentialMonitoringLogs = \App\Models\ActivityLog::query()
            ->where('metadata->credential_id', $credentialId)
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(function (\App\Models\ActivityLog $log): array {
                return [
                    'id' => $log->id,
                    'title' => $log->title ?: ($log->action ?: 'AgentKit activity'),
                    'description' => $log->description ?: '',
                    'status' => $log->status ?: 'info',
                    'action' => $log->action ?: 'activity',
                    'created_at' => $log->created_at?->toIso8601String(),
                    'http_status' => data_get($log->metadata, 'http_status'),
                    'duration_ms' => data_get($log->metadata, 'duration_ms'),
                    'exit_code' => data_get($log->metadata, 'exit_code'),
                ];
            })
            ->values()
            ->all();
    }

    public function closeAgentCredentialMonitoring(): void
    {
        $this->agentCredentialMonitoringOpen = false;
        $this->selectedAgentCredentialMonitoring = [];
        $this->agentCredentialMonitoringLogs = [];
    }

    public function saveAgentCredentialCooldownPreference(): void
    {
        $this->validate([
            'agentCredentialCooldownMinutes' => ['required', 'integer', 'min:1', 'max:10080'],
        ]);

        $credentialId = (int) ($this->selectedAgentCredentialMonitoring['id'] ?? 0);
        $credential = $credentialId > 0 ? $this->agentCredentialForCurrentUser($credentialId) : null;

        if (! $credential) {
            $this->dispatch('toast', type: 'error', title: 'Account tidak ditemukan', message: 'Pilih credential yang valid sebelum menyimpan durasi cooldown.');
            return;
        }

        $durationMinutes = max(1, min(10080, (int) $this->agentCredentialCooldownMinutes));
        $updates = ['cooldown_duration_minutes' => $durationMinutes];

        // If this account is already rate-limited, recalculate from the original
        // detection time; changing the preference must not restart the clock.
        if ($credential->last_error_type === 'rate_limited' && $credential->last_failure_at) {
            $until = $credential->last_failure_at->copy()->addMinutes($durationMinutes);
            $updates['cooldown_until'] = $until;
            $updates['status'] = $until->isFuture() ? 'cooldown' : 'error';
        }

        $credential->forceFill($updates)->save();
        $this->refreshAgentCredentialMonitoring($credential->id);
        $this->loadAgentAiCredentials();

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Durasi cooldown tersimpan',
            message: 'Durasi ' . $durationMinutes . ' menit akan diterapkan otomatis saat rate limit terdeteksi. Countdown dihitung dari waktu limit terdeteksi.'
        );
    }


    public function refreshAgentAiRuntimeStatus(): void
    {
        try {
            $this->agentAiRuntimeStatus = app(AgentKitWorkerManager::class)->status();
        } catch (\Throwable $e) {
            report($e);
            $this->agentAiRuntimeStatus = [
                'success' => false,
                'running' => false,
                'status' => 'unknown',
                'message' => 'Runtime status belum dapat dibaca. Periksa konfigurasi worker.',
            ];
        }
    }

    public function controlAgentAiRuntime(string $action): void
    {
        $action = strtolower(trim($action));
        if (! in_array($action, ['start', 'stop', 'restart'], true)) {
            $this->dispatch('toast', type: 'error', title: 'Aksi tidak valid', message: 'Gunakan Start, Stop, atau Restart.');
            return;
        }

        if ($this->agentAiRuntimeBusy) {
            return;
        }

        $this->agentAiRuntimeBusy = true;

        try {
            $manager = app(AgentKitWorkerManager::class);
            $result = match ($action) {
                'start' => $manager->start(),
                'stop' => $manager->stop(),
                'restart' => $manager->restart(),
            };

            $this->refreshAgentAiRuntimeStatus();
            $success = (bool) ($result['success'] ?? false);
            $this->dispatch(
                'toast',
                type: $success ? 'success' : 'error',
                title: $success ? 'Agent AI runtime diperbarui' : 'Kontrol runtime gagal',
                message: (string) ($result['message'] ?? 'Perintah runtime selesai.')
            );
        } catch (\Throwable $e) {
            report($e);
            $this->refreshAgentAiRuntimeStatus();
            $this->dispatch('toast', type: 'error', title: 'Kontrol runtime gagal', message: 'Runtime tidak dapat dikendalikan. Periksa konfigurasi driver dan log worker.');
        } finally {
            $this->agentAiRuntimeBusy = false;
        }
    }


    /**
     * Reflect the PowerShell login process in persistent shared cache, rather
     * than relying on the short-lived Livewire request spinner.
     */
    public function refreshCodexLoginStatus(): void
    {
        $status = Cache::get($this->codexLoginCacheKey(), []);
        $this->codexLoginState = (string) ($status['state'] ?? 'idle');
        $this->codexLoginMessage = (string) ($status['message'] ?? '');
        $this->codexLoginInProgress = in_array($this->codexLoginState, [
            'starting',
            'waiting_for_login',
            'importing',
        ], true);
    }

    private function codexLoginCacheKey(): string
    {
        return 'agentkit:codex-login:' . (int) Auth::id();
    }

    /**
     * Start the official Codex CLI browser login in a separate local PowerShell
     * window, then import the resulting file-based ChatGPT session.
     *
     * This bridge is intentionally limited to local Windows development. A
     * remote web server cannot safely open a browser on the user's workstation.
     */
    public function beginCodexLogin(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->dispatch(
                'toast',
                type: 'warning',
                title: 'Login perlu dilakukan di host AgentKit',
                message: 'Login browser otomatis saat ini hanya didukung pada development lokal Windows. Jalankan codex login pada host AgentKit, lalu import sesi melalui prosedur host tersebut.'
            );
            return;
        }

        if (! $this->codexCliIsAvailable()) {
            $this->dispatch(
                'toast',
                type: 'error',
                title: 'Codex CLI tidak terdeteksi',
                message: 'Atur CODEX_CLI_BINARY ke path codex.cmd yang valid, lalu muat ulang Settings.'
            );
            return;
        }

        $this->refreshCodexLoginStatus();
        if ($this->codexLoginInProgress) {
            $this->dispatch('toast', type: 'info', title: 'Login masih berjalan', message: 'Selesaikan login pada jendela PowerShell yang sudah terbuka sebelum menambahkan akun berikutnya.');
            return;
        }

        $userId = (int) Auth::id();
        $loginSessionId = bin2hex(random_bytes(16));
        Cache::put($this->codexLoginCacheKey(), [
            'session_id' => $loginSessionId,
            'state' => 'starting',
            'message' => 'Menyiapkan sesi Codex terisolasi...',
            'updated_at' => now()->toIso8601String(),
        ], now()->addMinutes(30));
        $this->refreshCodexLoginStatus();

        $accountName = $this->uniqueAgentCredentialName($this->newAgentCredentialName);

        $binary = trim((string) config('services.agent_ai.codex_cli_binary', ''), " \\t\\n\\r\\0\\x0B\\\"'");
        if ($binary === '') {
            $binary = 'codex.cmd';
        }

        $escape = static fn (string $value): string => str_replace("'", "''", $value);
        $scriptPath = storage_path('framework/agentkit-login-' . $userId . '-' . bin2hex(random_bytes(5)) . '.ps1');

        // Each Add Account flow gets a fresh Codex home so the CLI cannot reuse
        // the developer's default ~/.codex session or another account's session.
        $accountHome = storage_path('framework/agentkit-codex-home-' . $userId . '-' . bin2hex(random_bytes(8)));
        $authFile = $accountHome . DIRECTORY_SEPARATOR . 'auth.json';
        $phpBinary = PHP_BINARY;
        $basePath = base_path();
        $statusCommand = static function (string $state, string $message) use ($escape, $phpBinary, $userId, $loginSessionId): string {
            return sprintf(
                "& '%s' artisan agent-ai:codex-login-status --user-id=%d --session-id '%s' --state '%s' --message '%s'",
                $escape($phpBinary),
                $userId,
                $loginSessionId,
                $state,
                $escape($message)
            );
        };
        $importCommand = sprintf(
            "& '%s' artisan agent-ai:codex-import --user-id=%d --name '%s' --auth-file '%s'",
            $escape($phpBinary),
            $userId,
            $escape($accountName),
            $escape($authFile)
        );

        $script = implode("\r\n", [
            "\$ErrorActionPreference = 'Stop'",
            "Set-Location '" . $escape($basePath) . "'",
            "Write-Host 'Rizky Moto AI - ChatGPT / Codex login' -ForegroundColor Cyan",
            "Write-Host 'Login ini menggunakan sesi terpisah khusus untuk akun baru.' -ForegroundColor Yellow",
            "Write-Host 'Selesaikan login pada browser yang dibuka Codex CLI.'",
            $statusCommand('waiting_for_login', 'Menunggu login selesai pada browser Codex.'),
            "New-Item -ItemType Directory -Force -Path '" . $escape($accountHome) . "' | Out-Null",
            "\$env:CODEX_HOME = '" . $escape($accountHome) . "'",
            // Force file-based auth storage; keyring/auto storage may otherwise
            // reuse an OS-level login that is shared between Codex homes.
            "'cli_auth_credentials_store = \"file\"' | Set-Content -LiteralPath '" . $escape($accountHome . DIRECTORY_SEPARATOR . 'config.toml') . "' -Encoding utf8",
            "\$codex = '" . $escape($binary) . "'",
            "& \$codex login",
            "\$loginExitCode = \$LASTEXITCODE; if (\$loginExitCode -ne 0) { " . $statusCommand('failed', 'Login gagal atau dibatalkan. Credential tidak diimpor.') . "; Remove-Item -LiteralPath '" . $escape($accountHome) . "' -Recurse -Force -ErrorAction SilentlyContinue; Write-Host 'Login gagal atau dibatalkan. Credential tidak diimpor.' -ForegroundColor Red; Read-Host 'Tekan Enter untuk menutup'; exit \$loginExitCode }",
            "if (-not (Test-Path -LiteralPath '" . $escape($authFile) . "')) { " . $statusCommand('failed', 'File auth.json tidak ditemukan pada CODEX_HOME terisolasi; akun tidak diimpor.') . "; Write-Host 'AUTH.JSON TIDAK DITEMUKAN pada CODEX_HOME terisolasi. CLI mungkin tidak menghormati CODEX_HOME atau tidak memakai file auth store. Akun tidak diimpor.' -ForegroundColor Red; Remove-Item -LiteralPath '" . $escape($accountHome) . "' -Recurse -Force -ErrorAction SilentlyContinue; Read-Host 'Tekan Enter untuk menutup'; exit 2 }",
            $statusCommand('importing', 'Login berhasil. Mengimpor access token terenkripsi ke credential pool...'),
            $importCommand,
            "if (\$LASTEXITCODE -eq 0) { " . $statusCommand('completed', 'Login dan import selesai. Jalankan Test Token untuk validasi AgentKit.') . "; Write-Host 'Import selesai. Buka Settings dan jalankan Test Token.' -ForegroundColor Green; Remove-Item -LiteralPath '" . $escape($accountHome) . "' -Recurse -Force -ErrorAction SilentlyContinue } else { " . $statusCommand('failed', 'Import credential gagal. Periksa pesan pada jendela PowerShell.') . "; Write-Host 'Import gagal. Sesi sementara dipertahankan di ' + '" . $escape($accountHome) . "' + ' untuk diagnosis; hapus folder ini setelah diperiksa.' -ForegroundColor Red }",
            "Read-Host 'Tekan Enter untuk menutup jendela ini'",
            "",
        ]);

        if (! is_dir(dirname($scriptPath))) {
            @mkdir(dirname($scriptPath), 0770, true);
        }

        if (file_put_contents($scriptPath, $script, LOCK_EX) === false) {
            Cache::put($this->codexLoginCacheKey(), ['session_id' => $loginSessionId, 'state' => 'failed', 'message' => 'Script login tidak dapat ditulis ke storage/framework.', 'updated_at' => now()->toIso8601String()], now()->addMinutes(30));
            $this->refreshCodexLoginStatus();
            $this->dispatch('toast', type: 'error', title: 'Tidak dapat menyiapkan login', message: 'Script login tidak dapat ditulis ke storage/framework.');
            return;
        }

        try {
            $result = Process::timeout(5)->run([
                'powershell.exe',
                '-NoProfile',
                '-NonInteractive',
                '-ExecutionPolicy',
                'Bypass',
                '-Command',
                // -NoExit keeps errors visible; quote the script path because the project
                // directory commonly contains spaces on Windows.
                "Start-Process -FilePath 'powershell.exe' -ArgumentList @('-NoProfile','-ExecutionPolicy','Bypass','-NoExit','-File','\"" . str_replace('"', '\"', $scriptPath) . "\"')",
            ]);

            if ($result->failed()) {
                @unlink($scriptPath);
                Cache::put($this->codexLoginCacheKey(), ['session_id' => $loginSessionId, 'state' => 'failed', 'message' => 'PowerShell tidak dapat membuka jendela login Codex.', 'updated_at' => now()->toIso8601String()], now()->addMinutes(30));
                $this->refreshCodexLoginStatus();
                report(new \RuntimeException(trim($result->errorOutput() ?: $result->output())));
                $this->dispatch('toast', type: 'error', title: 'Jendela login gagal dibuka', message: 'PowerShell tidak dapat membuka jendela login Codex. Jalankan codex login secara manual pada terminal host lokal.');
                return;
            }

            $this->dispatch(
                'toast',
                type: 'success',
                title: 'Jendela login Codex dibuka',
                message: 'Selesaikan login pada jendela PowerShell. Jendela akan tetap terbuka agar pesan error dapat dibaca jika login atau import gagal.'
            );
        } catch (\Throwable $e) {
            Cache::put($this->codexLoginCacheKey(), ['session_id' => $loginSessionId, 'state' => 'failed', 'message' => 'Login Codex gagal dimulai. Periksa log aplikasi.', 'updated_at' => now()->toIso8601String()], now()->addMinutes(30));
            $this->refreshCodexLoginStatus();
            report($e);
            $this->dispatch('toast', type: 'error', title: 'Login Codex gagal dimulai', message: 'Periksa log aplikasi dan pastikan aplikasi berjalan pada sesi Windows interaktif yang sama.');
        }
    }

    private function codexCliIsAvailable(): bool
    {
        // Avoid spawning a subprocess on every Livewire render: process startup
        // can block the settings page, especially on Windows.
        $binary = trim((string) config('services.agent_ai.codex_cli_binary', ''), " \\t\\n\\r\\0\\x0B\\\"'");
        if ($binary === '') {
            $binary = PHP_OS_FAMILY === 'Windows' ? 'codex.cmd' : 'codex';
        }

        if (str_contains($binary, '/') || str_contains($binary, '\\')) {
            return is_file($binary);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $appData = (string) getenv('APPDATA');
            return $appData !== '' && is_file(rtrim($appData, '\\\\/') . DIRECTORY_SEPARATOR . 'npm' . DIRECTORY_SEPARATOR . $binary);
        }

        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $directory) {
            if (is_file(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $binary)) {
                return true;
            }
        }

        return false;
    }

    /** Ensure every imported account has a distinct, readable label within this user's pool. */
    private function uniqueAgentCredentialName(?string $requestedName = null): string
    {
        $base = mb_substr(trim((string) $requestedName), 0, 110);
        if ($base === '') {
            $base = 'Codex Account';
        }

        $query = AgentAiCredential::query()
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereNull('user_id');
            });

        $existing = $query->pluck('name')->filter()->map(
            static fn ($name): string => mb_strtolower(trim((string) $name))
        )->all();

        if (! in_array(mb_strtolower($base), $existing, true)) {
            return $base;
        }

        for ($suffix = 2; $suffix < 10000; $suffix++) {
            $candidate = mb_substr($base, 0, 120 - mb_strlen((string) $suffix) - 1) . ' ' . $suffix;
            if (! in_array(mb_strtolower($candidate), $existing, true)) {
                return $candidate;
            }
        }

        return mb_substr($base, 0, 100) . ' ' . now()->format('YmdHis');
    }

    /**
     * Raw token entry is intentionally disabled. New accounts must pass through
     * the guarded Codex importer and explicit compatibility validation.
     */
    public function addAgentAiCredential(): void
    {
        $this->dispatch(
            'toast',
            type: 'warning',
            title: 'Import melalui Codex CLI',
            message: 'Input token manual dinonaktifkan. Gunakan codex login dan agent-ai:codex-import, lalu validasi credential sebelum diaktifkan.'
        );
    }

    public function testAgentAiCredential(int $credentialId): void
    {
        $credential = $this->agentCredentialForCurrentUser($credentialId);

        if (! $credential) {
            return;
        }

        if (! $credential->is_active && $credential->status !== 'pending_validation') {
            $this->dispatch(
                'toast',
                type: 'warning',
                title: 'Credential tidak siap diuji',
                message: 'Credential harus aktif atau berstatus Pending Validation.'
            );
            return;
        }

        try {
            $result = app(AgentKitProvider::class)->testCredential($credential);
            $this->loadAgentAiCredentials();

            $this->dispatch(
                'toast',
                type: ($result['success'] ?? false) ? 'success' : 'error',
                title: ($result['success'] ?? false) ? 'Agent credential valid' : 'Agent credential test gagal',
                message: (string) ($result['message'] ?? 'Test selesai.'),
            );
        } catch (\Throwable $e) {
            report($e);
            $this->loadAgentAiCredentials();

            $this->dispatch(
                'toast',
                type: 'error',
                title: 'Agent credential test gagal',
                message: $e->getMessage(),
            );
        }
    }

    public function toggleAgentAiCredential(int $credentialId): void
    {
        $credential = $this->agentCredentialForCurrentUser($credentialId);

        if (! $credential) {
            return;
        }

        if ($credential->status === 'pending_validation') {
            $this->dispatch(
                'toast',
                type: 'warning',
                title: 'Credential belum tervalidasi',
                message: 'Jalankan Test Token terlebih dahulu. Credential baru hanya dapat aktif setelah live validation berhasil.'
            );
            return;
        }

        $credential->update([
            'is_active' => ! $credential->is_active,
            'status' => $credential->is_active ? 'disabled' : 'active',
            'cooldown_until' => null,
        ]);

        $this->loadAgentAiCredentials();
    }

    public function deleteAgentAiCredential(int $credentialId): void
    {
        $credential = $this->agentCredentialForCurrentUser($credentialId);

        if (! $credential) {
            return;
        }

        $credential->delete();
        $this->loadAgentAiCredentials();

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Agent credential removed',
            message: 'Credential dihapus dari pool.'
        );
    }

    private function agentCredentialForCurrentUser(int $credentialId): ?AgentAiCredential
    {
        return AgentAiCredential::query()
            ->whereKey($credentialId)
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereNull('user_id');
            })
            ->first();
    }
}
