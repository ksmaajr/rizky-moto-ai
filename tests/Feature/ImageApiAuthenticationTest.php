<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generation_status_requires_a_bearer_api_key(): void
    {
        $this->getJson('/api/v1/images/generations/1')->assertUnauthorized();
    }

    public function test_revoked_api_key_is_rejected(): void
    {
        $user = User::factory()->create();
        $secret = 'rms_live_' . str()->random(48);

        ApiKey::query()->create([
            'user_id' => $user->id, 'name' => 'Revoked test key',
            'prefix' => substr($secret, 0, 16), 'key_hash' => hash('sha256', $secret),
            'scopes' => ['images:read'], 'rate_limit' => 60, 'revoked_at' => now(),
        ]);

        $this->withToken($secret)->getJson('/api/v1/images/generations/1')->assertUnauthorized();
    }

    public function test_valid_key_without_read_scope_cannot_read_generation_status(): void
    {
        $user = User::factory()->create();
        $secret = 'rms_live_' . str()->random(48);

        ApiKey::query()->create([
            'user_id' => $user->id, 'name' => 'Generate-only key',
            'prefix' => substr($secret, 0, 16), 'key_hash' => hash('sha256', $secret),
            'scopes' => ['images:generate'], 'rate_limit' => 60,
        ]);

        $this->withToken($secret)->getJson('/api/v1/images/generations/1')->assertForbidden();
    }
}
