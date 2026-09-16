<?php

namespace Veda\LaravelClient\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Veda\LaravelClient\Facades\VedaClient;
use Veda\LaravelClient\Http\Controllers\StartSidecarSessionController;
use Veda\LaravelClient\Tests\Fixtures\EchoHostTool;
use Veda\LaravelClient\Tests\TestCase;

final class HostSessionTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->post('/veda/session', StartSidecarSessionController::class)
            ->middleware('auth:sanctum');
    }

    public function test_it_mints_embed_token_from_sidecar(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:8787/veda/embed/token' => Http::response([
                'token' => 'veda_embed_test.token',
                'visitor_id' => 'host-1',
                'expires_in' => 3600,
            ]),
        ]);

        VedaClient::host()->resolveToolsUsing(fn () => [new EchoHostTool]);

        $user = $this->createUser();
        $token = $user->createToken('web')->plainTextToken;

        $response = $this->withToken($token)->postJson('/veda/session');

        $response
            ->assertOk()
            ->assertJsonPath('origin', 'http://127.0.0.1:8787')
            ->assertJsonPath('token', 'veda_embed_test.token')
            ->assertJsonPath('expires_in', 3600);

        Http::assertSent(function (Request $request) use ($user): bool {
            return $request->url() === 'http://127.0.0.1:8787/veda/embed/token'
                && $request->hasHeader('Authorization', 'Bearer host-secret')
                && $request['visitor_id'] === 'host-'.$user->id
                && is_string($request['host_mcp_url'])
                && is_string($request['host_mcp_token'])
                && $request['host_mcp_token'] !== '';
        });

        $this->assertTrue($user->tokens()->where('name', 'veda-mcp')->exists());
    }
}
