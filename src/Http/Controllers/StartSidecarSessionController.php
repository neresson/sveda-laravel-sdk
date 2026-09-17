<?php

namespace Sveda\LaravelClient\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sveda\LaravelClient\Host\HostManager;

class StartSidecarSessionController
{
    public function __invoke(Request $request, HostManager $host): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof Authenticatable, 401);

        return response()->json($host->startSession($user));
    }
}
