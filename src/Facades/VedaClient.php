<?php

namespace Veda\LaravelClient\Facades;

use Illuminate\Support\Facades\Facade;
use Veda\Client\Client;
use Veda\LaravelClient\Host\HostManager;

/**
 * @method static HostManager host()
 * @method static Client client()
 *
 * @see \Veda\LaravelClient\VedaClientManager
 */
class VedaClient extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'veda-client';
    }
}
