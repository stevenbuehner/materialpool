<?php

namespace Tests\Unit\Services\ContextSearch\Qdrant;

use App\Services\ContextSearch\Qdrant\QdrantClient;
use App\Services\ContextSearch\Qdrant\QdrantCollectionProvisioner;
use RuntimeException;
use Tests\TestCase;

final class QdrantCollectionProvisionerTest extends TestCase
{
    public function test_provisions_the_expected_collection_indexes_and_alias(): void
    {
        $client = new FakeQdrantClient();
        $provisioner = $this->provisioner($client);

        $collection = $provisioner->provision('a1b2c3d4', '20260922t120000z', 768);
        $provisioner->activate($collection);

        $this->assertSame('materialpool_chunks_a1b2c3d4_20260922t120000z', $collection);
        $this->assertSame([
            'name' => $collection,
            'dimensions' => 768,
            'distance' => 'Cosine',
            'vectors_on_disk' => true,
            'payload_on_disk' => true,
        ], $client->createdCollection);
        $this->assertSame([
            'resource_id' => 'integer',
            'document_revision' => 'keyword',
            'source_type' => 'keyword',
            'language' => 'keyword',
            'embedding_profile' => 'keyword',
        ], $client->payloadIndexes);
        $this->assertSame('materialpool_chunks_active', $client->replacedAlias);
        $this->assertSame($collection, $client->aliasTarget);
    }

    public function test_rejects_an_existing_collection_with_an_incompatible_dimension(): void
    {
        $client = new FakeQdrantClient();
        $client->collection = [
            'config' => ['params' => ['vectors' => ['size' => 1024, 'distance' => 'Cosine']]],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('incompatible with the configured embedding profile');

        $this->provisioner($client)->provision('a1b2c3d4', '20260922t120000z', 768);
    }

    private function provisioner(QdrantClient $client): QdrantCollectionProvisioner
    {
        return new QdrantCollectionProvisioner(
            client: $client,
            collectionPrefix: 'materialpool_chunks',
            activeAlias: 'materialpool_chunks_active',
            distance: 'Cosine',
            vectorsOnDisk: true,
            payloadOnDisk: true,
        );
    }
}

final class FakeQdrantClient implements QdrantClient
{
    /** @var array<string, mixed>|null */
    public ?array $collection = null;

    /** @var array<string, mixed>|null */
    public ?array $createdCollection = null;

    /** @var array<string, string> */
    public array $payloadIndexes = [];

    public ?string $replacedAlias = null;

    public ?string $aliasTarget = null;

    public function isReady(): bool
    {
        return true;
    }

    public function collection(string $name): ?array
    {
        return $this->collection;
    }

    public function createCollection(
        string $name,
        int $dimensions,
        string $distance,
        bool $vectorsOnDisk,
        bool $payloadOnDisk,
    ): void {
        $this->createdCollection = [
            'name' => $name,
            'dimensions' => $dimensions,
            'distance' => $distance,
            'vectors_on_disk' => $vectorsOnDisk,
            'payload_on_disk' => $payloadOnDisk,
        ];
        $this->collection = [
            'config' => ['params' => ['vectors' => [
                'size' => $dimensions,
                'distance' => $distance,
            ]]],
        ];
    }

    public function createPayloadIndex(string $collection, string $field, string $schema): void
    {
        $this->payloadIndexes[$field] = $schema;
    }

    public function aliases(): array
    {
        return [];
    }

    public function replaceAlias(string $alias, string $collection): void
    {
        $this->replacedAlias = $alias;
        $this->aliasTarget = $collection;
    }

    public function upsertPoints(string $collection, array $points): void
    {
    }

    public function deleteResourcePoints(string $collection, int $resourceId, string $embeddingProfile): void
    {
    }
}
