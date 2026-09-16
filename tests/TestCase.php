<?php

namespace Veda\LaravelClient\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Server\McpServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Veda\LaravelClient\Tests\Fixtures\TestUser;
use Veda\LaravelClient\VedaClientServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SanctumServiceProvider::class,
            McpServiceProvider::class,
            VedaClientServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('auth.providers.users.model', TestUser::class);
        $app['config']->set('veda-client.base_url', 'http://127.0.0.1:8787');
        $app['config']->set('veda-client.host_api_key', 'host-secret');
        $app['config']->set('veda-client.mcp.path', '/mcp/veda');
        $app['config']->set('veda-client.mcp.token_ttl_seconds', 3600);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__.'/../vendor/laravel/sanctum/database/migrations');
    }

    protected function createUser(array $attributes = []): TestUser
    {
        return TestUser::query()->create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_active' => true,
        ], $attributes));
    }
}
