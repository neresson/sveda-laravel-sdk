<?php

namespace Sveda\LaravelClient\Mcp;

use Laravel\Mcp\Server;
use Sveda\LaravelClient\Host\HostManager;

class HostMcpServer extends Server
{
    public int $maxPaginationLength = 250;

    public int $defaultPaginationLength = 250;

    protected array $tools = [];

    protected array $resources = [];

    protected array $prompts = [];

    protected function boot(): void
    {
        $name = trim((string) config('sveda-client.mcp.server_name', ''));
        $version = trim((string) config('sveda-client.mcp.server_version', ''));
        $instructions = trim((string) config('sveda-client.mcp.instructions', ''));

        $this->name = $name !== '' ? $name : 'Host Application';
        $this->version = $version !== '' ? $version : '0.1.0';
        if ($instructions !== '') {
            $this->instructions = $instructions;
        }

        $host = app(HostManager::class);
        $this->tools = array_map(
            fn ($tool) => new HostMcpTool($tool),
            $host->resolveTools(),
        );
    }
}
