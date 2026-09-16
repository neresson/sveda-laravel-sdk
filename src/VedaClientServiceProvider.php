<?php

namespace Veda\LaravelClient;

use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Veda\LaravelClient\Host\HostManager;
use Veda\LaravelClient\Http\Middleware\AuthenticateHostMcp;
use Veda\LaravelClient\Mcp\HostMcpServer;

class VedaClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/veda-client.php', 'veda-client');

        $this->app->singleton(HostManager::class);
        $this->app->singleton(VedaClientManager::class, fn ($app) => new VedaClientManager(
            $app->make(HostManager::class),
        ));
        $this->app->alias(VedaClientManager::class, 'veda-client');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/veda-client.php' => config_path('veda-client.php'),
            ], 'veda-client-config');
        }

        $this->registerMcpServer();
    }

    protected function registerMcpServer(): void
    {
        if (! class_exists(Mcp::class)) {
            return;
        }

        $path = (string) config('veda-client.mcp.path', '/mcp/veda');
        $throttle = (string) config('veda-client.mcp.throttle', '120,1');

        Mcp::web($path, HostMcpServer::class)
            ->middleware(['throttle:'.$throttle, AuthenticateHostMcp::class]);
    }
}
