<?php

namespace Sveda\LaravelClient\Contracts;

use Illuminate\Contracts\JsonSchema\JsonSchema;

interface HostTool
{
    public const MODE_READ = 'read';

    public const MODE_WRITE = 'write';

    public const MODE_DELETE = 'delete';

    public function name(): string;

    public function description(): string;

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array;

    public function mode(): string;

    public function domain(): string;

    public function handle(array $arguments): mixed;
}
