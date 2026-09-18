<?php

namespace Sveda\LaravelClient\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Sveda\LaravelClient\Tests\TestCase;

final class SessionRouteDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('sveda-client.session.enabled', false);
    }

    public function test_session_route_is_not_registered_when_disabled(): void
    {
        $this->assertFalse(Route::has('sveda.session'));
    }
}
