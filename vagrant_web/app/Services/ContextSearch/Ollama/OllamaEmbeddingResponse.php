<?php

namespace App\Services\ContextSearch\Ollama;

final class OllamaEmbeddingResponse
{
    /** @param array<int, array<float>> $embeddings */
    public function __construct(
        public readonly string $server,
        public readonly string $profileId,
        public readonly array $embeddings,
    ) {
    }
}
