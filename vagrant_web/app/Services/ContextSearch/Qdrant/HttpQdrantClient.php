<?php

namespace App\Services\ContextSearch\Qdrant;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

final class HttpQdrantClient implements QdrantClient
{
    public function __construct(
        private readonly string $url,
        private readonly ?string $apiKey,
        private readonly int $connectTimeout,
        private readonly int $timeout,
    ) {
        $parts = parse_url($this->url);

        if (! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || blank($parts['host'] ?? null)
            || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])) {
            throw new InvalidArgumentException('The Qdrant URL must be an HTTP(S) base URL without credentials, query, or fragment.');
        }

        if ($this->connectTimeout < 1 || $this->timeout < 1) {
            throw new InvalidArgumentException('Qdrant timeouts must be positive.');
        }
    }

    public function isReady(): bool
    {
        try {
            return $this->safeRequest()->get('/readyz')->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    public function collection(string $name): ?array
    {
        $response = $this->safeRequest()->get('/collections/'.rawurlencode($name));

        if ($response->notFound()) {
            return null;
        }

        $this->assertSuccessful($response, 'GET', '/collections/{collection}');

        $result = $response->json('result');

        if (! is_array($result)) {
            throw new QdrantRequestException('Qdrant returned an invalid collection response.');
        }

        return $result;
    }

    public function createCollection(
        string $name,
        int $dimensions,
        string $distance,
        bool $vectorsOnDisk,
        bool $payloadOnDisk,
    ): void {
        $response = $this->safeRequest()->put('/collections/'.rawurlencode($name), [
            'vectors' => [
                'size' => $dimensions,
                'distance' => $distance,
                'on_disk' => $vectorsOnDisk,
            ],
            'on_disk_payload' => $payloadOnDisk,
        ]);

        if ($response->conflict()) {
            return;
        }

        $this->assertSuccessful($response, 'PUT', '/collections/{collection}');
    }

    public function createPayloadIndex(string $collection, string $field, string $schema): void
    {
        $response = $this->safeRequest()->put('/collections/'.rawurlencode($collection).'/index?wait=true', [
            'field_name' => $field,
            'field_schema' => $schema,
        ]);

        if ($response->conflict()) {
            return;
        }

        $this->assertSuccessful($response, 'PUT', '/collections/{collection}/index');
    }

    public function aliases(): array
    {
        $response = $this->safeRequest()->get('/aliases');
        $this->assertSuccessful($response, 'GET', '/aliases');

        $aliases = $response->json('result.aliases');

        if (! is_array($aliases)) {
            throw new QdrantRequestException('Qdrant returned an invalid alias response.');
        }

        $result = [];

        foreach ($aliases as $alias) {
            if (is_array($alias)
                && is_string($alias['alias_name'] ?? null)
                && is_string($alias['collection_name'] ?? null)) {
                $result[$alias['alias_name']] = $alias['collection_name'];
            }
        }

        return $result;
    }

    public function replaceAlias(string $alias, string $collection): void
    {
        $currentCollection = $this->aliases()[$alias] ?? null;

        if ($currentCollection === $collection) {
            return;
        }

        $actions = [];

        if ($currentCollection !== null) {
            $actions[] = ['delete_alias' => ['alias_name' => $alias]];
        }

        $actions[] = ['create_alias' => [
            'alias_name' => $alias,
            'collection_name' => $collection,
        ]];

        $response = $this->request()->post('/collections/aliases', ['actions' => $actions]);
        $this->assertSuccessful($response, 'POST', '/collections/aliases');
    }

    public function upsertPoints(string $collection, array $points): void
    {
        if ($points === []) {
            return;
        }

        $response = $this->safeRequest()->put('/collections/'.rawurlencode($collection).'/points?wait=true', [
            'points' => $points,
        ]);

        $this->assertSuccessful($response, 'PUT', '/collections/{collection}/points');
    }

    public function deleteResourcePoints(string $collection, int $resourceId, string $embeddingProfile): void
    {
        $response = $this->safeRequest()->post('/collections/'.rawurlencode($collection).'/points/delete?wait=true', [
            'filter' => [
                'must' => [
                    ['key' => 'resource_id', 'match' => ['value' => $resourceId]],
                    ['key' => 'embedding_profile', 'match' => ['value' => $embeddingProfile]],
                ],
            ],
        ]);

        $this->assertSuccessful($response, 'POST', '/collections/{collection}/points/delete');
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl(rtrim($this->url, '/'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout);

        if (filled($this->apiKey)) {
            $request->withHeaders(['api-key' => $this->apiKey]);
        }

        return $request;
    }

    private function safeRequest(): PendingRequest
    {
        return $this->request()->retry(
            [100, 300],
            when: static fn (Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException
                    && ($exception->response->serverError() || $exception->response->status() === 429)),
            throw: false,
        );
    }

    private function assertSuccessful(Response $response, string $method, string $path): void
    {
        if (! $response->successful()) {
            throw new QdrantRequestException(sprintf(
                'Qdrant request %s %s failed with HTTP %d.',
                $method,
                $path,
                $response->status(),
            ));
        }
    }
}
