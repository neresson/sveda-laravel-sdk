<?php

namespace Sveda\LaravelClient\Host;

use Composer\InstalledVersions;
use Illuminate\Contracts\Auth\Authenticatable;
use Sveda\LaravelClient\Contracts\HostTool;
use Sveda\LaravelClient\Mcp\HostMcpTool;

final class HostManifest
{
    public const SCHEMA = 'sveda.host/v1';

    /**
     * @return array<string, mixed>
     */
    public static function toolToMcpArray(HostTool $tool): array
    {
        /** @var array<string, mixed> $array */
        $array = (new HostMcpTool($tool))->toArray();

        $payload = [
            'name' => (string) ($array['name'] ?? $tool->name()),
            'title' => (string) ($array['title'] ?? $tool->name()),
            'description' => (string) ($array['description'] ?? $tool->description()),
            'inputSchema' => $array['inputSchema'] ?? ['type' => 'object', 'properties' => (object) []],
            '_meta' => $array['_meta'] ?? [
                'domain' => $tool->domain(),
                'mode' => $tool->mode(),
            ],
        ];

        if (array_key_exists('annotations', $array)) {
            $payload['annotations'] = $array['annotations'];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function build(HostManager $host, ?Authenticatable $user = null): array
    {
        $authenticated = $user !== null;

        return [
            'schema' => self::SCHEMA,
            'sdk' => [
                'language' => 'laravel',
                'version' => self::sdkVersion(),
            ],
            'subject' => [
                'authenticated' => $authenticated,
                'policy' => $authenticated ? $host->policyFor($user) : null,
            ],
            'hooks' => $host->registeredHooks(),
            'tools' => array_map(
                fn (HostTool $tool): array => self::toolToMcpArray($tool),
                $host->resolveTools($user),
            ),
        ];
    }

    protected static function sdkVersion(): string
    {
        if (class_exists(InstalledVersions::class)) {
            return InstalledVersions::getPrettyVersion('sveda-ai/laravel-sdk')
                ?? InstalledVersions::getPrettyVersion('sveda/laravel-client')
                ?? 'unknown';
        }

        return 'unknown';
    }
}
