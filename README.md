# sveda-laravel-sdk

Laravel SDK for integrating your application with [Sveda](https://sveda.dev) without writing your own MCP server.

Docs: [sveda.dev/docs/hosts/laravel](https://sveda.dev/docs/hosts/laravel)

Packagist: `sveda-ai/laravel-sdk`

## Install

```bash
composer require sveda-ai/laravel-sdk
```

`sveda-ai/php-sdk` is pulled in transitively. The service provider is auto-discovered.

```bash
php artisan vendor:publish --tag=sveda-client-config
```

Minimal `.env`:

```
SVEDA_CLIENT_BASE_URL=http://127.0.0.1:8787
SVEDA_CLIENT_HOST_API_KEY=the-sidecar-host-key
```

## How it fits together

```
Browser (chat UI)
   │ 1. POST /sveda/session (auth cookie)
   ▼
Your Laravel app ── 2. mints Sanctum token, asks sidecar for embed token ──▶ Sveda sidecar
   │ 3. { token, origin }                                                 (stores host_mcp_url/token
   ▼                                                                       for this visitor)
Browser opens chat iframe with embed token
   │
   ▼
Sveda sidecar ── 4. discovers & calls your tools over MCP ──▶ POST /mcp/sveda (Bearer Sanctum token)
```

The package registers both endpoints for you:

- `POST /sveda/session` (named `sveda.session`, middleware `web` + `auth`) — mints an embed session for the authenticated user.
- `POST /mcp/sveda` — the MCP server the sidecar calls to list and execute your tools.

## Define a tool

```php
<?php

namespace App\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Sveda\LaravelClient\Contracts\HostTool;

class CreatePostTool implements HostTool
{
    public function name(): string
    {
        return 'create_post';
    }

    public function description(): string
    {
        // This text is the prompt the model sees — say when to call the tool.
        return 'Create a post for the current user. Call this whenever the user asks to create or publish a post. Invent a short title and body if the user did not specify them.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Post title.')->max(120),
            'body' => $schema->string()->description('Post body.')->max(5000),
        ];
    }

    public function mode(): string
    {
        // HostTool::MODE_READ | MODE_WRITE | MODE_DELETE
        return self::MODE_WRITE;
    }

    public function domain(): string
    {
        return 'posts';
    }

    public function handle(array $arguments): mixed
    {
        $post = Auth::user()->posts()->create([
            'title' => $arguments['title'] ?? 'Untitled',
            'body' => $arguments['body'] ?? '',
        ]);

        return ['id' => $post->id, 'title' => $post->title];
    }
}
```

`mode()` is metadata: `read` is annotated read-only, `delete` destructive, everything else a non-destructive write. `domain()` groups tools in the UI.

## Register tools and access rules

In a service provider:

```php
use Sveda\LaravelClient\Facades\SvedaClient;

public function boot(): void
{
    SvedaClient::host()->resolveToolsUsing(fn () => [
        new \App\Tools\SearchPostsTool,
        new \App\Tools\CreatePostTool,
        new \App\Tools\UpdatePostTool,
    ]);

    // Optional: who may use the AI at all (default: any authenticated user).
    SvedaClient::host()->authorizeUsing(fn ($user) => $user->is_active);

    // Optional: customize the visitor id sent to the sidecar (default: "host-{id}").
    SvedaClient::host()->visitorIdUsing(fn ($user) => "user-{$user->id}");
}
```

## Render the chat

```blade
<x-sveda::chat />
```

The component posts to `route('sveda.session')` and renders the iframe. Override the endpoints when needed:

```blade
<x-sveda::chat session="/custom/session" origin="https://sveda.example.com" />
```

## Configuration

| Key | Default | Purpose |
| --- | --- | --- |
| `base_url` | — | Sidecar URL (`SVEDA_CLIENT_BASE_URL`) |
| `host_api_key` | — | Host key the sidecar expects (`SVEDA_CLIENT_HOST_API_KEY`) |
| `mcp.path` | `/mcp/sveda` | MCP endpoint path |
| `mcp.server_name` / `mcp.server_version` | `Host Application` / `0.1.0` | Reported in MCP `initialize` |
| `mcp.instructions` | generic text | Server instructions sent to the model with your tool list |
| `mcp.ability` | `sveda:mcp` | Sanctum ability required on MCP tokens |
| `mcp.token_ttl_seconds` | `3600` | MCP token lifetime |
| `mcp.throttle` | `120,1` | Rate limit for the MCP endpoint |
| `session.enabled` | `true` | Set `false` to register your own session route |
| `session.path` / `session.name` | `/sveda/session` / `sveda.session` | Session endpoint |
| `session.middleware` | `['web', 'auth']` | Use `['auth:sanctum']` for token-based APIs |
| `session.visitor_prefix` | `host` | Default visitor id prefix |

## Troubleshooting

- **Chat renders but the model never calls tools.** Check `php artisan route:list` for `mcp/sveda`; make sure `resolveToolsUsing` runs in a booted provider; describe *when* to call each tool in `description()` — the description is the model's prompt.
- **401 on `/mcp/sveda`.** The sidecar sends the Sanctum token minted at session start; tokens expire after `mcp.token_ttl_seconds`. New chat session = new token.
- **404 on session start.** `SVEDA_CLIENT_BASE_URL` / `SVEDA_CLIENT_HOST_API_KEY` are not configured, or the sidecar is down.
- **`route('sveda.session')` not defined.** You set `session.enabled=false`; register your own route pointing to `StartSidecarSessionController`.

## License

GNU Affero General Public License v3.0. See [LICENSE](LICENSE).
