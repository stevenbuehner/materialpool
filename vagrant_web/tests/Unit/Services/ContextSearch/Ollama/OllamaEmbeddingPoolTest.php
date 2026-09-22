<?php

namespace Tests\Unit\Services\ContextSearch\Ollama;

use App\Services\ContextSearch\EmbeddingProfile;
use App\Services\ContextSearch\Ollama\OllamaEmbeddingPool;
use App\Services\ContextSearch\Ollama\OllamaProfileMismatchException;
use App\Services\ContextSearch\Ollama\OllamaServer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class OllamaEmbeddingPoolTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('array')->flush();
        Http::preventStrayRequests();
    }

    public function test_distributes_independent_requests_across_healthy_servers(): void
    {
        Http::fake([
            'first.test/api/tags' => $this->modelsResponse(),
            'second.test/api/tags' => $this->modelsResponse(),
            'first.test/api/embed' => $this->embeddingResponse(),
            'second.test/api/embed' => $this->embeddingResponse(),
        ]);

        $servers = [];

        foreach (range(1, 24) as $number) {
            $servers[] = $this->pool()->embed(['A small input.'], "resource-{$number}")->server;
        }

        $this->assertSame(['first', 'second'], array_values(array_unique($servers)));
    }

    public function test_verifies_every_server_before_an_index_generation(): void
    {
        Http::fake([
            'first.test/api/tags' => $this->modelsResponse(),
            'second.test/api/tags' => $this->modelsResponse(),
            'first.test/api/embed' => $this->embeddingResponse(),
            'second.test/api/embed' => $this->embeddingResponse(),
        ]);

        $servers = $this->pool()->verifyProfile();

        $this->assertSame(['first', 'second'], array_column($servers, 'name'));
        $this->assertSame([2, 2], array_column($servers, 'dimensions'));
    }

    public function test_fails_over_after_a_transient_server_error(): void
    {
        $routingKey = $this->routingKeyFor('first');

        Http::fake([
            'first.test/api/tags' => Http::response(['error' => 'busy'], 503),
            'second.test/api/tags' => $this->modelsResponse(),
            'second.test/api/embed' => $this->embeddingResponse(),
        ]);

        $response = $this->pool()->embed(['A small input.'], $routingKey);

        $this->assertSame('second', $response->server);
    }

    public function test_model_digest_mismatch_stops_without_failing_over(): void
    {
        Http::fake([
            'first.test/api/tags' => Http::response(['models' => [[
                'name' => 'embeddinggemma:300m-qat-q8_0',
                'digest' => str_repeat('b', 64),
            ]]]),
            'second.test/*' => $this->modelsResponse(),
        ]);

        $this->expectException(OllamaProfileMismatchException::class);
        $this->expectExceptionMessage('different digest');

        try {
            $this->pool()->embed(['A small input.'], $this->routingKeyFor('first'));
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function test_open_circuit_skips_a_server_until_its_cooldown_expires(): void
    {
        $routingKey = $this->routingKeyFor('first');

        Http::fake([
            'first.test/api/tags' => Http::response(['error' => 'busy'], 503),
            'second.test/api/tags' => $this->modelsResponse(),
            'second.test/api/embed' => $this->embeddingResponse(),
        ]);

        $pool = $this->pool(failureThreshold: 1);
        $pool->embed(['First input.'], $routingKey);
        $response = $pool->embed(['Second input.'], $routingKey);

        $this->assertSame('second', $response->server);
        $firstTagsRequests = Http::recorded(fn ($request): bool => $request->url() === 'http://first.test/api/tags');
        $this->assertCount(1, $firstTagsRequests);
    }

    private function pool(int $failureThreshold = 2): OllamaEmbeddingPool
    {
        return new OllamaEmbeddingPool(
            servers: [
                new OllamaServer('first', 'http://first.test', null, 1),
                new OllamaServer('second', 'http://second.test', null, 1),
            ],
            profile: new EmbeddingProfile('embeddinggemma:300m-qat-q8_0', str_repeat('a', 64), 2, []),
            cache: Cache::store('array'),
            connectTimeout: 2,
            timeout: 10,
            failureThreshold: $failureThreshold,
            circuitCooldown: 60,
        );
    }

    private function routingKeyFor(string $server): string
    {
        foreach (range(1, 100) as $number) {
            $key = "routing-key-{$number}";
            $offset = hexdec(substr(hash('sha256', $key), 0, 8)) % 2;

            if (($offset === 0 ? 'first' : 'second') === $server) {
                return $key;
            }
        }

        $this->fail("No routing key found for {$server}.");
    }

    private function modelsResponse(): mixed
    {
        return Http::response(['models' => [[
            'name' => 'embeddinggemma:300m-qat-q8_0',
            'digest' => str_repeat('a', 64),
        ]]]);
    }

    private function embeddingResponse(): mixed
    {
        return Http::response([
            'model' => 'embeddinggemma:300m-qat-q8_0',
            'embeddings' => [[0.1, 0.2]],
        ]);
    }
}
