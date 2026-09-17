<?php

namespace Sveda\LaravelClient\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Chat extends Component
{
    public function __construct(
        public ?string $session = null,
        public ?string $origin = null,
        public ?string $token = null,
    ) {}

    public function render(): View
    {
        return view('sveda-client::components.chat');
    }

    public function sidecarBaseUrl(): string
    {
        if ($this->origin !== null && trim($this->origin) !== '') {
            return rtrim(trim($this->origin), '/');
        }

        if (config()->has('lms.ai.sidecar.url')) {
            return rtrim((string) config('lms.ai.sidecar.url'), '/');
        }

        return rtrim((string) config('sveda-client.base_url', ''), '/');
    }

    public function sessionUrl(): string
    {
        if ($this->session !== null && trim($this->session) !== '') {
            return trim($this->session);
        }

        return route('sveda.session');
    }
}
