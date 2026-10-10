<?php

namespace App\Console\Commands;

use App\Models\AgentAiCredential;
use App\Models\User;
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
        if (!$userId || $userId < 1) {
            $this->error('Tentukan --user-id dengan ID user Laravel pemilik credential.');
            return self::FAILURE;
        }

        if (!User::query()->whereKey($userId)->exists()) {
            $this->error('User Laravel pemilik credential tidak ditemukan. Tidak ada credential yang diimpor.');
            return self::FAILURE;
        }

        $path = $this->resolveAuthPath();
        if ($path === '' || is_link($path) || !is_file($path) || !is_readable($path) || (filesize($path) ?: 0) > 1048576) {
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

        if (!is_array($auth) || ($auth['auth_mode'] ?? null) !== 'chatgpt') {
            $this->error('Sesi Codex tidak terdeteksi sebagai login ChatGPT. API-key login tidak diimpor.');
            return self::FAILURE;
        }

        $token = $auth['tokens']['access_token'] ?? null;
        if (!is_string($token) || strlen(trim($token)) < 20) {
            $this->error('Access token tidak tersedia pada struktur file auth ini. Format Codex berubah atau credential store bukan file-based; import dibatalkan.');
            return self::FAILURE;
        }

        $token = trim($token);
        $metadata = $this->extractAccountMetadata($auth);
        if (strlen($token) > 65535) {
            unset($token, $auth);
            $this->error('Access token memiliki ukuran tidak wajar. Import dibatalkan.');
            return self::FAILURE;
        }

        // Avoid silently creating duplicate accounts when the same Codex session
        // is imported repeatedly. Compare decrypted tokens only in memory.
        $existing = AgentAiCredential::query()
            ->where('user_id', $userId)
            ->get(['id', 'access_token']);

        foreach ($existing as $storedCredential) {
            if (is_string($storedCredential->access_token) && hash_equals($storedCredential->access_token, $token)) {
                unset($token, $auth, $existing, $storedCredential);
                $this->error('Sesi Codex ini sudah terdaftar untuk user tersebut. Tidak ada duplikat yang dibuat.');
                return self::FAILURE;
            }
        }

        unset($existing, $storedCredential);

        $requestedName = trim((string) $this->option('name'));
        if ($requestedName === '') {
            $this->error('Nama akun tidak boleh kosong.');
            return self::FAILURE;
        }

        // Prefer the username supplied by Codex when it exists. If not, retain
        // the friendly fallback; do not fabricate identity fields from the email. 
        $name = $metadata['username'] ?: $requestedName;

        // Keep account labels unique even when this command is invoked directly
        // rather than through the Settings Add Account flow.
        $existingNames = AgentAiCredential::query()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->pluck('name')
            ->filter()
            ->map(static fn ($value): string => mb_strtolower(trim((string) $value)))
            ->all();
        $baseName = mb_substr($name, 0, 110);
        $uniqueName = $baseName;
        for ($suffix = 2; in_array(mb_strtolower($uniqueName), $existingNames, true); $suffix++) {
            $uniqueName = mb_substr($baseName, 0, 120 - mb_strlen((string) $suffix) - 1) . ' ' . $suffix;
        }

        // Never persist auth.json, refresh tokens, ID tokens, or account IDs.
        // The extracted access token is encrypted by AgentAiCredential's cast.
        $credential = AgentAiCredential::create([
            'user_id' => $userId,
            'name' => $uniqueName,
            'username' => $metadata['username'],
            'email' => $metadata['email'],
            'access_token' => $token,
            'is_active' => false,
            'status' => 'pending_validation',
        ]);

        unset($token, $auth);

        $this->components->info('Access token berhasil diimpor ke encrypted storage sebagai PENDING VALIDATION.');
        $this->line('Credential ID: '.$credential->id);
        $this->line('Codex username: '.($credential->username ?: 'Tidak tersedia dari Codex'));
        $this->line('Codex email: '.($credential->email ?: 'Tidak tersedia dari Codex'));
        $this->warn('Credential belum aktif. Gunakan tombol Test Token di Settings untuk menjalankan satu live image request yang dapat memakai kuota.');
        $this->line('Catatan: adapter ini membaca struktur auth.json Codex yang bersifat internal dan dapat berubah. Jangan gunakan pada server multi-user tanpa isolasi CODEX_HOME per akun.');

        return self::SUCCESS;
    }


    /**
     * Read optional identity metadata from the Codex auth payload/JWT claims.
     * These claims are used only for display; auth.json and identity tokens are
     * never persisted. Codex does not guarantee that either field is present.
     *
     * @return array{username: ?string, email: ?string}
     */
    private function extractAccountMetadata(array $auth): array
    {
        $sources = [];

        foreach ([$auth['profile'] ?? null, $auth['user'] ?? null] as $source) {
            if (is_array($source)) {
                $sources[] = $source;
            }
        }

        foreach (['id_token', 'access_token'] as $tokenKey) {
            $jwt = $auth['tokens'][$tokenKey] ?? null;
            if (! is_string($jwt)) {
                continue;
            }

            $parts = explode('.', $jwt);
            if (count($parts) < 2 || $parts[1] === '') {
                continue;
            }

            $payload = strtr($parts[1], '-_', '+/');
            $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
            $decoded = base64_decode($payload, true);
            if (! is_string($decoded)) {
                continue;
            }

            try {
                $claims = json_decode($decoded, true, 32, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                continue;
            }

            if (! is_array($claims)) {
                continue;
            }

            // OpenAI profile claims may be namespaced rather than top-level.
            foreach ([
                $claims['profile'] ?? null,
                $claims['https://api.openai.com/profile'] ?? null,
                $claims['https://api.openai.com/auth'] ?? null,
            ] as $nested) {
                if (is_array($nested)) {
                    $sources[] = $nested;
                }
            }

            $sources[] = $claims;
        }

        // Codex does not expose a stable "username" field in every session.
        // Some accounts expose only a display/name claim inside the profile or
        // namespaced OpenAI claims, so accept those as the visible account label.
        $username = $this->firstMetadataValue($sources, [
            'preferred_username',
            'username',
            'user_name',
            'display_name',
            'nickname',
            'handle',
            'name',
            'given_name',
        ]);

        $email = $this->firstMetadataValue($sources, ['email']);
        if ($email !== null) {
            $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? mb_strtolower($email) : null;
        }

        // Never treat an email address as a username.
        if ($username !== null && filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $username = null;
        }

        return [
            'username' => $username !== null ? mb_substr($username, 0, 190) : null,
            'email' => $email !== null ? mb_substr($email, 0, 254) : null,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $sources
     */
    private function firstMetadataValue(array $sources, array $keys): ?string
    {
        foreach ($sources as $source) {
            foreach ($keys as $key) {
                $value = $source[$key] ?? null;
                if (! is_string($value) || trim($value) === '') {
                    continue;
                }

                $value = trim($value);
                if (mb_strlen($value) > 254 || preg_match('/[\\x00-\\x1F\\x7F]/u', $value)) {
                    continue;
                }

                return $value;
            }
        }

        return null;
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
