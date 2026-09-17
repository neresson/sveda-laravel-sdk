# sveda-laravel-sdk

Laravel SDK for integrating your application with [Sveda AI](https://github.com/neresson/sveda) without writing your own MCP server.

Packagist: `sveda-ai/laravel-sdk`

## Install

```bash
composer require sveda-ai/laravel-sdk
```

`sveda-ai/php-sdk` is pulled in transitively.

## Host integration

```php
use Sveda\LaravelClient\Facades\SvedaClient;

SvedaClient::host()->resolveToolsUsing(fn () => [
    new SearchOrdersTool,
]);

SvedaClient::host()->authorizeUsing(function ($user) {
    return $user->is_active && $user->can('use-ai');
});

$session = SvedaClient::host()->startSession($user);
```

The package automatically registers `/mcp/sveda` (configurable) and mints Sanctum tokens for the sidecar.

## License

MIT
