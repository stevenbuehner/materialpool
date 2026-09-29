<?php

namespace App\Console\Commands;

use App\Services\ContextSearch\Qdrant\HttpQdrantClient;
use App\Support\EnvironmentFile;
use Illuminate\Console\Command;
use Throwable;

final class ConfigureContextSearchQdrant extends Command {
	protected $signature = 'context-search:qdrant:configure';
	protected $description = 'Qdrant-Verbindung interaktiv einrichten oder bearbeiten und vor dem Speichern prüfen';

	public function handle(EnvironmentFile $environment): int {
		if (!$this->input->isInteractive()) {
			$this->components->error('Dieser Befehl benötigt ein interaktives Terminal.');
			return self::INVALID;
		}
		try {
			$environment->assertWritable();
			$url = trim((string)$this->ask('QDRANT_URL', (string)config('context_search.qdrant.url')));
			if ($url === '') {
				$this->components->error('Eine Qdrant-URL ist erforderlich. Die bestehende Konfiguration blieb unverändert.');
				return self::INVALID;
			}

			$existingKey = (string)config('context_search.qdrant.api_key');
			$key = (string)$this->secret($existingKey === '' ? 'QDRANT_API_KEY' : 'QDRANT_API_KEY (leer: bisherigen Schlüssel behalten)');
			$key = $key === '' ? $existingKey : $key;
			if ($key === '') {
				$this->components->error('Ein Qdrant-API-Key ist erforderlich. Die bestehende Konfiguration blieb unverändert.');
				return self::INVALID;
			}

			$client = new HttpQdrantClient($url, $key, 2, 5);
			if (!$client->isReady()) {
				throw new \RuntimeException('Qdrant ist nicht bereit.');
			}
			$client->aliases(); // Authentifizierung und JSON-Antwort über einen lesenden Endpunkt prüfen.
			$environment->write(['QDRANT_URL' => $url, 'QDRANT_API_KEY' => $key]);
			$this->call('config:clear');
			config(['context_search.qdrant.url' => $url, 'context_search.qdrant.api_key' => $key]);
			$this->components->info('Qdrant-Verbindung geprüft und gespeichert.');
			return self::SUCCESS;
		} catch (Throwable) {
			$this->components->error('Qdrant konnte nicht geprüft oder gespeichert werden. Die bestehende Verbindung blieb unverändert.');
			return self::FAILURE;
		}
	}
}
