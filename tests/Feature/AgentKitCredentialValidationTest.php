<?php

namespace Tests\Feature;

use App\Models\AgentAiCredential;
use App\Models\User;
use App\Services\AI\Providers\AgentKitProvider;
use App\Services\AgentAiCredentialPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgentKitCredentialValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_validation_activates_credential_without_calling_image_generation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $token = $this->jwt([
            'exp' => now()->addHour()->timestamp,
            'https://api.openai.com/auth' => [
                'chatgpt_account_id' => 'test-account-id',
            ],
        ]);

        $credential = AgentAiCredential::query()->create([
            'user_id' => $user->id,
            'name' => 'Codex test account',
            'access_token' => $token,
            'is_active' => false,
            'status' => 'pending_validation',
        ]);

        Http::fake([
            'https://chatgpt.com/backend-api/codex/responses' => Http::response(
                implode("\n", [
                    'event: response.completed',
                    'data: {"type":"response.completed","response":{"status":"completed","output":[]}}',
                    '',
                    '',
                ]),
                200,
                ['Content-Type' => 'text/event-stream'],
            ),
        ]);

        $result = app(AgentKitProvider::class)->testCredential($credential);

        $this->assertTrue($result['success']);
        $this->assertSame('active', $result['status']);
        $this->assertFalse($result['image_generation_tested']);

        $fresh = $credential->fresh();
        $this->assertTrue($fresh->is_active);
        $this->assertSame('active', $fresh->status);

        $selected = app(AgentAiCredentialPool::class)->acquire($user->id);
        $this->assertNotNull($selected);
        $this->assertSame($credential->id, $selected['id']);
        $this->assertSame($token, $selected['key']);

        Http::assertSent(fn ($request) =>
            $request->method() === 'GET'
            && str_contains($request->url(), '/backend-api/codex/responses')
            && $request->method() === 'POST'
            && $request['stream'] === true
            && $request->hasHeader('Authorization', 'Bearer ' . $token)
            && $request->hasHeader('ChatGPT-Account-ID', 'test-account-id')
            && ! str_contains($request->url(), '/images/')
        );
    }

    private function jwt(array $claims): string
    {
        $encode = static fn (array $value): string => rtrim(
            strtr(base64_encode(json_encode($value, JSON_THROW_ON_ERROR)), '+/', '-_'),
            '='
        );

        return $encode(['alg' => 'none', 'typ' => 'JWT']) . '.' . $encode($claims) . '.test-signature';
    }
}
