<?php

namespace App\Services\ContextSearch\Qdrant;

use InvalidArgumentException;
use RuntimeException;

final class QdrantCollectionProvisioner
{
    /** @var array<string, string> */
    private const PAYLOAD_INDEXES = [
        'resource_id' => 'integer',
        'document_revision' => 'keyword',
        'index_revision' => 'keyword',
        'source_type' => 'keyword',
        'language' => 'keyword',
        'embedding_profile' => 'keyword',
    ];

    public function __construct(
        private readonly QdrantClient $client,
        private readonly string $collectionPrefix,
        private readonly string $activeAlias,
        private readonly string $distance,
        private readonly bool $vectorsOnDisk,
        private readonly bool $payloadOnDisk,
    ) {
    }

    public function provision(string $profileHash, string $generation, int $dimensions): string
    {
        $this->assertNameComponent($profileHash, 'profile hash');
        $this->assertNameComponent($generation, 'generation');

        if ($dimensions < 1) {
            throw new InvalidArgumentException('The vector dimension must be positive.');
        }

        $collectionName = "{$this->collectionPrefix}_{$profileHash}_{$generation}";

        if (strlen($collectionName) > 255) {
            throw new InvalidArgumentException('The generated Qdrant collection name is too long.');
        }

        if ($this->client->collection($collectionName) === null) {
            $this->client->createCollection(
                $collectionName,
                $dimensions,
                $this->distance,
                $this->vectorsOnDisk,
                $this->payloadOnDisk,
            );
        }

        $collection = $this->client->collection($collectionName);
        $this->assertCompatibleCollection($collectionName, $collection, $dimensions);

        foreach (self::PAYLOAD_INDEXES as $field => $schema) {
            $this->client->createPayloadIndex($collectionName, $field, $schema);
        }

        return $collectionName;
    }

    public function activate(string $collectionName): void
    {
        if ($this->client->collection($collectionName) === null) {
            throw new RuntimeException("Qdrant collection {$collectionName} does not exist.");
        }

        $this->client->replaceAlias($this->activeAlias, $collectionName);
    }

    private function assertNameComponent(string $value, string $label): void
    {
        if (! preg_match('/\A[a-z0-9][a-z0-9_-]{0,63}\z/', $value)) {
            throw new InvalidArgumentException("The Qdrant {$label} must use lowercase ASCII letters, numbers, underscores, or hyphens.");
        }
    }

    /** @param array<string, mixed>|null $collection */
    private function assertCompatibleCollection(string $name, ?array $collection, int $dimensions): void
    {
        if ($collection === null) {
            throw new RuntimeException("Qdrant collection {$name} was not created.");
        }

        $vectors = $collection['config']['params']['vectors'] ?? null;
        $actualDimensions = is_array($vectors) ? $vectors['size'] ?? null : null;
        $actualDistance = is_array($vectors) ? $vectors['distance'] ?? null : null;

        if ($actualDimensions !== $dimensions || strcasecmp((string) $actualDistance, $this->distance) !== 0) {
            throw new RuntimeException("Qdrant collection {$name} is incompatible with the configured embedding profile.");
        }
    }
}
