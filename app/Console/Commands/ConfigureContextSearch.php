<?php

namespace App\Console\Commands;

use App\Services\ContextSearch\EmbeddingProfile;
use App\Services\ContextSearch\Ollama\OllamaEmbeddingPool;
use App\Services\ContextSearch\Ollama\OllamaEmbeddingPoolException;
use App\Services\ContextSearch\Ollama\OllamaModelDigest;
use App\Services\ContextSearch\Ollama\OllamaServer;
use App\Services\ContextSearch\Ollama\OllamaServerConfiguration;
use App\Support\EnvironmentFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

final class ConfigureContextSearch extends Command {
	protected $signature = 'context-search:configure';
	protected $description = 'Kontextsuche und Ollama-Server interaktiv konfigurieren und prüfen';

	public function handle(EnvironmentFile $environment): int {
		if (!$this->input->isInteractive()) {
			$this->components->error('Dieser Befehl benötigt ein interaktives Terminal.');
			return self::INVALID;
		}
		$wasEnabled = (bool)config('context_search.enabled');
		try {
			$environment->assertWritable();
			// Auch ein SIGINT während einer Frage lässt die Suche sicher deaktiviert.
			$environment->write(['CONTEXT_SEARCH_ENABLED' => 'false']);
			$this->call('config:clear');
			config(['context_search.enabled' => FALSE]);
		} catch (Throwable) {
			$this->components->error('Die Produktions-.env muss als root beschreibbar sein.');
			return self::FAILURE;
		}

		$servers = $this->existingServers();
		foreach ($servers as $server) {
			try {
				$request = Http::baseUrl(rtrim($server->url, '/'))->connectTimeout(2)->timeout(3)->acceptJson();
				if ($server->apiKey !== NULL) {
					$request->withToken($server->apiKey);
				}
				$response = $request->get('/api/tags');
				$this->line("{$server->name}: " . ($response->successful() && is_array($response->json('models')) ? 'erreichbar' : 'nicht erreichbar'));
			} catch (Throwable) {
				$this->line("{$server->name}: nicht erreichbar (Timeout oder Verbindungsfehler)");
			}
		}

		$completed = FALSE;
		try {
			if (!$this->confirm('Context-Suche aktivieren?', $wasEnabled)) {
				$environment->write(['CONTEXT_SEARCH_ENABLED' => 'false']);
				$this->call('config:clear');
				$this->components->info('Kontextsuche ist deaktiviert. Vorhandene Serverdaten bleiben erhalten.');
				$completed = TRUE;
				return self::SUCCESS;
			}

			while (TRUE) {
				$this->line('Ollama-Server: ' . ($servers === [] ? 'keine' : implode(', ', array_map(fn(OllamaServer $server): string => $server->name, $servers))));
				$action = $this->choice('Aktion', ['Hinzufügen', 'Bearbeiten', 'Entfernen', 'Fertig'], 'Fertig');
				if ($action === 'Fertig') {
					break;
				}
				if ($action === 'Hinzufügen') {
					try {
						$server = $this->promptServer(NULL);
						if (isset($servers[$server->name])) {
							throw new \InvalidArgumentException('Der Servername ist bereits vergeben.');
						}
						$servers[$server->name] = $server;
					} catch (\InvalidArgumentException $exception) {
						$this->components->error($exception->getMessage());
					}
				} else {
					if ($servers === []) {
						$this->components->warn('Es ist kein Server vorhanden.');
						continue;
					}
					$name = $this->choice('Server wählen', array_keys($servers));
					if ($action === 'Entfernen') {
						unset($servers[$name]);
					} else {
						try {
							$servers[$name] = $this->promptServer($servers[$name]);
						} catch (\InvalidArgumentException $exception) {
							$this->components->error($exception->getMessage());
						}
					}
				}
			}

			$definitions = implode(',', array_map(fn(OllamaServer $server): string => "{$server->name}={$server->url}|{$server->maxConcurrency}", $servers));
			$keys = implode(',', array_map(fn(OllamaServer $server): string => "{$server->name}={$server->apiKey}", array_filter($servers, fn(OllamaServer $server): bool => filled($server->apiKey))));
			$values = ['CONTEXT_SEARCH_OLLAMA_SERVERS' => $definitions, 'CONTEXT_SEARCH_OLLAMA_API_KEYS' => $keys];
			if ($servers === []) {
				$environment->write($values + ['CONTEXT_SEARCH_ENABLED' => 'false']);
				$this->call('config:clear');
				$this->components->info('Keine Ollama-Server gespeichert; Kontextsuche bleibt deaktiviert.');
				$completed = TRUE;
				return self::SUCCESS;
			}
			if ($this->call('context-search:qdrant:configure') !== self::SUCCESS) {
				return self::FAILURE;
			}

			$parsed = OllamaServerConfiguration::parse($definitions, $keys);
			$model = trim((string)$this->ask('CONTEXT_SEARCH_EMBEDDING_MODEL', (string)config('context_search.embedding.model')));
			$digest = app(OllamaModelDigest::class)->read($parsed[0], $model);
			$this->line("Embedding-Digest von {$parsed[0]->name} ermittelt: {$digest}");
			$dimensions = trim((string)$this->ask('CONTEXT_SEARCH_EMBEDDING_DIMENSIONS', (string)config('context_search.embedding.dimensions')));
			$options = trim((string)$this->ask('CONTEXT_SEARCH_EMBEDDING_OPTIONS_JSON', (string)config('context_search.embedding.options_json')));
			if (!ctype_digit($dimensions) || (int)$dimensions < 1) {
				throw new \InvalidArgumentException('Die Dimension muss eine positive Ganzzahl sein.');
			}
			$profile = EmbeddingProfile::fromConfiguration(['model' => $model, 'digest' => $digest, 'dimensions' => $dimensions, 'options_json' => $options]);
			$pool = new OllamaEmbeddingPool($parsed, $profile, app('cache.store'), 2, 30, 2, 60);
			$pool->verifyProfile();
			$environment->write($values + [
				'CONTEXT_SEARCH_EMBEDDING_MODEL' => $model,
				'CONTEXT_SEARCH_EMBEDDING_DIGEST' => $digest,
				'CONTEXT_SEARCH_EMBEDDING_DIMENSIONS' => $dimensions,
				'CONTEXT_SEARCH_EMBEDDING_OPTIONS_JSON' => $options,
				'CONTEXT_SEARCH_ENABLED' => 'true',
			]);
			$this->call('config:clear');
			$this->components->info('Alle Ollama-Server geprüft; Kontextsuche aktiviert. Produktive Indexläufe bleiben gesperrt.');
			$completed = TRUE;
			return self::SUCCESS;
		} catch (InvalidArgumentException|OllamaEmbeddingPoolException $exception) {
			$this->components->error($exception->getMessage());
			$this->components->error('Kontextsuche wird deaktiviert.');
			return self::FAILURE;
		} catch (Throwable) {
			$this->components->error('Konfiguration abgebrochen oder Prüfung fehlgeschlagen. Kontextsuche wird deaktiviert.');
			return self::FAILURE;
		} finally {
			if (!$completed) {
				try {
					$environment->write(['CONTEXT_SEARCH_ENABLED' => 'false']);
					$this->call('config:clear');
				} catch (Throwable) {
					$this->components->error('Die Kontextsuche konnte nicht deaktiviert werden. .env manuell prüfen.');
				}
			}
		}
	}

