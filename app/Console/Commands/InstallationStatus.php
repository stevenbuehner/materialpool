<?php

namespace App\Console\Commands;

use App\Services\Bibles\Import\BibleDataImporter;
use App\Services\Bibles\Import\OpenBibleData;
use App\Services\ContextSearch\EmbeddingProfile;
use App\Services\ContextSearch\Ollama\OllamaEmbeddingPool;
use App\Services\ContextSearch\Ollama\OllamaServerConfiguration;
use App\Services\ContextSearch\Qdrant\QdrantClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Throwable;

class InstallationStatus extends Command {
	protected $signature = 'materialpool:status
        {--latest-version= : Bereits geprüfte neueste stabile Release-Version}';

	protected $description = 'Installations- und Betriebsstatus ohne Änderungen anzeigen';

	public function handle(BibleDataImporter $importer): int {
		$version = $this->installedVersion();
		$latest = $this->latestVersion();
		$this->section(config('app.name').' '.$version);
		if ($latest === NULL) {
			$this->warningLine('Update-Status: nicht abrufbar');
		} elseif ($version === 'unbekannt') {
			$this->warningLine('Update-Status: installierte Version unbekannt; neuestes Release '.$latest);
		} elseif (version_compare($latest, $version, '>')) {
			$this->warningLine('Update verfügbar: '.$latest);
		} else {
			$this->successLine('Update-Status: aktuell');
		}

		// Eine vollständige URL kann der Terminal-Linker erkennen und bleibt auch kopierbar.
		$this->line('<fg=cyan>URL:</> '.OutputFormatter::escape((string)config('app.url')));
		$proxies = trim((string)config('trustedproxy.proxies'));
		if ($proxies !== '') {
			$this->line('<fg=cyan>Trusted Proxy:</> '.OutputFormatter::escape($proxies));
		}
		if (config('session.secure') === FALSE && (parse_url((string)config('app.url'), PHP_URL_SCHEME) === 'https' || $proxies !== '')) {
			$this->line('<fg=red>! SESSION_SECURE_COOKIE=false: Bei HTTPS beziehungsweise einem TLS-Reverse-Proxy muss der Wert true sein.</>');
		}

		$this->newLine();
		$this->section('Qdrant');
		if (!config('context_search.enabled')) {
			$this->warningLine('Kontextsuche in der Konfiguration deaktiviert');
			$this->warningLine('Qdrant-Erreichbarkeit: nicht geprüft');
		} else {
			$this->line('<fg=cyan>URL:</> '.OutputFormatter::escape((string)config('context_search.qdrant.url')));
			$this->line('<fg=cyan>Collection-Präfix:</> '.OutputFormatter::escape((string)config('context_search.qdrant.collection_prefix')));
			$this->line('<fg=cyan>Aktiver Alias:</> '.OutputFormatter::escape((string)config('context_search.qdrant.active_alias')));
			$this->line('<fg=cyan>API-Key konfiguriert:</> '.(filled(config('context_search.qdrant.api_key')) ? '<fg=green>ja</>' : '<fg=yellow>nein</>'));
			try {
				app(QdrantClient::class)->isReady()
					? $this->successLine('Qdrant: erreichbar und bereit')
					: $this->warningLine('Qdrant: nicht erreichbar oder nicht bereit');
			} catch (Throwable) {
				$this->warningLine('Qdrant: Erreichbarkeit nicht prüfbar');
			}
		}

		$this->newLine();
		$this->section('Ollama');
		$this->ollamaStatus();

		$this->newLine();
		$this->section('Datenbank');
		try {
			$materials = DB::table('materials')->count();
			$resources = DB::table('resources')->count();
			$keywords = DB::table('keywords')->count();
			$bibleverses = DB::table('bibleverses')->count();
			$this->successLine('Datenbank: erreichbar, Bestände lesbar');
			$this->line('<fg=cyan>Materialien:</> '.$materials);
			$this->line('<fg=cyan>Ressourcen:</> '.$resources);
			$this->line('<fg=cyan>Keywords:</> '.$keywords);
			$this->line('<fg=cyan>Bibelstellen:</> '.$bibleverses);

			$this->newLine();
			$this->section('Seeds');
			$installed = $importer->installed();
			$cross = $installed[OpenBibleData::ID] ?? NULL;
			$this->line('<fg=cyan>Querverweise:</> '.($cross === NULL ? '<fg=yellow>nicht installiert</>' : '<fg=green>installiert ('.$cross->row_count.')</>'));
			$translations = array_filter($installed, fn (object $entry): bool => $entry->kind === 'translation');
			$this->line('<fg=cyan>Bibelübersetzungen:</> '.($translations === [] ? '<fg=yellow>keine installiert</>' : '<fg=green>'.count($translations).' installiert</>'));
			foreach ($translations as $entry) {
				$this->line('  - '.OutputFormatter::escape(preg_replace('/[\x00-\x1F\x7F]/u', '', $entry->title)));
			}
		} catch (Throwable) {
			$this->error('Datenbank und Seeds: Status nicht lesbar');
			return self::FAILURE;
		}

		$this->newLine();
		$this->section('Backup');
		if (!config('backup.enabled')) {
			$this->warningLine('Nicht konfiguriert');
			return self::SUCCESS;
		}
		try {
			return $this->call('backup:list');
		} catch (Throwable) {
			$this->error('Backup-Status nicht abrufbar');
			return self::FAILURE;
		}
	}

