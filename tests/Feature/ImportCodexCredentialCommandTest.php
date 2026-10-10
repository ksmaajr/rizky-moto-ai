<?php

namespace Tests\Feature;

use App\Models\AgentAiCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ImportCodexCredentialCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_requires_an_existing_laravel_user(): void
    {
        $path = $this->makeAuthFile('test-access-token-value-long-enough');

        try {
            $exitCode = Artisan::call('agent-ai:codex-import', [
                '--user-id' => 999999,
                '--auth-file' => $path,
                '--name' => 'Test Codex',
            ]);

            $this->assertSame(1, $exitCode);
            $this->assertDatabaseCount('agent_ai_credentials', 0);
        } finally {
            @unlink($path);
        }
    }

    public function test_import_encrypts_token_and_keeps_credential_pending_validation(): void
    {
        $user = User::factory()->create();
        $token = 'test-access-token-value-long-enough-to-pass-validation';
        $path = $this->makeAuthFile($token);

        try {
            $exitCode = Artisan::call('agent-ai:codex-import', [
                '--user-id' => $user->id,
                '--auth-file' => $path,
                '--name' => 'Test Codex',
            ]);

            $this->assertSame(0, $exitCode);
            $credential = AgentAiCredential::query()->where('user_id', $user->id)->firstOrFail();

            $this->assertSame($token, $credential->access_token);
            $this->assertSame('pending_validation', $credential->status);
            $this->assertFalse($credential->is_active);
            $this->assertStringNotContainsString($token, (string) $credential->getRawOriginal('access_token'));
        } finally {
            @unlink($path);
        }
    }

    public function test_import_does_not_duplicate_the_same_session_for_one_user(): void
    {
        $user = User::factory()->create();
        $token = 'test-access-token-value-long-enough-to-pass-validation';
        $path = $this->makeAuthFile($token);

        try {
            Artisan::call('agent-ai:codex-import', [
                '--user-id' => $user->id,
                '--auth-file' => $path,
                '--name' => 'Test Codex',
            ]);

            $exitCode = Artisan::call('agent-ai:codex-import', [
                '--user-id' => $user->id,
                '--auth-file' => $path,
                '--name' => 'Test Codex Again',
            ]);

            $this->assertSame(1, $exitCode);
            $this->assertDatabaseCount('agent_ai_credentials', 1);
        } finally {
            @unlink($path);
        }
    }

    private function makeAuthFile(string $token): string
    {
        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = $directory.'/codex-auth-'.bin2hex(random_bytes(6)).'.json';
        file_put_contents($path, json_encode([
            'auth_mode' => 'chatgpt',
            'tokens' => ['access_token' => $token],
        ], JSON_THROW_ON_ERROR));

        return $path;
    }
}
