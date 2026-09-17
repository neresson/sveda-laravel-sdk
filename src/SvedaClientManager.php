<?php

namespace Sveda\LaravelClient;

use Sveda\Client\Client;
use Sveda\LaravelClient\Host\HostManager;

class SvedaClientManager
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
