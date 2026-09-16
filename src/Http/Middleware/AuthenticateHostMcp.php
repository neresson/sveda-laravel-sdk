<?php

namespace Veda\LaravelClient\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Veda\LaravelClient\Host\HostManager;

class AuthenticateHostMcp
{
    public const PAGE_CONTEXT_HEADER = 'X-Veda-Page-Context';

    public const CHAT_ID_HEADER = 'X-Veda-Chat-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();
        if (! is_string($plain) || $plain === '') {
            abort(401);
        }

        $accessToken = PersonalAccessToken::findToken($plain);
        if ($accessToken === null) {
            abort(401);
        }

        if ($accessToken->expires_at !== null && $accessToken->expires_at->isPast()) {
            abort(401);
        }

        $ability = (string) config('veda-client.mcp.ability', 'veda:mcp');
        if (! $accessToken->can($ability)) {
            abort(401);
        }

        $user = $accessToken->tokenable;
        if (! $user instanceof Authenticatable) {
            abort(401);
        }

        $host = app(HostManager::class);
        if (! $host->authorize($user)) {
            abort(403);
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();

        if (method_exists($user, 'withAccessToken')) {
            $user->withAccessToken($accessToken);
        }

        Auth::setUser($user);
        $host->afterAuthenticate($user);

        return $next($request);
    }
}
