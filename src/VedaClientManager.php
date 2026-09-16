<?php

namespace Veda\LaravelClient;

use Veda\Client\Client;
use Veda\LaravelClient\Host\HostManager;

class VedaClientManager
{
    public function __construct(
        protected HostManager $host,
    ) {}

    public function host(): HostManager
    {
        return $this->host;
    }

    public function client(): Client
    {
        return $this->host->client();
    }
}
