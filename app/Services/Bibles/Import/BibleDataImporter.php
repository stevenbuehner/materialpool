<?php

namespace App\Services\Bibles\Import;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class BibleDataImporter
{
    public function __construct(
        private readonly TranslationSource $source,
        private readonly ScrollmapperCsv $csv,
        private readonly OpenBibleData $crossReferences,
    ) {}

    public function installed(): array
    {
        return DB::table('bible_data_imports')->get()->keyBy('dataset_id')->all();
    }

    public function run(array $translationIds, bool $cross): array
    {
        $installed = $this->installed();
        $prepared = [];
        $files = [];
        $results = [];
        try {
            if ($translationIds !== []) {
                $catalog = $this->source->catalog();
                foreach (array_unique($translationIds) as $id) {
                    if (! isset($catalog[$id])) {
                        throw new RuntimeException("Übersetzung nicht verfügbar: {$id}");
                    }
                    $entry = $catalog[$id];
                    if ($entry['unavailable_reason'] !== null) {
                        throw new RuntimeException("Übersetzung nicht installierbar: {$id}: {$entry['unavailable_reason']}");
                    }
                    if ($entry['path'] === null) {
                        throw new RuntimeException("Übersetzung ohne CSV-Export: {$id}");
                    }
                    $current = $installed[$id] ?? null;
                    if ($current) {
                        $bibleId = DB::table('bibles')->where('uuid', $id)->value('id');
                        if (! $bibleId || DB::table('bible_contents')->where('bible_id', $bibleId)->count() !== $current->row_count) {
                            throw new RuntimeException("Installierter Versbestand ist inkonsistent: {$id}");
                        }
                    }
                    $entry = $this->source->metadata($id);
                    if ($current && $current->normalization_version === $this->source->normalizationVersion()
                        && $current->observed_blob === $entry['blob']) {
                        $metadataChanged = $current->title !== $entry['title']
                            || $current->language !== $entry['language']
                            || $current->rights !== ($entry['rights'] ?? 'nicht angegeben');
                        $results[$id] = $metadataChanged ? 'aktualisiert (Metadaten)' : 'unverändert';
                        if ($metadataChanged) {
                            $prepared[$id] = ['metadata_only' => true, 'entry' => $entry];
                        } elseif ($current->observed_revision !== $this->source->revision()) {
                            $prepared[$id] = ['observation_only' => true, 'revision' => $this->source->revision()];
                        }
                        continue;
                    }
                    $raw = tempnam(sys_get_temp_dir(), 'bible-raw-');
                    $normalized = tempnam(sys_get_temp_dir(), 'bible-norm-');
                    $files[] = $raw;
                    $files[] = $normalized;
                    $this->source->download($entry, $raw);
                    $data = $this->csv->normalize($raw, $normalized);
                    if ($current && $data['count'] < $current->row_count * config('bible_data.minimum_retained_fraction')) {
                        throw new RuntimeException("Unerwarteter Versrückgang: {$id}");
                    }
                    $prepared[$id] = compact('entry', 'data', 'normalized');
                }
            }
            if ($cross) {
                $manifest = $this->crossReferences->validate();
                $id = OpenBibleData::ID;
                $current = $installed[$id] ?? null;
                if (! $current) {
                    $legacyCount = DB::table('bibleverses_cross_ref')->count();
                    if ($legacyCount > 0 && $legacyCount < 100000) {
                        throw new RuntimeException('Unklarer bestehender Cross-Reference-Bestand ohne Importstatus.');
                    }
                }
                if ($current && DB::table('bibleverses_cross_ref')->count() !== $current->row_count) {
                    throw new RuntimeException('Installierter Cross-Reference-Bestand ist inkonsistent.');
                }
                if ($current && $manifest['row_count'] < $current->row_count * config('bible_data.minimum_retained_fraction')) {
                    throw new RuntimeException('Unerwarteter Cross-Reference-Rückgang.');
                }
                $prepared[$id] = ['manifest' => $manifest];
            }

            DB::transaction(function () use ($prepared, $installed, &$results): void {
                $ownership = DB::selectOne('SELECT IS_USED_LOCK(?) AS owner, CONNECTION_ID() AS connection_id', [config('bible_data.lock_name')]);
                if (! $ownership || (int) $ownership->owner !== (int) $ownership->connection_id) {
                    throw new RuntimeException('Importsperre wurde vor dem Schreiben verloren.');
                }
                foreach ($prepared as $id => $item) {
                    $current = $installed[$id] ?? null;
                    if ($item['observation_only'] ?? false) {
                        DB::table('bible_data_imports')->where('dataset_id', $id)->update([
                            'observed_revision' => $item['revision'], 'verified_at' => now(), 'updated_at' => now(),
                        ]);
                        continue;
                    }
                    if ($item['metadata_only'] ?? false) {
                        $entry = $item['entry'];
                        DB::table('bibles')->where('uuid', $id)->update([
                            'title' => $entry['title'], 'language' => $entry['language'],
                            'rights' => $entry['rights'] ?? 'nicht angegeben',
                            'source' => $entry['source_url'], 'updated_at' => now(),
                        ]);
                        DB::table('bible_data_imports')->where('dataset_id', $id)->update([
                            'title' => $entry['title'], 'language' => $entry['language'],
                            'rights' => $entry['rights'] ?? 'nicht angegeben',
                            'source_url' => $entry['source_url'],
                            'observed_revision' => $this->source->revision(),
                            'verified_at' => now(), 'updated_at' => now(),
                        ]);
                        continue;
                    }
                    if ($id === OpenBibleData::ID) {
                        $this->writeCrossReferences($item['manifest'], $current, $results);
                    } else {
                        $this->writeTranslation($id, $item, $current, $results);
                    }
                }
            }, 1);

            return $results;
        } finally {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    private function writeTranslation(string $id, array $item, ?object $current, array &$results): void
    {
        $entry = $item['entry'];
        $data = $item['data'];
        $same = $current && hash_equals($current->applied_hash, $data['hash'])
            && $current->normalization_version === $this->source->normalizationVersion();
        if (! $same) {
            $bible = DB::table('bibles')->where('uuid', $id)->first();
            $metadata = [
                'title' => $entry['title'], 'description' => "Scrollmapper: {$entry['code']} ({$data['scope']}, {$data['books']} Bücher, {$data['placeholders']} leere Platzhalter)",
                'creator' => 'scrollmapper/bible_databases', 'language' => $entry['language'],
                'rights' => $entry['rights'] ?? 'nicht angegeben', 'source' => $entry['source_url'],
                'updated_at' => now(),
            ];
            if ($bible) {
                DB::table('bibles')->where('id', $bible->id)->update($metadata);
                DB::table('bible_contents')->where('bible_id', $bible->id)->delete();
                $bibleId = $bible->id;
            } else {
                $bibleId = DB::table('bibles')->insertGetId($metadata + ['uuid' => $id, 'created_at' => now()]);
            }
            $batch = [];
            foreach ($this->csv->rows($item['normalized']) as $row) {
                $batch[] = ['bible_id' => $bibleId] + $row;
                if (count($batch) === 1000) {
                    DB::table('bible_contents')->insert($batch);
                    $batch = [];
                }
            }
            if ($batch !== []) {
                DB::table('bible_contents')->insert($batch);
            }
        }
        $status = [
            'kind' => 'translation', 'title' => $entry['title'], 'language' => $entry['language'],
            'rights' => $entry['rights'] ?? 'nicht angegeben', 'source_url' => $entry['source_url'],
            'source_path' => $entry['path'], 'normalization_version' => $this->source->normalizationVersion(),
            'applied_hash' => $data['hash'], 'observed_hash' => $data['hash'],
            'applied_revision' => $same ? $current->applied_revision : $this->source->revision(),
            'applied_blob' => $same ? $current->applied_blob : $entry['blob'],
            'observed_revision' => $this->source->revision(), 'observed_blob' => $entry['blob'],
            'row_count' => $data['count'], 'verified_at' => now(), 'updated_at' => now(),
        ];
        DB::table('bible_data_imports')->updateOrInsert(['dataset_id' => $id], $status + ['created_at' => $current->created_at ?? now()]);
        $results[$id] = $same ? 'unverändert' : ($current ? 'aktualisiert' : 'importiert');
    }

    private function writeCrossReferences(array $manifest, ?object $current, array &$results): void
    {
        $id = OpenBibleData::ID;
        $same = $current && $current->normalization_version === OpenBibleData::VERSION
            && hash_equals($current->applied_hash, $manifest['sha256']);
        if (! $same) {
            DB::table('bibleverses_cross_ref')->delete();
            $batch = [];
            foreach ($this->crossReferences->rows() as [$source, $relevance, $from, $to]) {
                $batch[] = ['source' => $source, 'relevance' => $relevance, 'target_from' => $from, 'target_to' => $to];
                if (count($batch) === 2000) {
                    DB::table('bibleverses_cross_ref')->insert($batch);
                    $batch = [];
                }
            }
            if ($batch !== []) {
                DB::table('bibleverses_cross_ref')->insert($batch);
            }
        }
        DB::table('bible_data_imports')->updateOrInsert(['dataset_id' => $id], [
            'kind' => 'cross_references', 'title' => 'OpenBible Cross References', 'language' => null,
            'rights' => $manifest['rights'], 'source_url' => $manifest['source_url'],
            'source_path' => OpenBibleData::PAYLOAD,
            'applied_hash' => $manifest['sha256'], 'normalization_version' => OpenBibleData::VERSION,
            'observed_hash' => $manifest['sha256'],
            'applied_revision' => $same ? $current->applied_revision : $manifest['source_date'],
            'applied_blob' => $same ? $current->applied_blob : $manifest['sha256'],
            'observed_revision' => $manifest['source_date'], 'observed_blob' => $manifest['sha256'],
            'row_count' => $manifest['row_count'], 'verified_at' => now(),
            'created_at' => $current->created_at ?? now(), 'updated_at' => now(),
        ]);
        $results[$id] = $same ? 'unverändert' : ($current ? 'aktualisiert' : 'importiert');
    }
}
