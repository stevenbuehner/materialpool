<?php

namespace App\Services\ContextSearch\Ollama;

use InvalidArgumentException;

final class OllamaServer
{
    public function __construct(
        public readonly string $name,
        public readonly string $url,
        public readonly ?string $apiKey,
        public readonly int $maxConcurrency,
    ) {
        if (! preg_match('/\A[a-z0-9][a-z0-9_-]{0,63}\z/', $this->name)) {
            throw new InvalidArgumentException('Ollama server names must use lowercase ASCII letters, numbers, underscores, or hyphens.');
        }

        $parts = parse_url($this->url);

        if (! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || blank($parts['host'] ?? null)
            || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])) {
            throw new InvalidArgumentException("The Ollama URL for {$this->name} must be an HTTP(S) base URL without credentials, query, or fragment.");
        }

        if ($this->maxConcurrency < 1) {
            throw new InvalidArgumentException("The Ollama concurrency limit for {$this->name} must be positive.");
        }
    }
}
