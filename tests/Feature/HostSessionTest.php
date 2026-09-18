<?php

namespace Sveda\LaravelClient\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Sveda\LaravelClient\Facades\SvedaClient;
use Sveda\LaravelClient\Tests\Fixtures\EchoHostTool;
use Sveda\LaravelClient\Tests\TestCase;

final class HostSessionTest extends TestCase
{
    public function test_it_mints_embed_token_from_sidecar(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:8787/sveda/embed/token' => Http::response([
                'token' => 'sveda_embed_test.token',
                'visitor_id' => 'host-1',
                'expires_in' => 3600,
            ]),
        ]);

        SvedaClient::host()->resolveToolsUsing(fn () => [new EchoHostTool]);

        $user = $this->createUser();

        $response = $this->actingAs($user)->postJson('/sveda/session');

        $response
            ->assertOk()
            ->assertJsonPath('origin', 'http://127.0.0.1:8787')
            ->assertJsonPath('token', 'sveda_embed_test.token')
            ->assertJsonPath('expires_in', 3600);

        Http::assertSent(function (Request $request) use ($user): bool {
            return $request->url() === 'http://127.0.0.1:8787/sveda/embed/token'
                && $request->hasHeader('Authorization', 'Bearer host-secret')
                && $request['visitor_id'] === 'host-'.$user->id
                && is_string($request['host_mcp_url'])
                && is_string($request['host_mcp_token'])
                && $request['host_mcp_token'] !== '';
        });

        $this->assertTrue($user->tokens()->where('name', 'sveda-mcp')->exists());
    }
}
