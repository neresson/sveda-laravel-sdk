<?php

namespace Sveda\LaravelClient\Tests\Feature;

use Sveda\LaravelClient\Facades\SvedaClient;
use Sveda\LaravelClient\Tests\Fixtures\EchoHostTool;
use Sveda\LaravelClient\Tests\TestCase;

final class HostMcpServerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SvedaClient::host()->resolveToolsUsing(fn () => [new EchoHostTool]);
    }

    public function test_unauthenticated_mcp_request_is_rejected(): void
    {
        $this->mcpJson(null, 'tools/list')->assertUnauthorized();
    }

    public function test_initialize_reports_configured_name_and_instructions(): void
    {
        config()->set('sveda-client.mcp.server_name', 'Playground Feed');
        config()->set('sveda-client.mcp.instructions', 'Feed tools for the current user.');

        $user = $this->createUser();
        $token = $user->createToken('sveda-mcp', ['sveda:mcp'], now()->addHour())->plainTextToken;

        $response = $this->mcpJson($token, 'initialize', [
            'protocolVersion' => '2025-11-25',
            'capabilities' => [],
            'clientInfo' => ['name' => 'sveda-test', 'version' => '0.1.0'],
        ]);

        $response->assertOk();
        $this->assertSame('Playground Feed', $response->json('result.serverInfo.name'));
        $this->assertSame('Feed tools for the current user.', $response->json('result.instructions'));
    }

    public function test_authenticated_user_can_list_and_call_tools(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('sveda-mcp', ['sveda:mcp'], now()->addHour())->plainTextToken;

        $list = $this->mcpJson($token, 'tools/list', ['per_page' => 250]);
        $list->assertOk();

        $names = collect($list->json('result.tools'))->pluck('name')->all();
        $this->assertContains('echo_message', $names);

        $tool = collect($list->json('result.tools'))->firstWhere('name', 'echo_message');
        $this->assertSame('demo', $tool['_meta']['domain'] ?? null);
        $this->assertSame('read', $tool['_meta']['mode'] ?? null);

        $call = $this->mcpJson($token, 'tools/call', [
            'name' => 'echo_message',
            'arguments' => ['message' => 'hello'],
        ], 2);
        $call->assertOk();
        $this->assertFalse((bool) $call->json('result.isError'));

        $text = (string) data_get($call->json('result.content'), '0.text');
        $decoded = json_decode($text, true);
        $this->assertSame('hello', $decoded['data']['message'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function mcpJson(?string $token, string $method, array $params = [], int $id = 1)
    {
        $headers = [
            'Accept' => 'application/json, text/event-stream',
        ];

        if (is_string($token) && $token !== '') {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return $this->postJson('/mcp/sveda', [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ], $headers);
    }
}
