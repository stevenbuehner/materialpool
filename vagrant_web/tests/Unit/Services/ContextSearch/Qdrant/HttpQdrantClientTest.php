<?php

namespace Tests\Unit\Services\ContextSearch\Qdrant;

use App\Services\ContextSearch\Qdrant\HttpQdrantClient;
use App\Services\ContextSearch\Qdrant\QdrantRequestException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class HttpQdrantClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_creates_a_collection_and_payload_index_with_authentication(): void
    {
        Http::fake([
            'qdrant.test/collections/*/index*' => Http::response(['status' => 'ok', 'result' => ['status' => 'completed']]),
            'qdrant.test/collections/*' => Http::response(['status' => 'ok', 'result' => true]),
        ]);

        $client = $this->client();
        $client->createCollection('materialpool_chunks_profile_generation', 768, 'Cosine', true, true);
        $client->createPayloadIndex('materialpool_chunks_profile_generation', 'resource_id', 'integer');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PUT'
                && $request->url() === 'http://qdrant.test/collections/materialpool_chunks_profile_generation'
                && $request->hasHeader('api-key', 'test-secret')
                && $request['vectors'] === [
                    'size' => 768,
                    'distance' => 'Cosine',
                    'on_disk' => true,
                ]
                && $request['on_disk_payload'] === true;
        });

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PUT'
                && $request->url() === 'http://qdrant.test/collections/materialpool_chunks_profile_generation/index?wait=true'
                && $request['field_name'] === 'resource_id'
                && $request['field_schema'] === 'integer';
        });
    }

    public function test_replaces_an_existing_alias_atomically(): void
    {
        Http::fake(function (Request $request) {
            if ($request->method() === 'GET') {
                return Http::response([
                    'status' => 'ok',
                    'result' => ['aliases' => [[
                        'alias_name' => 'materialpool_chunks_active',
                        'collection_name' => 'materialpool_chunks_old_generation',
                    ]]],
                ]);
            }

            return Http::response(['status' => 'ok', 'result' => true]);
        });

        $this->client()->replaceAlias('materialpool_chunks_active', 'materialpool_chunks_new_generation');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'http://qdrant.test/collections/aliases'
                && $request['actions'] === [
                    ['delete_alias' => ['alias_name' => 'materialpool_chunks_active']],
                    ['create_alias' => [
                        'alias_name' => 'materialpool_chunks_active',
                        'collection_name' => 'materialpool_chunks_new_generation',
                    ]],
                ];
        });
    }

    public function test_does_not_expose_the_api_key_in_request_errors(): void
    {
        Http::fake(['qdrant.test/*' => Http::response(['status' => 'error'], 401)]);

        try {
            $this->client()->aliases();
            $this->fail('Expected QdrantRequestException was not thrown.');
        } catch (QdrantRequestException $exception) {
            $this->assertStringNotContainsString('test-secret', $exception->getMessage());
            $this->assertStringContainsString('HTTP 401', $exception->getMessage());
        }
    }

    public function test_upserts_and_deletes_resource_points_with_the_profile_filter(): void
    {
        Http::fake(['qdrant.test/*' => Http::response(['status' => 'ok', 'result' => ['status' => 'completed']])]);

        $this->client()->upsertPoints('materialpool_chunks_profile_generation', [[
            'id' => 'a3d03722-6077-5e44-a391-3302f9c9386a',
            'vector' => [0.1, 0.2],
            'payload' => ['resource_id' => 42],
        ]]);
        $this->client()->deleteResourcePoints('materialpool_chunks_profile_generation', 42, 'profile-1');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PUT'
                && $request->url() === 'http://qdrant.test/collections/materialpool_chunks_profile_generation/points?wait=true'
                && $request['points'][0]['payload']['resource_id'] === 42;
        });
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'http://qdrant.test/collections/materialpool_chunks_profile_generation/points/delete?wait=true'
                && $request['filter']['must'] === [
                    ['key' => 'resource_id', 'match' => ['value' => 42]],
                    ['key' => 'embedding_profile', 'match' => ['value' => 'profile-1']],
                ];
        });
    }

    private function client(): HttpQdrantClient
    {
        return new HttpQdrantClient(
            url: 'http://qdrant.test',
            apiKey: 'test-secret',
            connectTimeout: 2,
            timeout: 10,
        );
    }
}
