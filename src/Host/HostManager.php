<?php

namespace Sveda\LaravelClient\Host;

use Illuminate\Contracts\Auth\Authenticatable;
use Sveda\Client\Client;
use Sveda\Client\Exceptions\AuthenticationException;
use Sveda\Client\Exceptions\ErrorException;
use Sveda\Client\Exceptions\TransporterException;
use Sveda\Client\Factory;
use Sveda\LaravelClient\Contracts\HostTool;

class HostManager
{
    /** @var callable|null */
    protected $authorizeUsing;

    /** @var callable|null */
    protected $afterAuthenticateUsing;

    /** @var callable|null */
    protected $resolveToolsUsing;

    /** @var callable|null */
    protected $visitorIdUsing;

    /** @var callable|null */
    protected $mintTokenUsing;

    public function authorizeUsing(callable $callback): void
    {
        $this->authorizeUsing = $callback;
    }

    public function afterAuthenticateUsing(callable $callback): void
    {
        $this->afterAuthenticateUsing = $callback;
    }

    /**
     * @param  callable(): list<HostTool>  $callback
     */
    public function resolveToolsUsing(callable $callback): void
    {
        $this->resolveToolsUsing = $callback;
    }

    public function visitorIdUsing(callable $callback): void
    {
        $this->visitorIdUsing = $callback;
    }

    public function mintTokenUsing(callable $callback): void
    {
        $this->mintTokenUsing = $callback;
    }

    public function authorize(Authenticatable $user): bool
    {
        if ($this->authorizeUsing === null) {
            return true;
        }

        return (bool) ($this->authorizeUsing)($user);
    }

    public function afterAuthenticate(Authenticatable $user): void
    {
        if ($this->afterAuthenticateUsing !== null) {
            ($this->afterAuthenticateUsing)($user);
        }
    }

    /**
     * @return list<HostTool>
     */
    public function resolveTools(): array
    {
        if ($this->resolveToolsUsing === null) {
            return [];
        }

        $tools = ($this->resolveToolsUsing)();

        return array_values(array_filter($tools, fn ($tool): bool => $tool instanceof HostTool));
    }

    public function visitorId(Authenticatable $user): string
    {
        if ($this->visitorIdUsing !== null) {
            return (string) ($this->visitorIdUsing)($user);
        }

        $prefix = trim((string) config('sveda-client.session.visitor_prefix', 'host'));

        return $prefix.'-'.$user->getAuthIdentifier();
    }

    public function mintMcpToken(Authenticatable $user): string
    {
        if ($this->mintTokenUsing !== null) {
            return (string) ($this->mintTokenUsing)($user);
        }

        return $this->defaultMintMcpToken($user);
    }

    public function mcpUrl(): string
    {
        if (config()->has('lms.ai.sidecar.mcp_url')) {
            $fromLms = trim((string) config('lms.ai.sidecar.mcp_url'));
            if ($fromLms !== '') {
                return rtrim($fromLms, '/');
            }
        }

        $configured = trim((string) config('sveda-client.mcp.url', ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return url((string) config('sveda-client.mcp.path', '/mcp/sveda'));
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl() !== '' && $this->hostApiKey() !== '';
    }

    /**
     * @return array{origin: string, token: string, expires_in: int, appearance: array<string, mixed>|null}
     */
    public function startSession(Authenticatable $user): array
    {
        if (! $this->isConfigured()) {
            abort(404);
        }

        $ttl = $this->tokenTtlSeconds();
        $mcpToken = $this->mintMcpToken($user);
        $visitorId = $this->visitorId($user);

        try {
            $response = $this->hostClient()->embed()->createToken([
                'visitor_id' => $visitorId,
                'host_mcp_url' => $this->mcpUrl(),
                'host_mcp_token' => $mcpToken,
            ]);
        } catch (AuthenticationException|ErrorException|TransporterException) {
            abort(502);
        }

        if ($response->token === '') {
            abort(502);
        }

        return [
            'origin' => $this->baseUrl(),
            'token' => $response->token,
            'expires_in' => $response->expiresIn,
            'appearance' => $response->appearance,
        ];
    }

    public function client(): Client
    {
        return $this->makeClient();
    }

    public function hostClient(): Client
    {
        return $this->makeClient(host: true);
    }

    protected function makeClient(bool $host = false): Client
    {
        $factory = Factory::factory()
            ->withBaseUri($this->baseUrl())
            ->withTimeout((int) config('sveda-client.timeout', 30))
            ->withConnectTimeout((int) config('sveda-client.connect_timeout', 5));

        if ($host) {
            $factory->withHostApiKey($this->hostApiKey());
        }

        return $factory
            ->withTransporter(new \Sveda\LaravelClient\Transporters\HttpTransporter(
                baseUri: $this->baseUrl(),
                defaultHeaders: $host ? ['Authorization' => 'Bearer '.$this->hostApiKey()] : [],
                timeout: (int) config('sveda-client.timeout', 30),
                connectTimeout: (int) config('sveda-client.connect_timeout', 5),
            ))
            ->make();
    }

    protected function defaultMintMcpToken(Authenticatable $user): string
    {
        $tokenName = (string) config('sveda-client.mcp.token_name', 'sveda-mcp');
        $ability = (string) config('sveda-client.mcp.ability', 'sveda:mcp');
        $ttl = $this->tokenTtlSeconds();

        if (method_exists($user, 'tokens')) {
            $user->tokens()->where('name', $tokenName)->delete();
        }

        if (! method_exists($user, 'createToken')) {
            throw new \RuntimeException('User model must use Laravel Sanctum HasApiTokens trait.');
        }

        return $user->createToken($tokenName, [$ability], now()->addSeconds($ttl))->plainTextToken;
    }

    protected function baseUrl(): string
    {
        if (config()->has('lms.ai.sidecar.url')) {
            return rtrim((string) config('lms.ai.sidecar.url'), '/');
        }

        return rtrim((string) config('sveda-client.base_url', ''), '/');
    }

    protected function hostApiKey(): string
    {
        if (config()->has('lms.ai.sidecar.host_api_key')) {
            return trim((string) config('lms.ai.sidecar.host_api_key'));
        }

        return trim((string) config('sveda-client.host_api_key', ''));
    }

    protected function tokenTtlSeconds(): int
    {
        if (config()->has('lms.ai.sidecar.token_ttl_seconds')) {
            return max(60, (int) config('lms.ai.sidecar.token_ttl_seconds'));
        }

        return max(60, (int) config('sveda-client.mcp.token_ttl_seconds', 3600));
    }
}
