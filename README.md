# veda-ai/laravel-client

Laravel SDK for integrating your application with [Veda AI](https://github.com/neresson/veda) without writing your own MCP server.

## Install

```bash
composer require veda-ai/laravel-client
```

`veda-ai/client` is pulled in transitively.

## Host integration

```php
use Veda\LaravelClient\Facades\VedaClient;

VedaClient::host()->resolveToolsUsing(fn () => [
    new SearchOrdersTool,
]);

VedaClient::host()->authorizeUsing(function ($user) {
    return $user->is_active && $user->can('use-ai');
});

$session = VedaClient::host()->startSession($user);
```

The package automatically registers `/mcp/veda` (configurable) and mints Sanctum tokens for the sidecar.

## License

MIT
