<?php

namespace Sveda\LaravelClient\Mcp;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Sveda\LaravelClient\Contracts\HostTool;

class HostMcpTool extends Tool
{
    public function __construct(protected HostTool $hostTool)
    {
        $this->name = $hostTool->name();
        $this->title = $hostTool->name();
        $this->description = $hostTool->description();
        $this->meta = [
            'domain' => $hostTool->domain(),
            'mode' => $hostTool->mode(),
        ];
    }

    public function annotations(): array
    {
        return match ($this->hostTool->mode()) {
            HostTool::MODE_READ => ['readOnlyHint' => true],
            HostTool::MODE_DELETE => [
                'readOnlyHint' => false,
                'destructiveHint' => true,
            ],
            default => [
                'readOnlyHint' => false,
                'destructiveHint' => false,
            ],
        };
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->hostTool->schema($schema);
    }

    public function handle(Request $request): Response
    {
        $result = $this->hostTool->handle($request->all());

        if (is_array($result)) {
            return Response::text(json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        return Response::text((string) $result);
    }
}
