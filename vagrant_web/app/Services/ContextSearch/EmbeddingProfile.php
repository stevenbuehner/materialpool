<?php

namespace App\Services\ContextSearch;

use InvalidArgumentException;
use JsonException;

final class EmbeddingProfile
{
    /** @param array<string, mixed> $options */
    public function __construct(
        public readonly string $model,
        public readonly string $digest,
        public readonly int $dimensions,
        public readonly array $options,
    ) {
        if (blank($this->model)) {
            throw new InvalidArgumentException('The context-search embedding model must be configured.');
        }

        if (! preg_match('/\A[a-f0-9]{64}\z/i', $this->digest)) {
            throw new InvalidArgumentException('The context-search embedding digest must be a SHA-256 digest.');
        }

        if ($this->dimensions < 1) {
            throw new InvalidArgumentException('The context-search embedding dimensions must be positive.');
        }
    }

    /** @param array<string, mixed> $configuration */
    public static function fromConfiguration(array $configuration): self
    {
        $optionsJson = $configuration['options_json'] ?? '{}';

        if (! is_string($optionsJson)) {
            throw new InvalidArgumentException('The context-search embedding options must be JSON.');
        }

        try {
            $options = json_decode($optionsJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('The context-search embedding options must be valid JSON.');
        }

        if (! is_array($options) || ! str_starts_with(ltrim($optionsJson), '{')) {
            throw new InvalidArgumentException('The context-search embedding options must be a JSON object.');
        }

        return new self(
            model: (string) ($configuration['model'] ?? ''),
            digest: strtolower((string) ($configuration['digest'] ?? '')),
            dimensions: (int) ($configuration['dimensions'] ?? 0),
            options: self::sortRecursively($options),
        );
    }

    public function id(): string
    {
        try {
            return substr(hash('sha256', json_encode([
                'model' => $this->model,
                'digest' => $this->digest,
                'dimensions' => $this->dimensions,
                'options' => $this->options,
            ], JSON_THROW_ON_ERROR)), 0, 16);
        } catch (JsonException) {
            throw new InvalidArgumentException('The context-search embedding profile cannot be serialized.');
        }
    }

    /** @param array<mixed> $value
     *  @return array<mixed>
     */
    private static function sortRecursively(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::sortRecursively($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
