<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\AuthenticatedTestCase as TestCase;

class ClearCacheTest extends TestCase
{
    public function test_post_clears_cache_and_returns_to_current_page(): void
    {
        Artisan::shouldReceive('call')->once()->with('optimize:clear')->andReturn(0);
        $this->from('/eventos')->post('/cache/clear')->assertRedirect('/eventos')->assertSessionHas('success');
    }

    public function test_failures_are_reported_without_success_message(): void
    {
        Artisan::shouldReceive('call')->once()->with('optimize:clear')->andReturn(1);
        $this->from('/eventos')->post('/cache/clear')->assertRedirect('/eventos')->assertSessionHas('error')->assertSessionMissing('success');
    }

    public function test_cache_cannot_be_cleared_with_get_request(): void
    {
        Artisan::shouldReceive('call')->never();
        $this->get('/cache/clear')->assertStatus(405);
    }
}
