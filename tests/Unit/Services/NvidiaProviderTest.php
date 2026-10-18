<?php

namespace Tests\Unit\Services;

use App\Services\AI\Providers\NvidiaProvider;
use App\Services\ActivityLogService;
use App\Services\NvidiaApiKeyPool;
use PHPUnit\Framework\TestCase;

class NvidiaProviderTest extends TestCase
{
    public function test_provider_exposes_only_supported_nvidia_models(): void
    {
        $provider = new NvidiaProvider(
            $this->createMock(ActivityLogService::class),
            $this->createMock(NvidiaApiKeyPool::class),
        );

        $this->assertSame('nvidia', $provider->name());
        $this->assertTrue($provider->supportsModel('black-forest-labs/flux.2-klein-4b'));
        $this->assertTrue($provider->supportsModel('black-forest-labs/flux.3-image-klein'));
        $this->assertFalse($provider->supportsModel('openai/gpt-image-2.5-sunburst'));
        $this->assertFalse($provider->supportsModel('unknown/model'));
    }
}
