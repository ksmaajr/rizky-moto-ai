<?php

namespace App\Console\Commands;

use App\Models\AgentAiCredential;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class ImportCodexCredentialCommand extends Command
{
    protected $signature = 'agent-ai:codex-import
                            {--name=Codex Account : Friendly account label}
                            {--user-id= : Laravel user ID that owns this account}
                            {--auth-file= : Explicit Codex auth.json path (file-store mode only)}';

    protected $description = 'Import the access token from a locally authenticated Codex CLI session into the encrypted AgentKit credential pool for explicit live validation.';

    public function handle(): int
    {
        $userId = filter_var($this->option('user-id'), FILTER_VALIDATE_INT);
        if (! $userId || $userId < 1) {
            $this->error('Tentukan --user-id dengan ID user Laravel pemilik credential.');
            return self::FAILURE;
        }

        $path = $this->resolveAuthPath();
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            $this->error('File auth Codex tidak ditemukan atau tidak dapat dibaca. Jalankan "codex login" pada mesin ini terlebih dahulu dan pastikan Codex menggunakan file credential store.');
            return self::FAILURE;
        }

        try {
            $raw = File::get($path);
            $auth = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $this->error('File auth Codex bukan JSON valid. Tidak ada credential yang diimpor.');
            return self::FAILURE;
        } finally {
            unset($raw);
        }

        if (! is_array($auth) || ($auth['auth_mode'] ?? null) !== 'chatgpt') {
            $this->error('Sesi Codex tidak terdeteksi sebagai login ChatGPT. API-key login tidak diimpor.');
            return self::FAILURE;
        }

        $token = $auth['tokens']['access_token'] ?? null;
        if (! is_string($token) || strlen(trim($token)) < 20) {
            $this->error('Access token tidak tersedia pada struktur file auth ini. Format Codex berubah atau credential store bukan file-based; import dibatalkan.');
            return self::FAILURE;
        }

        $name = trim((string) $this->option('name'));
        if ($name === '') {
            $this->error('Nama akun tidak boleh kosong.');
            return self::FAILURE;
        }

        // Never persist auth.json, refresh tokens, ID tokens, or account IDs.
        // The extracted access token is encrypted by AgentAiCredential's cast.
        $credential = AgentAiCredential::create([
            'user_id' => $userId,
            'name' => mb_substr($name, 0, 120),
            'access_token' => trim($token),
            'is_active' => false,
            'status' => 'pending_validation',
        ]);

        unset($token, $auth);

        $this->components->info('Access token berhasil diimpor ke encrypted storage sebagai PENDING VALIDATION.');
        $this->line('Credential ID: '.$credential->id);
        $this->warn('Credential belum aktif. Gunakan tombol Test Token di Settings untuk menjalankan satu live image request yang dapat memakai kuota.');
        $this->line('Catatan: adapter ini membaca struktur auth.json Codex yang bersifat internal dan dapat berubah. Jangan gunakan pada server multi-user tanpa isolasi CODEX_HOME per akun.');

        return self::SUCCESS;
    }

    private function resolveAuthPath(): string
    {
        $explicit = trim((string) $this->option('auth-file'));
        if ($explicit !== '') {
            return $explicit;
        }

        $codexHome = trim((string) getenv('CODEX_HOME'));
        if ($codexHome !== '') {
            return rtrim($codexHome, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'auth.json';
        }

        $home = getenv('USERPROFILE') ?: getenv('HOME') ?: '';
        if ($home === '') {
            return '';
        }

        return rtrim($home, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.codex'.DIRECTORY_SEPARATOR.'auth.json';
    }
}
