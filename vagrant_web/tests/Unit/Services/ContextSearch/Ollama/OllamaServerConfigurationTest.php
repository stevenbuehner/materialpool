<?php

namespace Tests\Unit\Services\ContextSearch\Ollama;

use App\Services\ContextSearch\Ollama\OllamaServerConfiguration;
use InvalidArgumentException;
use Tests\TestCase;

final class OllamaServerConfigurationTest extends TestCase
{
    public function test_parses_individual_server_limits_and_api_keys(): void
    {
        $servers = OllamaServerConfiguration::parse(
            'first=http://first.test:11434|2,second=https://second.test|1',
            'first=first-secret,second=second-secret',
        );

        $this->assertCount(2, $servers);
        $this->assertSame('first', $servers[0]->name);
        $this->assertSame(2, $servers[0]->maxConcurrency);
        $this->assertSame('second-secret', $servers[1]->apiKey);
    }

    public function test_rejects_api_keys_for_unknown_servers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        OllamaServerConfiguration::parse('first=http://first.test|1', 'second=secret');
    }
}
