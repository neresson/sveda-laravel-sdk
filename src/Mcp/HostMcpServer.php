<?php

namespace Sveda\LaravelClient\Mcp;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Sveda\LaravelClient\Host\HostManager;

#[Name('Host Application')]
#[Version('0.1.0')]
#[Instructions('Host application tools for the authenticated user. Follow each tool schema. User permissions already filter the catalog.')]
class HostMcpServer extends Server
{
    public int $maxPaginationLength = 250;

    public int $defaultPaginationLength = 250;

    protected array $tools = [];

    protected array $resources = [];

    protected array $prompts = [];

    protected function boot(): void
    {
        $host = app(HostManager::class);
        $this->tools = array_map(
            fn ($tool) => new HostMcpTool($tool),
            $host->resolveTools(),
        );
    }
}
