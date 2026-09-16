# veda-ai/laravel-client

Laravel SDK for integrating your application with [Veda AI](https://github.com/neresson/veda) without writing your own MCP server.

## Install

Until the packages are on Packagist, require them from GitHub. Composer only reads repositories from the **root** project, so both repos must be listed:

```bash
composer config repositories.veda-client vcs https://github.com/neresson/veda-client.git
composer config repositories.veda-laravel-client vcs https://github.com/neresson/veda-laravel-client.git
composer require veda-ai/laravel-client:dev-main
```

Or in `composer.json`:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/neresson/veda-client.git" },
    { "type": "vcs", "url": "https://github.com/neresson/veda-laravel-client.git" }
  ],
  "require": {
    "veda-ai/laravel-client": "dev-main"
  }
}
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
