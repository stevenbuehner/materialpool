<?php

namespace Tests\Unit\Services\ContextSearch\Ollama;

use App\Services\ContextSearch\Ollama\OllamaModelDigest;
use App\Services\ContextSearch\Ollama\OllamaProfileMismatchException;
use App\Services\ContextSearch\Ollama\OllamaServer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class OllamaModelDigestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_reads_the_selected_model_digest_with_the_server_api_key(): void
    {
        $digest = str_repeat('a', 64);
        Http::fake(['ollama.test/api/tags' => Http::response(['models' => [
            ['name' => 'other:latest', 'digest' => str_repeat('b', 64)],
            ['model' => 'embeddinggemma:test', 'digest' => $digest],
        ]])]);

        $this->assertSame($digest, (new OllamaModelDigest())->read($this->server(), 'embeddinggemma:test'));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://ollama.test/api/tags'
            && $request->hasHeader('Authorization', 'Bearer example-key'));
    }

    public function test_rejects_a_missing_model(): void
    {
        Http::fake(['ollama.test/api/tags' => Http::response(['models' => []])]);

        $this->expectException(OllamaProfileMismatchException::class);
        $this->expectExceptionMessage('nicht bereit');
        (new OllamaModelDigest())->read($this->server(), 'embeddinggemma:test');
    }

    public function test_rejects_an_invalid_digest(): void
    {
        Http::fake(['ollama.test/api/tags' => Http::response(['models' => [
            ['name' => 'embeddinggemma:test', 'digest' => 'invalid'],
        ]])]);

        $this->expectException(OllamaProfileMismatchException::class);
        $this->expectExceptionMessage('gültigen SHA-256-Digest');
        (new OllamaModelDigest())->read($this->server(), 'embeddinggemma:test');
    }

    public function test_rejects_an_unreadable_model_list(): void
    {
        Http::fake(['ollama.test/api/tags' => Http::response(['error' => 'unauthorized'], 401)]);

        $this->expectException(OllamaProfileMismatchException::class);
        $this->expectExceptionMessage('konnte nicht gelesen werden');
        (new OllamaModelDigest())->read($this->server(), 'embeddinggemma:test');
    }

    private function server(): OllamaServer
    {
        return new OllamaServer('first', 'http://ollama.test', 'example-key', 1);
    }
}
