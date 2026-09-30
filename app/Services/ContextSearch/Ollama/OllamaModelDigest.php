<?php

namespace App\Services\ContextSearch\Ollama;

use Illuminate\Support\Facades\Http;

final class OllamaModelDigest {
	public function read(OllamaServer $server, string $model): string {
		$request = Http::baseUrl(rtrim($server->url, '/'))
			->acceptJson()
			->connectTimeout(2)
			->timeout(30);

		if ($server->apiKey !== NULL) {
			$request->withToken($server->apiKey);
		}

		$response = $request->get('/api/tags');
		if (!$response->successful() || !is_array($response->json('models'))) {
			throw new OllamaProfileMismatchException("Die Modellliste von Ollama-Server {$server->name} konnte nicht gelesen werden.");
		}

		foreach ($response->json('models') as $entry) {
			if (!is_array($entry) || (($entry['name'] ?? NULL) !== $model && ($entry['model'] ?? NULL) !== $model)) {
				continue;
			}

			$digest = $entry['digest'] ?? NULL;
			if (!is_string($digest) || preg_match('/\A[a-f0-9]{64}\z/', $digest) !== 1) {
				throw new OllamaProfileMismatchException("Ollama-Server {$server->name} liefert keinen gültigen SHA-256-Digest für {$model}.");
			}

			return $digest;
		}

		throw new OllamaProfileMismatchException("Ollama-Server {$server->name} stellt das Embedding-Modell {$model} nicht bereit.");
	}
}
