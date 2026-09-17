<?php

namespace Sveda\LaravelClient\Facades;

use Illuminate\Support\Facades\Facade;
use Sveda\Client\Client;
use Sveda\LaravelClient\Host\HostManager;

/**
 * @method static HostManager host()
 * @method static Client client()
 *
 * @see \Sveda\LaravelClient\SvedaClientManager
 */
class SvedaClient extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sveda-client';
    }
}
