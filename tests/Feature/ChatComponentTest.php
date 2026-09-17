<?php

namespace Sveda\LaravelClient\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Sveda\LaravelClient\Tests\TestCase;

class ChatComponentTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('sveda-client.base_url', 'http://127.0.0.1:8787');
    }

    public function test_chat_component_renders_sidecar_script_and_element(): void
    {
        $html = Blade::render('<x-sveda::chat session="/sveda/session" />');

        $this->assertStringContainsString('http://127.0.0.1:8787/build/sveda/embed.css', $html);
        $this->assertStringContainsString('http://127.0.0.1:8787/sveda/sveda-chat.js', $html);
        $this->assertStringContainsString('<sveda-chat', $html);
        $this->assertStringContainsString('session="/sveda/session"', $html);
    }
}
