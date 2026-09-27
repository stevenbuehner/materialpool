<?php

namespace App\Services\ContextSearch\Ollama;

use InvalidArgumentException;

final class OllamaServerConfiguration {
	/**
	 * @return array<int, OllamaServer>
	 */
	public static function parse(string $servers, string $apiKeys): array {
		$keys   = self::parseMap($apiKeys, 'Ollama API keys');
		$result = [];

		foreach (array_filter(array_map('trim', explode(',', $servers))) as $server) {
			[$name, $definition] = array_pad(explode('=', $server, 2), 2, NULL);

			if (blank($name) || blank($definition)) {
				throw new InvalidArgumentException('Each Ollama server must use name=url|max_parallel_jobs format.');
			}

			[$url, $limit] = array_pad(explode('|', $definition, 2), 2, '1');

			if (isset($result[$name])) {
				throw new InvalidArgumentException("The Ollama server {$name} is configured more than once.");
			}

			$result[$name] = new OllamaServer(
				name: $name,
				url: trim($url),
				apiKey: $keys[$name] ?? NULL,
				maxConcurrency: filter_var(trim($limit), FILTER_VALIDATE_INT) ?: 0,
			);
		}

		if ($result === []) {
			throw new InvalidArgumentException('At least one Ollama server must be configured.');
		}

		$unknownKeys = array_diff(array_keys($keys), array_keys($result));

		if ($unknownKeys !== []) {
			throw new InvalidArgumentException('An Ollama API key was configured for an unknown server.');
		}

		return array_values($result);
	}

	/** @return array<string, string> */
	private static function parseMap(string $value, string $label): array {
		if (blank($value)) {
			return [];
		}

		$result = [];

		foreach (array_filter(array_map('trim', explode(',', $value))) as $entry) {
			[$key, $item] = array_pad(explode('=', $entry, 2), 2, NULL);

			if (blank($key) || blank($item) || isset($result[$key])) {
				throw new InvalidArgumentException("{$label} must use unique name=value entries.");
			}

			$result[$key] = $item;
		}

		return $result;
	}
}
