<?php

namespace Sveda\LaravelClient\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Sveda\LaravelClient\Tests\TestCase;

final class SessionRouteTest extends TestCase
{
    public function test_package_registers_named_session_route(): void
    {
        $this->assertTrue(Route::has('sveda.session'));
    }

    public function test_session_route_requires_authentication(): void
    {
        $this->postJson('/sveda/session')->assertUnauthorized();
    }

    public function test_session_route_mints_session_for_authenticated_user(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:8787/sveda/embed/token' => Http::response([
                'token' => 'sveda_embed_test.token',
                'visitor_id' => 'host-1',
                'expires_in' => 3600,
            ]),
        ]);

        $user = $this->createUser();

        $this->actingAs($user)
            ->postJson('/sveda/session')
            ->assertOk()
            ->assertJsonPath('token', 'sveda_embed_test.token');
    }
}
