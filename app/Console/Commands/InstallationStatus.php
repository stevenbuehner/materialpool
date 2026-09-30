<?php

namespace App\Console\Commands;

use App\Services\Bibles\Import\BibleDataImporter;
use App\Services\Bibles\Import\OpenBibleData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

class InstallationStatus extends Command {
	protected $signature = 'materialpool:status
        {--latest-version= : Bereits geprüfte neueste stabile Release-Version}';

	protected $description = 'Installations- und Betriebsstatus ohne Änderungen anzeigen';

	public function handle(BibleDataImporter $importer): int {
		$version = $this->installedVersion();
		$latest = $this->latestVersion();
		$this->line(config('app.name').' '.$version);
		if ($latest === NULL) {
			$this->line('Update-Status: nicht abrufbar');
		} elseif ($version === 'unbekannt') {
			$this->line('Update-Status: installierte Version unbekannt; neuestes Release '.$latest);
		} elseif (version_compare($latest, $version, '>')) {
			$this->line('Update verfügbar: '.$latest);
		} else {
			$this->line('Update-Status: aktuell');
		}

		// Eine vollständige URL kann der Terminal-Linker erkennen und bleibt auch kopierbar.
		$this->line('URL: '.config('app.url'));
		$proxies = trim((string)config('trustedproxy.proxies'));
		if ($proxies !== '') {
			$this->line('Trusted Proxy: '.$proxies);
		}

		$this->newLine();
		$this->line('Qdrant');
		if (!config('context_search.enabled')) {
			$this->line('Kontextsuche in der Konfiguration deaktiviert');
		} else {
			$this->line('URL: '.config('context_search.qdrant.url'));
			$this->line('Collection-Präfix: '.config('context_search.qdrant.collection_prefix'));
			$this->line('Aktiver Alias: '.config('context_search.qdrant.active_alias'));
			$this->line('API-Key konfiguriert: '.(filled(config('context_search.qdrant.api_key')) ? 'ja' : 'nein'));
		}

		$this->newLine();
		try {
			$materials = DB::table('materials')->count();
			$resources = DB::table('resources')->count();
			$keywords = DB::table('keywords')->count();
			$bibleverses = DB::table('bibleverses')->count();
			$this->line('Datenbank: ok');
			$this->line('Materialien: '.$materials);
			$this->line('Ressourcen: '.$resources);
			$this->line('Keywords: '.$keywords);
			$this->line('Bibelstellen: '.$bibleverses);

			$this->newLine();
			$this->line('Seeds');
			$installed = $importer->installed();
			$cross = $installed[OpenBibleData::ID] ?? NULL;
			$this->line('Querverweise: '.($cross === NULL ? 'nicht installiert' : 'installiert ('.$cross->row_count.')'));
			$translations = array_filter($installed, fn (object $entry): bool => $entry->kind === 'translation');
			$this->line('Bibelübersetzungen: '.($translations === [] ? 'keine installiert' : count($translations).' installiert'));
			foreach ($translations as $entry) {
				$this->line('  - '.preg_replace('/[\x00-\x1F\x7F]/u', '', $entry->title));
			}
		} catch (Throwable) {
			$this->error('Datenbank und Seeds: Status nicht lesbar');
			return self::FAILURE;
		}

		$this->newLine();
		$this->line('Backup');
		if (!config('backup.enabled')) {
			$this->line('Nicht konfiguriert');
			return self::SUCCESS;
		}
		try {
			return $this->call('backup:list');
		} catch (Throwable) {
			$this->error('Backup-Status nicht abrufbar');
			return self::FAILURE;
		}
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
