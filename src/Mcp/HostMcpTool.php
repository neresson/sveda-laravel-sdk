<?php

namespace Sveda\LaravelClient\Mcp;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Sveda\LaravelClient\Contracts\HostTool;
use Sveda\LaravelClient\Host\HostManager;

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
        if (method_exists($hostTool, 'confirmation') && $hostTool->confirmation() === 'required') {
            $this->meta['confirmation'] = 'required';
        }
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
        $user = $request->user();
        if ($user !== null) {
            $allowed = array_map(
                fn (HostTool $tool): string => $tool->name(),
                app(HostManager::class)->resolveTools($user),
            );
            if (! in_array($this->hostTool->name(), $allowed, true)) {
                return Response::error('Tool is not allowed for this user.');
            }
        }

        $result = $this->hostTool->handle($request->all());

        if (is_array($result)) {
            return Response::text(json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        return Response::text((string) $result);
    }
}
