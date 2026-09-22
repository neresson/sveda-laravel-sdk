<?php

namespace Sveda\LaravelClient\Tests\Feature;

use Sveda\LaravelClient\Contracts\HostTool;
use Sveda\LaravelClient\Facades\SvedaClient;
use Sveda\LaravelClient\Tests\Fixtures\EchoHostTool;
use Sveda\LaravelClient\Tests\TestCase;

final class HostManifestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SvedaClient::host()->resolveToolsUsing(fn () => [new EchoHostTool]);
    }

    public function test_describe_matches_mcp_tools_list_for_anonymous_subject(): void
    {
        $manifest = SvedaClient::host()->describe();
        $this->assertSame('sveda.host/v1', $manifest['schema']);
        $this->assertFalse($manifest['subject']['authenticated']);
        $this->assertNull($manifest['subject']['policy']);
        $this->assertTrue($manifest['hooks']['resolve_tools']);

        $user = $this->createUser();
        $token = $user->createToken('sveda-mcp', ['sveda:mcp'], now()->addHour())->plainTextToken;
        $listed = collect($this->mcpJson($token, 'tools/list', ['per_page' => 250])->assertOk()->json('result.tools'))
            ->keyBy('name');

        foreach ($manifest['tools'] as $tool) {
            $this->assertArrayHasKey($tool['name'], $listed->all());
            $this->assertSame($listed[$tool['name']]['description'], $tool['description']);
            $this->assertSame($listed[$tool['name']]['_meta'], $tool['_meta']);
        }
    }

    public function test_describe_reports_policy_for_authenticated_user(): void
    {
        SvedaClient::host()->policyUsing(fn () => 'agent');

        $user = $this->createUser();
        $manifest = SvedaClient::host()->describe($user);

        $this->assertTrue($manifest['subject']['authenticated']);
        $this->assertSame('agent', $manifest['subject']['policy']);
        $this->assertTrue($manifest['hooks']['policy']);
    }

    public function test_artisan_tools_command_supports_table_output(): void
    {
        $this->artisan('sveda:tools', ['--table' => true])
            ->assertSuccessful()
            ->expectsTable(
                ['name', 'mode', 'domain', 'confirmation'],
                [['echo_message', 'read', 'demo', '']],
            );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function mcpJson(?string $token, string $method, array $params = [], int $id = 1)
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($token !== null) {
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