	private function ollamaStatus(): void {
		$definitions = trim((string)config('context_search.ollama.servers'));
		if ($definitions === '') {
			$this->warningLine('Keine Ollama-Server konfiguriert');
			return;
		}

		try {
			$servers = OllamaServerConfiguration::parse($definitions, (string)config('context_search.ollama.api_keys'));
		} catch (Throwable) {
			$this->warningLine('Ollama-Konfiguration ungültig');
			return;
		}
		try {
			$profile = app(EmbeddingProfile::class);
		} catch (Throwable) {
			$profile = NULL;
		}

		foreach ($servers as $server) {
			$this->line('<fg=cyan>Server:</> '.OutputFormatter::escape($server->name));
			try {
				$request = Http::baseUrl(rtrim($server->url, '/'))->acceptJson()
					->connectTimeout((int)config('context_search.ollama.connect_timeout'))->timeout(5);
				if (filled($server->apiKey)) {
					$request->withToken($server->apiKey);
				}
				$response = $request->get('/api/tags');
				if (!$response->successful()) {
					$this->warningLine('API: erreichbar, antwortet mit HTTP '.$response->status());
					$this->warningLine('Embedding-Funktion: nicht funktionsfähig');
					continue;
				}
				if (!is_array($response->json('models'))) {
					$this->warningLine('API: erreichbar, Modellliste ungültig');
					$this->warningLine('Embedding-Funktion: nicht funktionsfähig');
					continue;
				}
				$this->successLine('API: erreichbar');
			} catch (Throwable) {
				$this->warningLine('API: nicht erreichbar');
				$this->warningLine('Embedding-Funktion: nicht prüfbar');
				continue;
			}

			if ($profile === NULL) {
				$this->warningLine('Embedding-Funktion: nicht prüfbar (Profil nicht konfiguriert)');
				continue;
			}
			try {
				$pool = new OllamaEmbeddingPool(
					[$server], $profile, app('cache.store'),
					(int)config('context_search.ollama.connect_timeout'),
					(int)config('context_search.embedding.timeout'),
					(int)config('context_search.ollama.failure_threshold'),
					(int)config('context_search.ollama.circuit_cooldown'),
				);
				$pool->verifyProfile();
				$this->successLine('Embedding-Funktion: funktionsfähig');
			} catch (Throwable) {
				$this->warningLine('Embedding-Funktion: nicht funktionsfähig');
			}
		}
	}

	private function section(string $title): void {
		$this->line('<fg=cyan;options=bold>● '.OutputFormatter::escape($title).'</>');
	}

	private function successLine(string $message): void {
		$this->line('<fg=green>✓ '.OutputFormatter::escape($message).'</>');
	}

	private function warningLine(string $message): void {
		$this->line('<fg=yellow>! '.OutputFormatter::escape($message).'</>');
	}

	private function installedVersion(): string {
		$release = base_path('release.json');
		if (!File::exists($release)) {
			return 'unbekannt';
		}
		$version = json_decode(File::get($release), TRUE)['version'] ?? NULL;
		return is_string($version) && preg_match('/^v?[0-9]+\.[0-9]+\.[0-9]+[a-z]?$/', $version) ? $version : 'unbekannt';
	}

	private function latestVersion(): ?string {
		$option = $this->option('latest-version');
		if ($option !== NULL) {
			return is_string($option) && preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $option) ? $option : NULL;
		}
		try {
			$response = Http::connectTimeout(2)->timeout(5)->acceptJson()
				->get('https://api.github.com/repos/stevenbuehner/materialpool/releases/latest');
			if (!$response->successful() || $response->json('draft') !== FALSE || $response->json('prerelease') !== FALSE) {
				return NULL;
			}
			$latest = $response->json('tag_name');
			return is_string($latest) && preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $latest) ? $latest : NULL;
		} catch (Throwable) {
			return NULL;
		}
	}
}
