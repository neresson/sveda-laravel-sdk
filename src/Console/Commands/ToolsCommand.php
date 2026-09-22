<?php

namespace Sveda\LaravelClient\Console\Commands;

use Illuminate\Console\Command;
use Sveda\LaravelClient\Host\HostManager;

class ToolsCommand extends Command
{
    protected $signature = 'sveda:tools {--user= : Authenticated user id or email} {--table : Render a human-readable table instead of JSON}';

    protected $description = 'List Sveda host tools registered in this application';

    public function handle(HostManager $host): int
    {
        $user = $this->resolveUser();

        if ($user === false) {
            return self::FAILURE;
        }

        $manifest = $host->describe($user);

        if ($this->option('table')) {
            $rows = collect($manifest['tools'])->map(function (array $tool): array {
                $meta = $tool['_meta'] ?? [];

                return [
                    (string) ($tool['name'] ?? ''),
                    (string) ($meta['mode'] ?? ''),
                    (string) ($meta['domain'] ?? ''),
                    (string) ($meta['confirmation'] ?? ''),
                ];
            })->all();

            $this->table(['name', 'mode', 'domain', 'confirmation'], $rows);

            return self::SUCCESS;
        }

        $this->line(json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|null|false
     */
    protected function resolveUser()
    {
        $identifier = trim((string) $this->option('user'));
        if ($identifier === '') {
            return null;
        }

        $modelClass = (string) config('auth.providers.users.model');
        if ($modelClass === '' || ! class_exists($modelClass)) {
            $this->error('No auth user model is configured.');

            return false;
        }

        /** @var \Illuminate\Contracts\Auth\Authenticatable|null $user */
        $user = $modelClass::query()->where('email', $identifier)->first();
        if ($user === null) {
            $user = $modelClass::query()->find($identifier);
        }

        if ($user === null) {
            $this->error('User not found.');

            return false;
        }

        return $user;
    }
}
