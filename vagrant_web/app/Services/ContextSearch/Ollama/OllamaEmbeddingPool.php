<?php

namespace App\Services\ContextSearch\Ollama;

use App\Services\ContextSearch\EmbeddingProfile;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class OllamaEmbeddingPool
{
    /** @param array<int, OllamaServer> $servers */
    public function __construct(
        private readonly array $servers,
        private readonly EmbeddingProfile $profile,
        private readonly CacheRepository $cache,
        private readonly int $connectTimeout,
        private readonly int $timeout,
        private readonly int $failureThreshold,
        private readonly int $circuitCooldown,
    ) {
        if ($this->servers === []) {
            throw new OllamaEmbeddingPoolException('At least one Ollama server must be configured.');
        }

        if ($this->connectTimeout < 1 || $this->timeout < 1 || $this->failureThreshold < 1 || $this->circuitCooldown < 1) {
            throw new OllamaEmbeddingPoolException('Ollama pool timeouts and limits must be positive.');
        }
    }

    public function profile(): EmbeddingProfile
    {
        return $this->profile;
    }

    /**
     * Performs the profile self-test for every configured server.
     *
     * @return array<int, array{name: string, digest: string, dimensions: int}>
     */
    public function verifyProfile(): array
    {
        $verified = [];

        foreach ($this->servers as $server) {
            $this->assertServerProfile($server);
            $verified[] = [
                'name' => $server->name,
                'digest' => $this->profile->digest,
                'dimensions' => $this->profile->dimensions,
            ];
        }

        return $verified;
    }

    /**
     * @param array<int, string> $inputs
     */
    public function embed(array $inputs, string $routingKey): OllamaEmbeddingResponse
    {
        if ($inputs === [] || ! array_is_list($inputs) || collect($inputs)->contains(fn (mixed $input): bool => ! is_string($input) || blank($input))) {
            throw new OllamaEmbeddingPoolException('Embedding inputs must be a non-empty list of non-blank strings.');
        }

        $attempted = [];

        foreach ($this->orderedServers($routingKey) as $server) {
            if ($this->circuitIsOpen($server)) {
                continue;
            }

            $slot = $this->reserveSlot($server);

            if ($slot === null) {
                continue;
            }

            try {
                $attempted[] = $server->name;
                $this->assertServerProfile($server);
                $response = $this->embedOn($server, $inputs);
                $this->clearFailures($server);

                return $response;
            } catch (OllamaProfileMismatchException $exception) {
                throw $exception;
            } catch (ConnectionException $exception) {
                $this->recordTransientFailure($server);
            } catch (OllamaTransientException $exception) {
                $this->recordTransientFailure($server);
            } finally {
                $slot->release();
            }
        }

        $servers = $attempted === [] ? 'no healthy Ollama server' : implode(', ', $attempted);

        throw new OllamaEmbeddingPoolException("No Ollama server could generate the requested embedding ({$servers}).");
    }

    /** @return array<int, OllamaServer> */
    private function orderedServers(string $routingKey): array
    {
        $servers = $this->servers;
        $offset = hexdec(substr(hash('sha256', $routingKey), 0, 8)) % count($servers);

        return array_merge(array_slice($servers, $offset), array_slice($servers, 0, $offset));
    }

    private function assertServerProfile(OllamaServer $server): void
    {
        $response = $this->request($server)->get('/api/tags');
        $this->assertResponse($response, $server, 'GET', '/api/tags');

        $models = $response->json('models');

        if (! is_array($models)) {
            throw new OllamaProfileMismatchException("Ollama server {$server->name} returned an invalid model list.");
        }

        foreach ($models as $model) {
            if (! is_array($model)
                || (($model['name'] ?? null) !== $this->profile->model && ($model['model'] ?? null) !== $this->profile->model)) {
                continue;
            }

            if (($model['digest'] ?? null) !== $this->profile->digest) {
                throw new OllamaProfileMismatchException("Ollama server {$server->name} has a different digest for the configured embedding model.");
            }

            $probe = $this->request($server)->post('/api/embed', [
                'model' => $this->profile->model,
                'input' => ['Materialpool embedding profile verification.'],
                'dimensions' => $this->profile->dimensions,
                'truncate' => false,
                'options' => $this->profile->options,
            ]);
            $this->assertResponse($probe, $server, 'POST', '/api/embed');
            $this->assertEmbeddingResponse($probe, $server, 1);

            return;
        }

        throw new OllamaProfileMismatchException("Ollama server {$server->name} does not provide the configured embedding model.");
    }

    /** @param array<int, string> $inputs */
    private function embedOn(OllamaServer $server, array $inputs): OllamaEmbeddingResponse
    {
        $response = $this->request($server)->post('/api/embed', [
            'model' => $this->profile->model,
            'input' => $inputs,
            'dimensions' => $this->profile->dimensions,
            'truncate' => false,
            'options' => $this->profile->options,
        ]);
        $this->assertResponse($response, $server, 'POST', '/api/embed');
        $embeddings = $this->assertEmbeddingResponse($response, $server, count($inputs));

        return new OllamaEmbeddingResponse($server->name, $this->profile->id(), $embeddings);
    }

    private function request(OllamaServer $server): PendingRequest
    {
        $request = Http::baseUrl(rtrim($server->url, '/'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout);

        if (filled($server->apiKey)) {
            $request->withToken($server->apiKey);
        }

        return $request;
    }

    private function assertResponse(Response $response, OllamaServer $server, string $method, string $path): void
    {
        if ($response->successful()) {
            return;
        }

        if ($response->status() === 401 || $response->status() === 403 || $response->status() === 400 || $response->status() === 404) {
            throw new OllamaProfileMismatchException("Ollama server {$server->name} rejected {$method} {$path} as a configuration error.");
        }

        if ($response->status() === 408 || $response->status() === 429 || $response->serverError()) {
            throw new OllamaTransientException("Ollama server {$server->name} is temporarily unavailable.");
        }

        throw new OllamaEmbeddingPoolException("Ollama server {$server->name} returned HTTP {$response->status()}.");
    }

    /** @return array<int, array<float>> */
    private function assertEmbeddingResponse(Response $response, OllamaServer $server, int $expectedCount): array
    {
        if (($response->json('model') ?? $this->profile->model) !== $this->profile->model) {
            throw new OllamaProfileMismatchException("Ollama server {$server->name} returned a different embedding model.");
        }

        $embeddings = $response->json('embeddings');

        if (! is_array($embeddings) || count($embeddings) !== $expectedCount) {
            throw new OllamaProfileMismatchException("Ollama server {$server->name} returned an unexpected embedding count.");
        }

        foreach ($embeddings as $embedding) {
            if (! is_array($embedding) || count($embedding) !== $this->profile->dimensions) {
                throw new OllamaProfileMismatchException("Ollama server {$server->name} returned incompatible embedding dimensions.");
            }

            foreach ($embedding as $value) {
                if (! is_numeric($value) || ! is_finite((float) $value)) {
                    throw new OllamaProfileMismatchException("Ollama server {$server->name} returned an invalid embedding value.");
                }
            }
        }

        /** @var array<int, array<float>> $embeddings */
        return $embeddings;
    }

    private function reserveSlot(OllamaServer $server): ?object
    {
        for ($slot = 0; $slot < $server->maxConcurrency; $slot++) {
            $lock = $this->cache->lock("context-search:ollama:slot:{$server->name}:{$slot}", $this->timeout + 5);

            if ($lock->get()) {
                return $lock;
            }
        }

        return null;
    }

    private function circuitIsOpen(OllamaServer $server): bool
    {
        $state = $this->cache->get($this->circuitKey($server), []);

        return is_array($state) && (int) ($state['open_until'] ?? 0) > now()->getTimestamp();
    }

    private function recordTransientFailure(OllamaServer $server): void
    {
        try {
            $this->cache->lock("{$this->circuitKey($server)}:lock", 5)->block(1, function () use ($server): void {
                $state = $this->cache->get($this->circuitKey($server), []);
                $failures = (int) ($state['failures'] ?? 0) + 1;
                $openUntil = $failures >= $this->failureThreshold
                    ? now()->addSeconds($this->circuitCooldown)->getTimestamp()
                    : 0;

                $this->cache->put($this->circuitKey($server), [
                    'failures' => $failures,
                    'open_until' => $openUntil,
                ], now()->addSeconds($this->circuitCooldown));
            });
        } catch (LockTimeoutException) {
            // A concurrent worker is updating the state. The current request already fails over safely.
        }
    }

    private function clearFailures(OllamaServer $server): void
    {
        $this->cache->forget($this->circuitKey($server));
    }

    private function circuitKey(OllamaServer $server): string
    {
        return "context-search:ollama:circuit:{$this->profile->id()}:{$server->name}";
    }
}
