<?php

namespace App\Console\Commands;

use App\Services\Bibles\Import\BibleDataImporter;
use App\Services\Bibles\Import\OpenBibleData;
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

		$this->newLine();
		$this->section('Qdrant');
		if (!config('context_search.enabled')) {
			$this->warningLine('Kontextsuche in der Konfiguration deaktiviert');
		} else {
			$this->line('<fg=cyan>URL:</> '.OutputFormatter::escape((string)config('context_search.qdrant.url')));
			$this->line('<fg=cyan>Collection-Präfix:</> '.OutputFormatter::escape((string)config('context_search.qdrant.collection_prefix')));
			$this->line('<fg=cyan>Aktiver Alias:</> '.OutputFormatter::escape((string)config('context_search.qdrant.active_alias')));
			$this->line('<fg=cyan>API-Key konfiguriert:</> '.(filled(config('context_search.qdrant.api_key')) ? '<fg=green>ja</>' : '<fg=yellow>nein</>'));
		}

		$this->newLine();
		$this->section('Datenbank');
		try {
			$materials = DB::table('materials')->count();
			$resources = DB::table('resources')->count();
			$keywords = DB::table('keywords')->count();
			$bibleverses = DB::table('bibleverses')->count();
			$this->successLine('Datenbank: ok');
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
