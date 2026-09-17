<?php

namespace Sveda\LaravelClient\Tests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Sveda\LaravelClient\Contracts\HostTool;

final class EchoHostTool implements HostTool
{
    public function name(): string
    {
        return 'echo_message';
    }

    public function description(): string
    {
        return 'Echo a message back.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()->description('Message to echo')->required(),
        ];
    }

    public function mode(): string
    {
        return 'read';
    }

    public function domain(): string
    {
        return 'demo';
    }

    public function handle(array $arguments): mixed
    {
        return [
            'success' => true,
            'data' => [
                'message' => (string) ($arguments['message'] ?? ''),
            ],
        ];
    }
}
