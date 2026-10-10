<?php

namespace Tests\Unit\Services;

use App\Models\AgentAiCredential;
use App\Models\User;
use App\Services\AgentAiCredentialPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentAiCredentialPoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_credentials_are_classified_as_non_usable_but_retryable_for_failover(): void
    {
        $result = app(AgentAiCredentialPool::class)->classifyFailure(
            'HTTP 401: invalid token',
            1,
        );

        $this->assertSame('invalid', $result['status']);
        $this->assertTrue($result['retry']);
        $this->assertSame('invalid_credential', $result['reason']);
    }

    public function test_forbidden_endpoint_errors_do_not_mark_a_credential_as_invalid(): void
    {
        $result = app(AgentAiCredentialPool::class)->classifyFailure(
            'Codex Images API HTTP 403: Forbidden',
            1,
        );

        $this->assertSame('failed', $result['status']);
        $this->assertFalse($result['retry']);
        $this->assertSame('provider_access_denied', $result['reason']);
    }

    public function test_rate_limit_errors_enter_a_bounded_cooldown(): void
    {
        $result = app(AgentAiCredentialPool::class)->classifyFailure(
            'HTTP 429: rate limit exceeded',
            1,
        );

        $this->assertSame('cooldown', $result['status']);
        $this->assertTrue($result['retry']);
        $this->assertSame(60, $result['seconds']);
        $this->assertSame('rate_limited', $result['reason']);
    }

    public function test_backend_errors_are_retryable_with_a_short_cooldown(): void
    {
        $result = app(AgentAiCredentialPool::class)->classifyFailure(
            'Agent backend returned an unexpected error',
            70,
        );

        $this->assertSame('cooldown', $result['status']);
        $this->assertTrue($result['retry']);
        $this->assertSame(30, $result['seconds']);
        $this->assertSame('agent_backend_error', $result['reason']);
    }

    public function test_unknown_errors_are_not_automatically_retried(): void
    {
        $result = app(AgentAiCredentialPool::class)->classifyFailure(
            'Invalid product reference image',
            2,
        );

        $this->assertSame('failed', $result['status']);
        $this->assertFalse($result['retry']);
        $this->assertSame('agent_request_failed', $result['reason']);
    }

    public function test_acquire_skips_invalid_credentials_and_can_select_the_next_account(): void
    {
        $user = User::factory()->create();

        $invalid = AgentAiCredential::query()->create([
            'user_id' => $user->id,
            'name' => 'Invalid account',
            'access_token' => 'secret-invalid-token',
            'is_active' => true,
            'status' => 'invalid',
        ]);

        $healthy = AgentAiCredential::query()->create([
            'user_id' => $user->id,
            'name' => 'Healthy account',
            'access_token' => 'secret-healthy-token',
            'is_active' => true,
            'status' => 'active',
        ]);

        $pool = app(AgentAiCredentialPool::class);
        $selected = $pool->acquire($user->id);

        $this->assertNotNull($selected);
        $this->assertSame($healthy->id, $selected['id']);
        $this->assertSame('secret-healthy-token', $selected['key']);
        $this->assertSame(1, $healthy->fresh()->request_count);
        $this->assertSame(0, $invalid->fresh()->request_count);
    }

    public function test_acquire_respects_excluded_ids_for_same_request_failover(): void
    {
        $user = User::factory()->create();

        $first = AgentAiCredential::query()->create([
            'user_id' => $user->id,
            'name' => 'Account one',
            'access_token' => 'secret-account-one',
            'is_active' => true,
            'status' => 'active',
        ]);

        $second = AgentAiCredential::query()->create([
            'user_id' => $user->id,
            'name' => 'Account two',
            'access_token' => 'secret-account-two',
            'is_active' => true,
            'status' => 'active',
        ]);

        $pool = app(AgentAiCredentialPool::class);
        $selectedFirst = $pool->acquire($user->id);
        $selectedSecond = $pool->acquire($user->id, [$first->id]);

        $this->assertSame($first->id, $selectedFirst['id']);
        $this->assertSame($second->id, $selectedSecond['id']);
        $this->assertSame(1, $first->fresh()->request_count);
        $this->assertSame(1, $second->fresh()->request_count);
    }

    public function test_access_token_is_encrypted_at_rest_and_decrypted_for_provider_use(): void
    {
        $credential = AgentAiCredential::query()->create([
            'name' => 'Encryption check',
            'access_token' => 'do-not-store-this-in-plain-text',
            'is_active' => true,
            'status' => 'active',
        ]);

        $storedValue = AgentAiCredential::query()
            ->whereKey($credential->id)
            ->value('access_token');

        $this->assertNotSame('do-not-store-this-in-plain-text', $storedValue);
        $this->assertSame('do-not-store-this-in-plain-text', $credential->fresh()->access_token);
    }
}
