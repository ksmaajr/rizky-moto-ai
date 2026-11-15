<?php

namespace Tests\Feature;

use Tests\TestCase;

class TakeoverTest extends TestCase
{
    public function test_enabled_takeover_replaces_front_and_login_pages(): void
    {
        config(['takeover.enabled' => true]);

        foreach (['/', '/login'] as $path) {
            $this->get($path)->assertOk()->assertSee('SIMULASI VISUAL')->assertSee('disabled', false);
        }

        $this->get('/up')->assertOk()->assertDontSee('SIMULASI VISUAL');
        $this->get('/does-not-exist')->assertNotFound();
    }

    public function test_disabled_takeover_restores_original_redirect(): void
    {
        config(['takeover.enabled' => false]);

        $this->get('/')->assertRedirect('/login');
    }
}
