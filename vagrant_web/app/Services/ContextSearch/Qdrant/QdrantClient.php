<?php

namespace App\Services\ContextSearch\Qdrant;

interface QdrantClient
{
    public function isReady(): bool;

    /** @return array<string, mixed>|null */
    public function collection(string $name): ?array;

    public function createCollection(
        string $name,
        int $dimensions,
        string $distance,
        bool $vectorsOnDisk,
        bool $payloadOnDisk,
    ): void;

    public function createPayloadIndex(string $collection, string $field, string $schema): void;

    /** @return array<string, string> */
    public function aliases(): array;

    public function replaceAlias(string $alias, string $collection): void;

    /** @param array<int, array{id: string, vector: array<float>, payload: array<string, mixed>}> $points */
    public function upsertPoints(string $collection, array $points): void;

    public function deleteResourcePoints(string $collection, int $resourceId, string $embeddingProfile): void;
}
