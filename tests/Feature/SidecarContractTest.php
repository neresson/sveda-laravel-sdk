<?php

namespace Sveda\LaravelClient\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SidecarContractTest extends TestCase
{
    #[Test]
    public function it_locks_sidecar_contract_surface(): void
    {
        $path = dirname(__DIR__, 2).'/contracts/sidecar.v1.json';
        if (! is_file($path)) {
            $path = dirname(__DIR__, 3).'/sveda/packages/protocol/contracts/sidecar.v1.json';
        }
        $this->assertFileExists($path);
        $contract = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($contract);
        $this->assertSame('1.0', $contract['version']);
        $this->assertSame('/sveda', $contract['prefix']);
        $this->assertSame(
            'application/vnd.sveda.stream+json',
            $contract['accept']['svedaStream'],
        );
    }
}
