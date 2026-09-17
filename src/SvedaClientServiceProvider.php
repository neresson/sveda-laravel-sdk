<?php

namespace Sveda\LaravelClient;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Sveda\LaravelClient\View\Components\Chat;
use Sveda\LaravelClient\Host\HostManager;
use Sveda\LaravelClient\Http\Middleware\AuthenticateHostMcp;
use Sveda\LaravelClient\Mcp\HostMcpServer;

class SvedaClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sveda-client.php', 'sveda-client');

        $this->app->singleton(HostManager::class);
        $this->app->singleton(SvedaClientManager::class, fn ($app) => new SvedaClientManager(
            $app->make(HostManager::class),
        ));
        $this->app->alias(SvedaClientManager::class, 'sveda-client');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sveda-client');
        Blade::component(Chat::class, 'sveda::chat');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sveda-client.php' => config_path('sveda-client.php'),
            ], 'sveda-client-config');
        }

        $this->registerMcpServer();
    }

    protected function registerMcpServer(): void
    {
        if (! class_exists(Mcp::class)) {
            return;
        }

        $path = (string) config('sveda-client.mcp.path', '/mcp/sveda');
        $throttle = (string) config('sveda-client.mcp.throttle', '120,1');

        Mcp::web($path, HostMcpServer::class)
            ->middleware(['throttle:'.$throttle, AuthenticateHostMcp::class]);
    }
}