	/** @return array<string, OllamaServer> */
	private function existingServers(): array {
		try {
			$raw = (string)config('context_search.ollama.servers');
			if (trim($raw) === '') {
				return [];
			}
			$result = [];
			foreach (OllamaServerConfiguration::parse($raw, (string)config('context_search.ollama.api_keys')) as $server) {
				$result[$server->name] = $server;
			}
			return $result;
		} catch (Throwable) {
			$this->components->warn('Die vorhandene Ollama-Konfiguration ist ungültig. Sie kann neu erfasst werden.');
			return [];
		}
	}

	private function promptServer(?OllamaServer $existing): OllamaServer {
		$name = $existing?->name ?? trim((string)$this->ask('Servername (klein, eindeutig)'));
		$url = trim((string)$this->ask("URL für {$name}", $existing?->url));
		$limit = trim((string)$this->ask("Maximale parallele Aufträge für {$name}", (string)($existing?->maxConcurrency ?? 1)));
		$key = (string)$this->secret("API-Key für {$name} (leer: bisherigen behalten/kein Key)");
		$key = $key === '' ? $existing?->apiKey : $key;
		if ($existing?->apiKey !== NULL && $this->confirm("API-Key für {$name} entfernen?", FALSE)) {
			$key = NULL;
		}
		if ($key !== NULL && str_contains($key, ',')) {
			throw new \InvalidArgumentException('Ollama-API-Keys dürfen kein Komma enthalten.');
		}
		return new OllamaServer($name, $url, $key, ctype_digit($limit) ? (int)$limit : 0);
	}
}
