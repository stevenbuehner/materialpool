<?php

namespace App\Services\ContextSearch;

final readonly class TextChunk
{
    public function __construct(
        public int $ordinal,
        public string $content,
        public int $startCharacter,
        public int $endCharacter,
    ) {
    }
}
