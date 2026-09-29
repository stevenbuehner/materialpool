<?php

namespace App\Console\Commands;

use App\Services\Bibles\Import\BibleDataImporter;
use App\Services\Bibles\Import\OpenBibleData;
use App\Services\Bibles\Import\ScrollmapperSource;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multisearch;

class ImportBibleData extends Command
{
    protected $signature = 'bible:import
        {--update-translations : Installierte Scrollmapper-Übersetzungen prüfen}
        {--update-cross-references : Installierte Cross References prüfen}
        {--cross-references : Cross References aus dem Release installieren}
        {--translation=* : Stabile Scrollmapper-ID; mehrfach zulässig}';

    protected $description = 'Bibelübersetzungen und Cross References installieren oder aktualisieren';

    public function handle(BibleDataImporter $importer, ScrollmapperSource $source, OpenBibleData $crossData): int
    {
        $this->warn(__('bible.rights_notice'));
        $results = [];
        $catalogCount = null;
        $locked = false;
        try {
            $locked = (int) (DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [config('bible_data.lock_name')])->acquired ?? 0) === 1;
            if (! $locked) {
                throw new RuntimeException('Ein Bibeldatenimport läuft bereits.');
            }
            $source->refresh();
            $installed = $importer->installed();
            $nonInteractive = (bool) $this->option('no-interaction');
            $options = (bool) $this->option('update-translations') || (bool) $this->option('update-cross-references')
                || (bool) $this->option('cross-references') || $this->option('translation') !== [];
            if (! $nonInteractive && $options) {
                throw new RuntimeException('Aktionsoptionen benötigen --no-interaction.');
            }
            if (! $nonInteractive) {
                if (! stream_isatty(STDIN)) {
                    throw new RuntimeException('Die interaktive Auswahl benötigt eine TTY.');
                }
                $catalog = $source->catalog(true);
                $catalogCount = count($catalog);
                $ordered = $this->orderedCatalog($catalog);
                $labels = [];
                foreach ($ordered as $id => $entry) {
                    if ($entry['path'] === null || $entry['unavailable_reason'] !== null) {
                        $reason = $entry['unavailable_reason'] ?? 'CSV-Export fehlt';
                        $brief = str_starts_with($reason, 'Nicht abbildbare Bücher:')
                            ? 'zusätzliche Bücher außerhalb des Modells (z. B. '.trim(explode(',', substr($reason, strlen('Nicht abbildbare Bücher:')))[0]).')'
                            : $reason;
                        $this->warn("Nicht installierbar: {$id} — {$brief}");
                        continue;
                    }
                    $state = isset($installed[$id]) ? ' [installiert]' : '';
                    $suggestion = ($entry['suggested'] ?? false) ? '[Vorschlag] ' : '';
                    $rights = $entry['rights'] ?? 'nicht angegeben';
                    $scope = $entry['scope'] ?? 'wird beim Import geprüft';
                    $shortRights = mb_strimwidth($rights, 0, 28, '…');
                    $labels[$id] = "{$suggestion}{$entry['code']} · {$entry['language']} · {$scope} · {$shortRights}{$state} · {$entry['title']}";
                }
                $translations = multisearch(
                    label: __('bible.select_translations'),
                    options: fn (?string $query): array => array_filter($labels, fn (string $label): bool => mb_stripos($label, (string) $query) !== false),
                    required: false,
                    hint: 'Leertaste wählt aus; keine Auswahl lässt bestehende Texte unverändert.',
                    info: fn (?string $id): string => $id && isset($ordered[$id])
                        ? $ordered[$id]['source_url'] : 'Quelle: github.com/scrollmapper/bible_databases',
                );
                $cross = false;
                if (is_file($crossData->directory().'/'.OpenBibleData::MANIFEST)) {
                    $crossData->validate();
                    $cross = confirm(__('bible.select_cross_references'), false, 'Ja', 'Nein');
                }
            } else {
                $translations = array_values(array_unique($this->option('translation')));
                if ($this->option('update-translations')) {
                    foreach ($installed as $id => $status) {
                        if ($status->kind === 'translation' && str_starts_with($id, 'scrollmapper:')) {
                            $translations[] = $id;
                        }
                    }
                }
                $translations = array_values(array_unique($translations));
                $cross = (bool) $this->option('cross-references')
                    || ((bool) $this->option('update-cross-references') && isset($installed[OpenBibleData::ID]));
                if ($this->option('update-cross-references') && ! isset($installed[OpenBibleData::ID])) {
                    $legacyCount = DB::table('bibleverses_cross_ref')->count();
                    if ($legacyCount >= 100000) {
                        $cross = true;
                    } elseif ($legacyCount > 0) {
                        throw new RuntimeException('Unklarer bestehender Cross-Reference-Bestand ohne Importstatus.');
                    }
                }
            }
            $results = $importer->run($translations, $cross);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $message = $exception instanceof QueryException
                ? 'Datenbankfehler beim atomaren Import; bisherige Daten bleiben erhalten.'
                : $exception->getMessage();
            $this->error('Bibeldatenimport fehlgeschlagen: '.$message);

            return self::FAILURE;
        } finally {
            $this->status($importer, $results, $catalogCount);
            if ($locked) {
                try {
                    DB::selectOne('SELECT RELEASE_LOCK(?)', [config('bible_data.lock_name')]);
                } catch (Throwable $exception) {
                    $this->warn('Die Datenbankverbindung zur Importsperre wurde beendet.');
                }
            }
        }
    }

    private function orderedCatalog(array $catalog): array
    {
        uasort($catalog, fn (array $a, array $b): int => [$a['language'], $a['title']] <=> [$b['language'], $b['title']]);
        $ordered = [];
        foreach (config('bible_data.suggested_translations') as $id) {
            if (isset($catalog[$id])) {
                $ordered[$id] = $catalog[$id];
                $ordered[$id]['suggested'] = true;
                unset($catalog[$id]);
            } else {
                $this->warn("Vorschlag nicht im Scrollmapper-Katalog: {$id}");
            }
        }

        return $ordered + $catalog;
    }

    private function status(BibleDataImporter $importer, array $results, ?int $catalogCount): void
    {
        $this->newLine();
        $this->info('Bibeldatenstatus'.($catalogCount === null ? '' : " — {$catalogCount} Katalogeinträge"));
        try {
            $rows = [];
            foreach ($importer->installed() as $id => $state) {
                $rows[] = [
                    $id, $state->title, $results[$id] ?? 'installiert', $state->row_count,
                    $state->applied_hash ?? 'unbekannt',
                    $state->observed_hash ?? 'unbekannt', $state->observed_revision ?? 'unbekannt',
                    $state->observed_blob ?? 'unbekannt',
                ];
                $details[$id] = "Quelle: {$state->source_url}; Rechte: ".($state->rights ?? 'nicht angegeben');
            }
            foreach ($results as $id => $result) {
                if (! in_array($id, array_column($rows, 0), true)) {
                    $rows[] = [$id, '', $result, 0, '—', '—', '—', '—'];
                }
            }
            $managed = array_keys($importer->installed());
            foreach (DB::table('bibles')->whereNotIn('uuid', $managed)->get(['uuid', 'title', 'id']) as $bible) {
                $rows[] = [$bible->uuid, $bible->title, 'bestehend, nicht verwaltet',
                    DB::table('bible_contents')->where('bible_id', $bible->id)->count(), '—', '—', '—', '—'];
            }
            if (! in_array(OpenBibleData::ID, $managed, true)) {
                $legacyCrossCount = DB::table('bibleverses_cross_ref')->count();
                if ($legacyCrossCount > 0) {
                    $rows[] = [OpenBibleData::ID, 'OpenBible Cross References', 'bestehend, nicht verwaltet', $legacyCrossCount, '—', '—', '—', '—'];
                }
            }
            if ($rows === []) {
                $this->line('Keine Bibeldaten installiert.');
                return;
            }
            $this->table(['ID', 'Titel', 'Aktion', 'Zeilen', 'Angewendet SHA-256', 'Beobachtet SHA-256', 'Quellrevision', 'Blob/Release'], $rows);
            foreach ($details ?? [] as $id => $detail) {
                $this->line("{$id}: {$detail}");
            }
        } catch (Throwable $exception) {
            $this->warn('Lokaler Status nicht lesbar: '.$exception->getMessage());
        }
    }
}
