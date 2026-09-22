<?php

namespace App\Services\ContextSearch\Extraction;

final readonly class ExtractedPage
{
    public function __construct(
        public int $pageNumber,
        public string $text,
        public string $method,
        public float $quality,
        public string $extractorVersion,
    ) {
    }
}
