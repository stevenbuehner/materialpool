<?php

namespace App\Services\ContextSearch\Extraction;

final readonly class OcrResult
{
    public function __construct(
        public string $text,
        public float $quality,
        public string $version,
    ) {
    }
}
