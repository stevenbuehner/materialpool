<?php

namespace App\Services\ContextSearch;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\File;
use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\Text;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

final class EvaluationDatasetService
{
    private const DISK = 'context_search_evaluation';
    private const MANIFEST_BATCH_SIZE = 100;

    /** @param array<int, int> $materialIds @param array<int, int> $resourceIds */
    public function freeze(string $purpose, array $materialIds, array $resourceIds, bool $includePrivate, ?string $privateReason): ContextSearchEvaluationDataset
    {
        if (! in_array($purpose, ContextSearchEvaluationDataset::PURPOSES, true)) {
            throw new \InvalidArgumentException('Der Zweck des Evaluationsdatensatzes ist ungültig.');
        }

        if ($materialIds === [] && $resourceIds === []) {
            throw new \InvalidArgumentException('Mindestens eine Material- oder Ressourcen-ID muss ausgewählt werden.');
        }

        if ($includePrivate && blank($privateReason)) {
            throw new \InvalidArgumentException('Für private Inhalte ist eine Zweckbegründung erforderlich.');
        }

        $resources = $this->selectedResources($materialIds, $resourceIds);
        if ($resources->isEmpty()) {
            throw new \RuntimeException('Die Auswahl enthält keine PDF- oder Textressource.');
        }

        $materials = $this->selectedMaterials($resources);
        $hasPrivateContent = $resources->contains(fn (Resource $resource): bool => ! $resource->is_public)
            || $materials->contains(fn (Material $material): bool => ! $material->is_public);

        if ($hasPrivateContent && ! $includePrivate) {
            throw new \RuntimeException('Die Auswahl enthält private Inhalte. Verwende die explizite Einschlussoption mit Begründung.');
        }

        $datasetId = (string) Str::uuid();
        $manifest = $this->manifest($datasetId, $purpose, $materials, $resources, $includePrivate, $privateReason);
        $manifestHash = $manifest['manifest_hash'];

        return DB::transaction(function () use ($datasetId, $purpose, $hasPrivateContent, $privateReason, $manifest, $manifestHash): ContextSearchEvaluationDataset {
            $this->assertManifestMembersAreFree($manifest);
            $dataset = new ContextSearchEvaluationDataset();
            $dataset->forceFill([
                'id' => $datasetId,
                'purpose' => $purpose,
                'status' => ContextSearchEvaluationDataset::STATUS_FROZEN,
                'includes_private' => $hasPrivateContent,
                'private_reason' => $hasPrivateContent ? $privateReason : null,
                'manifest' => $manifest,
                'manifest_hash' => $manifestHash,
                'material_count' => count($manifest['materials']),
                'resource_count' => count($manifest['resources']),
                'frozen_at' => now(),
            ])->save();
            $this->storeManifestMembers($dataset, $manifest);

            return $dataset;
        });
    }

    /** @param null|callable(string, int, int): void $onProgress */
    public function export(ContextSearchEvaluationDataset $dataset, ?callable $onProgress = null): ContextSearchEvaluationDataset
    {
        $manifest = $dataset->manifest;
        if ($this->hash($manifest) !== $dataset->manifest_hash) {
            throw new RuntimeException('Das gespeicherte Manifest stimmt nicht mit seinem Hash überein.');
        }

        if (! in_array($dataset->purpose, ContextSearchEvaluationDataset::PURPOSES, true) || $dataset->version < 1) {
            throw new RuntimeException('Zweck oder Version des Evaluationsdatensatzes ist ungültig.');
        }

        $relativePath = sprintf('exports/%s-v%d-%s.zip', $dataset->purpose, $dataset->version, $dataset->getKey());
        $absolutePath = Storage::disk(self::DISK)->path($relativePath);
        $directory = dirname($absolutePath);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Die private Exportablage konnte nicht angelegt werden.');
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Das Evaluationsarchiv konnte nicht erstellt werden.');
        }
        if ($onProgress !== null) {
            $zip->registerProgressCallback(0.01, function (float $fraction) use ($onProgress): void {
                $this->reportProgress($onProgress, 'Archiv schreiben', (int) round(max(0, min(1, $fraction)) * 1000), 1000);
            });
        }

        $closed = false;
        try {
            $zip->addFromString('manifest.json', $this->json($manifest));
            $total = count($manifest['resources']) + 1;
            $this->reportProgress($onProgress, 'Archiv vorbereiten', 1, $total);
            $completed = 1;
            foreach ($manifest['resources'] as $entry) {
                $this->addResource($zip, $entry);
                $this->reportProgress($onProgress, 'Archiv vorbereiten', ++$completed, $total);
            }
            $this->reportProgress($onProgress, 'Archiv schreiben', 0, 1000);
        } finally {
            $closed = $zip->close();
        }
        if (! $closed) {
            throw new RuntimeException('Das Evaluationsarchiv konnte nicht vollständig geschrieben werden.');
        }
        $this->reportProgress($onProgress, 'Archiv schreiben', 1000, 1000);

        $dataset->update([
            'status' => ContextSearchEvaluationDataset::STATUS_EXPORTED,
            'archive_path' => $relativePath,
            'archive_hash' => $this->archiveHash($absolutePath, $onProgress),
            'exported_at' => now(),
        ]);

        return $dataset->fresh();
    }

    /**
     * Freezes a fully curated draft. The membership ledger is already immutable
     * at this point and supplies the exact source set for the manifest.
     *
     * @param null|callable(string): void $onProgress
     */
    public function freezeCurated(ContextSearchEvaluationDataset $dataset, ?callable $onProgress = null): ContextSearchEvaluationDataset
    {
        if ($dataset->status !== ContextSearchEvaluationDataset::STATUS_READY) {
            throw new RuntimeException('Nur ein vollständiger Entwurf darf eingefroren werden.');
        }

        return DB::transaction(function () use ($dataset, $onProgress): ContextSearchEvaluationDataset {
            $dataset = ContextSearchEvaluationDataset::query()->lockForUpdate()->findOrFail($dataset->getKey());
            if ($dataset->status !== ContextSearchEvaluationDataset::STATUS_READY) {
                throw new RuntimeException('Der Entwurf wurde zwischenzeitlich geändert.');
            }
            $resources = $this->manifestMembers($dataset, ContextSearchEvaluationDatasetMember::TYPE_RESOURCE);
            $materials = $this->manifestMembers($dataset, ContextSearchEvaluationDatasetMember::TYPE_MATERIAL);
            $manifest = $this->manifest($dataset->getKey(), $dataset->purpose, $materials, $resources, $dataset->includes_private, $dataset->private_reason, $onProgress);
            if ($manifest['resources'] === [] || $manifest['materials'] === []) {
                throw new RuntimeException('Der Entwurf enthält keine vollständige, exportierbare Auswahl.');
            }
            $dataset->forceFill([
                'status' => ContextSearchEvaluationDataset::STATUS_FROZEN,
                'manifest' => $manifest,
                'manifest_hash' => $manifest['manifest_hash'],
                'material_count' => count($manifest['materials']),
                'resource_count' => count($manifest['resources']),
                'frozen_at' => now(),
            ])->save();
            return $dataset;
        });
    }

    /** @return \Generator<int, Resource|Material> */
    private function manifestMembers(ContextSearchEvaluationDataset $dataset, string $type): \Generator
    {
        $members = DB::table('context_search_evaluation_dataset_members')
            ->select('member_id')
            ->where('dataset_id', $dataset->getKey())
            ->where('member_type', $type)
            ->lazyById(self::MANIFEST_BATCH_SIZE, 'member_id');
        $query = $type === ContextSearchEvaluationDatasetMember::TYPE_RESOURCE
            ? $this->resourceManifestQuery()
            : $this->materialManifestQuery();
        $table = $type === ContextSearchEvaluationDatasetMember::TYPE_RESOURCE ? 'resources' : 'materials';

        foreach ($members->chunk(self::MANIFEST_BATCH_SIZE) as $batch) {
            foreach ((clone $query)->whereIn($table.'.id', $batch->pluck('member_id'))->get()->sortBy('id') as $model) {
                yield $model;
            }
        }
    }

    /**
     * @param null|callable(string, int, int): void $onProgress
     * @return array{manifest_hash: string, archive_hash: string}
     */
    public function verify(string $relativePath, ?callable $onProgress = null): array
    {
        $this->assertSafeArchivePath($relativePath);
        $absolutePath = Storage::disk(self::DISK)->path($relativePath);
        if (! is_file($absolutePath)) {
            throw new RuntimeException('Das Evaluationsarchiv wurde nicht gefunden.');
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            throw new RuntimeException('Das Evaluationsarchiv kann nicht gelesen werden.');
        }

        try {
            $json = $zip->getFromName('manifest.json');
            $manifest = is_string($json) ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : null;
            if (! is_array($manifest) || ! isset($manifest['manifest_hash'])) {
                throw new RuntimeException('Das Archiv enthält kein gültiges Manifest.');
            }
            $manifestHash = $manifest['manifest_hash'];
            unset($manifest['manifest_hash']);
            if (! hash_equals($manifestHash, $this->hash($manifest))) {
                throw new RuntimeException('Die Manifest-Prüfsumme ist ungültig.');
            }
            $total = count($manifest['resources']);
            $this->reportProgress($onProgress, 'Archiveinträge prüfen', 0, max(1, $total));
            foreach ($manifest['resources'] as $index => $resource) {
                if (! isset($resource['archive_path']) || $zip->locateName($resource['archive_path']) === false) {
                    throw new RuntimeException('Eine Quelldatei fehlt im Archiv.');
                }
                $this->reportProgress($onProgress, 'Archiveinträge prüfen', $index + 1, $total);
            }
            if ($total === 0) {
                $this->reportProgress($onProgress, 'Archiveinträge prüfen', 1, 1);
            }
        } finally {
            $zip->close();
        }

        return ['manifest_hash' => $manifestHash, 'archive_hash' => $this->archiveHash($absolutePath, $onProgress)];
    }

    /** @param null|callable(string, int, int): void $onProgress */
    public function import(string $relativePath, ?callable $onProgress = null): ContextSearchEvaluationDataset
    {
        $missingRequirements = [];
        if (app()->isProduction()) {
            $missingRequirements[] = 'APP_ENV=production ist gesperrt. Der Import muss auf einer getrennten Evaluationsumgebung mit eigener Datenbank und privater Dateiablage erfolgen.';
        }
        if (! config('context_search.evaluation.import_enabled')) {
            $missingRequirements[] = 'Die wirksame Konfiguration context_search.evaluation.import_enabled ist deaktiviert. Nach Freigabe der Evaluationsumgebung dort CONTEXT_SEARCH_EVALUATION_IMPORT_ENABLED=true in der .env setzen und bei gecachter Konfiguration ./vendor/bin/sail artisan config:clear ausführen.';
        }
        if ($missingRequirements !== []) {
            throw new RuntimeException('Der Import ist ausschließlich in einer explizit freigegebenen Evaluationsumgebung erlaubt. '.implode(' ', $missingRequirements));
        }

        $this->verify($relativePath, $onProgress);
        $absolutePath = Storage::disk(self::DISK)->path($relativePath);
        $zip = new ZipArchive();
        $zip->open($absolutePath);

        try {
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true, 512, JSON_THROW_ON_ERROR);
            $dataset = ContextSearchEvaluationDataset::query()->find($manifest['dataset_id']);
            if ($dataset !== null) {
                if (! hash_equals($dataset->manifest_hash, $manifest['manifest_hash'])) {
                    throw new RuntimeException('Die Datensatz-ID ist bereits mit einem anderen Manifest importiert.');
                }

                return $dataset;
            }

            $storedFiles = [];
            try {
                $dataset = DB::transaction(function () use ($manifest, $zip, &$storedFiles, $onProgress): ContextSearchEvaluationDataset {
                    $user = User::query()->firstOrCreate(
                        ['email' => 'context-search-evaluation@local.invalid'],
                        ['name' => 'Context search evaluation import', 'password' => Hash::make(Str::random(48))],
                    );
                    $materials = [];
                    $materialMembers = [];
                    $materialTotal = count($manifest['materials']);
                    $this->reportProgress($onProgress, 'Materialien importieren', 0, max(1, $materialTotal));
                    $importedMaterials = 0;
                    foreach ($manifest['materials'] as $entry) {
                        $material = new Material();
                        $material->forceFill([
                            'title' => $entry['title'], 'description' => $entry['description'], 'rating' => $entry['rating'],
                            'from_bot' => $entry['from_bot'], 'is_public' => $entry['is_public'], 'flag' => $entry['flag'],
                            'icon_of_bundle' => $entry['icon_of_bundle'], 'created_by' => $user->getKey(), 'modified_by' => $user->getKey(),
                        ])->saveQuietly();
                        $materials[$entry['source_id']] = $material;
                        $materialMembers[] = ['source_id' => $material->id];
                        $this->reportProgress($onProgress, 'Materialien importieren', ++$importedMaterials, $materialTotal);
                    }
                    if ($materialTotal === 0) {
                        $this->reportProgress($onProgress, 'Materialien importieren', 1, 1);
                    }
                    $resourceTotal = count($manifest['resources']);
                    $this->reportProgress($onProgress, 'Ressourcen importieren', 0, max(1, $resourceTotal));
                    $importedResources = 0;
                    $resourceMembers = [];
                    foreach ($manifest['resources'] as $entry) {
                        $resource = $entry['type'] === 'text' ? new Text() : new PdfFile();
                        $resource->forceFill([
                            'type' => $entry['type'], 'notes' => $entry['notes'], 'is_public' => $entry['is_public'],
                            'created_by' => $user->getKey(), 'content_hash' => $entry['content_hash'],
                        ]);
                        if ($resource instanceof Text) {
                            $resource->setContent($entry['text']);
                        } else {
                            $resource->setOriginalFilenameAttribute($entry['original_filename'] ?: 'source.pdf');
                            $target = 'evaluation/'.$manifest['dataset_id'].'/pdf/'.$entry['source_id'].'.pdf';
                            $stream = $zip->getStream($entry['archive_path']);
                            if (! is_resource($stream)) {
                                throw new RuntimeException('Eine PDF-Quelldatei kann nicht aus dem Archiv gelesen werden.');
                            }
                            Storage::disk('resources')->writeStream($target, $stream);
                            fclose($stream);
                            $storedFiles[] = $target;
                            $resource->setLocalStorageAndPath('resources', $target);
                        }
                        $resource->saveQuietly();
                        $hasMaterial = false;
                        foreach ($entry['material_ids'] as $sourceMaterialId) {
                            if (isset($materials[$sourceMaterialId])) {
                                $materials[$sourceMaterialId]->resources()->attach($resource->getKey());
                                $hasMaterial = true;
                            }
                        }
                        if ($hasMaterial) {
                            $resourceMembers[] = ['source_id' => $resource->id, 'revision_hash' => $this->revisionHash($resource)];
                        }
                        $this->reportProgress($onProgress, 'Ressourcen importieren', ++$importedResources, $resourceTotal);
                    }
                    if ($resourceTotal === 0) {
                        $this->reportProgress($onProgress, 'Ressourcen importieren', 1, 1);
                    }

                    $dataset = new ContextSearchEvaluationDataset();
                    $dataset->forceFill([
                        'id' => $manifest['dataset_id'], 'purpose' => $manifest['purpose'], 'status' => ContextSearchEvaluationDataset::STATUS_FROZEN,
                        'includes_private' => $manifest['includes_private'], 'private_reason' => $manifest['private_reason'], 'manifest' => $manifest,
                        'manifest_hash' => $manifest['manifest_hash'], 'material_count' => count($manifest['materials']),
                        'resource_count' => count($manifest['resources']), 'frozen_at' => $manifest['frozen_at'],
                    ])->save();
                    $this->storeManifestMembers($dataset, [
                        'materials' => $materialMembers,
                        'resources' => $resourceMembers,
                    ], $onProgress);

                    return $dataset;
                });
            } catch (\Throwable $exception) {
                foreach ($storedFiles as $file) {
                    Storage::disk('resources')->delete($file);
                }
                throw $exception;
            }
        } finally {
            $zip->close();
        }

        return $dataset;
    }

    /** @param array<int, int> $materialIds @param array<int, int> $resourceIds @return Collection<int, Resource> */
    private function selectedResources(array $materialIds, array $resourceIds): Collection
    {
        return $this->resourceManifestQuery()
            ->whereIn('type', ['pdf', 'text'])
            ->where(function ($query) use ($materialIds, $resourceIds): void {
                if ($resourceIds !== []) {
                    $query->whereIn('resources.id', $resourceIds);
                }
                if ($materialIds !== []) {
                    $query->{$resourceIds === [] ? 'whereHas' : 'orWhereHas'}('materials', fn ($materials) => $materials->whereIn('materials.id', $materialIds));
                }
            })
            ->orderBy('resources.id')
            ->get();
    }

    /** @param Collection<int, Resource> $resources @return Collection<int, Material> */
    private function selectedMaterials(Collection $resources): Collection
    {
        return $this->materialManifestQuery()
            ->whereIn('id', $resources->flatMap(fn (Resource $resource) => $resource->materials->modelKeys())->unique()->sort()->values())
            ->orderBy('id')
            ->get();
    }

    /** @return Builder<Resource> */
    private function resourceManifestQuery(): Builder
    {
        return Resource::query()->withoutGlobalScopes()
            ->with(['materials' => fn ($query) => $query->withoutGlobalScopes()->orderBy('materials.id')]);
    }

    /** @return Builder<Material> */
    private function materialManifestQuery(): Builder
    {
        return Material::query()->withoutGlobalScopes()
            ->with(['keywords' => fn ($query) => $query->orderBy('keywords.id'), 'bibleverses' => fn ($query) => $query->orderBy('bibleverses.id')]);
    }

    /**
     * @param iterable<Material> $materials
     * @param iterable<Resource> $resources
     * @param null|callable(string): void $onProgress
     */
    private function manifest(string $datasetId, string $purpose, iterable $materials, iterable $resources, bool $includePrivate, ?string $privateReason, ?callable $onProgress = null): array
    {
        $resourceEntries = [];
        foreach ($resources as $resource) {
            $revisionHash = $this->revisionHash($resource);
            $isText = $resource instanceof Text;

            $resourceEntries[] = [
                'source_id' => $resource->getKey(),
                'type' => $resource->type,
                'is_public' => (bool) $resource->is_public,
                'notes' => (string) $resource->notes,
                'revision_hash' => $revisionHash,
                'content_hash' => $resource->content_hash,
                'filesize' => $resource->filesize,
                'created_at' => optional($resource->created_at)?->toAtomString(),
                'updated_at' => optional($resource->updated_at)?->toAtomString(),
                'archive_path' => $isText ? 'texts/'.$resource->getKey().'.txt' : 'files/'.$resource->getKey().'.pdf',
                'original_filename' => $resource instanceof File ? $resource->original_filename : null,
                'text' => $isText ? (string) $resource->content : null,
                'material_ids' => $resource->materials->modelKeys(),
            ];
            if ($onProgress !== null) {
                $onProgress('Ressourcen');
            }
        }

        $materialEntries = [];
        foreach ($materials as $material) {
            $materialEntries[] = [
                'source_id' => $material->getKey(), 'title' => $material->title, 'description' => $material->description,
                'rating' => $material->rating, 'from_bot' => (bool) $material->from_bot, 'is_public' => (bool) $material->is_public,
                'flag' => $material->flag, 'icon_of_bundle' => $material->icon_of_bundle,
                'created_at' => optional($material->created_at)?->toAtomString(), 'updated_at' => optional($material->updated_at)?->toAtomString(),
                'keywords' => $material->keywords->map(fn ($keyword): array => ['title' => $keyword->title, 'type' => $keyword->type, 'relevance' => $keyword->pivot->relevance])->values()->all(),
                'bibleverses' => $material->bibleverses->map(fn ($verse): array => ['source_id' => $verse->getKey(), 'relevance' => $verse->pivot->relevance])->values()->all(),
            ];
            if ($onProgress !== null) {
                $onProgress('Materialien');
            }
        }

        $manifest = [
            'format' => 'materialpool-context-search-evaluation-v1',
            'dataset_id' => $datasetId,
            'purpose' => $purpose,
            'frozen_at' => now()->toAtomString(),
            'includes_private' => $includePrivate,
            'private_reason' => $includePrivate ? $privateReason : null,
            'materials' => $materialEntries,
            'resources' => $resourceEntries,
        ];

        $manifest['manifest_hash'] = $this->hash($manifest);

        return $manifest;
    }

    private function addResource(ZipArchive $zip, array $entry): void
    {
        if ($entry['type'] === 'text') {
            $zip->addFromString($entry['archive_path'], (string) $entry['text']);
            return;
        }

        $resource = Resource::query()->withoutGlobalScopes()->find($entry['source_id']);
        if (! $resource instanceof File || ! $resource->hasLocalFile() || ! $resource->localFileExists()) {
            throw new RuntimeException('Eine eingefrorene PDF-Quelldatei ist nicht mehr verfügbar.');
        }
        $path = $resource->getAbsoluteLocalPath();
        if ($path === false || ! $zip->addFile($path, $entry['archive_path'])) {
            throw new RuntimeException('Eine PDF-Quelldatei konnte nicht dem Archiv hinzugefügt werden.');
        }
    }

    private function revisionHash(Resource $resource): string
    {
        if ($resource instanceof Text) {
            return hash('sha256', (string) $resource->content);
        }
        if (! $resource instanceof File || ! $resource->hasLocalFile() || ! $resource->localFileExists()) {
            throw new RuntimeException('Eine PDF-Quelldatei ist nicht verfügbar.');
        }
        $stream = $resource->getLocalFileStream();
        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        fclose($stream);

        return hash_final($context);
    }

    /** @param array<string, mixed> $manifest */
    private function assertManifestMembersAreFree(array $manifest): void
    {
        $materialIds = collect($manifest['materials'])->pluck('source_id')->map(fn ($id) => (int) $id)->all();
        $resourceIds = collect($manifest['resources'])->pluck('source_id')->map(fn ($id) => (int) $id)->all();
        sort($materialIds);
        sort($resourceIds);
        Material::query()->withoutGlobalScopes()->whereIn('id', $materialIds)->orderBy('id')->lockForUpdate()->get(['id']);
        Resource::query()->withoutGlobalScopes()->whereIn('id', $resourceIds)->orderBy('id')->lockForUpdate()->get(['id']);
        $allowedPurposes = EvaluationDatasetOverlapPolicy::allowedPurposes($manifest['purpose']);
        $exists = ContextSearchEvaluationDatasetMember::query()->where(function ($query) use ($materialIds, $resourceIds): void {
            $query->where(fn ($members) => $members->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_MATERIAL)->whereIn('member_id', $materialIds))
                ->orWhere(fn ($members) => $members->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_RESOURCE)->whereIn('member_id', $resourceIds));
        })->whereHas('dataset', fn ($dataset) => $dataset->whereNotIn('purpose', $allowedPurposes))->exists();
        if ($exists) {
            throw new RuntimeException('Mindestens ein Material oder eine Ressource gehört bereits dauerhaft zu einem anderen Evaluationsdatensatz.');
        }
    }

    /**
     * @param array<string, mixed> $manifest
     * @param null|callable(string, int, int): void $onProgress
     */
    private function storeManifestMembers(ContextSearchEvaluationDataset $dataset, array $manifest, ?callable $onProgress = null): void
    {
        $total = count($manifest['materials']) + count($manifest['resources']);
        $this->reportProgress($onProgress, 'Mitgliedschaften speichern', 0, max(1, $total));
        $completed = 0;
        foreach ($manifest['materials'] as $material) {
            ContextSearchEvaluationDatasetMember::query()->create(['dataset_id' => $dataset->id, 'member_type' => ContextSearchEvaluationDatasetMember::TYPE_MATERIAL, 'member_id' => $material['source_id']]);
            $this->reportProgress($onProgress, 'Mitgliedschaften speichern', ++$completed, $total);
        }
        foreach ($manifest['resources'] as $resource) {
            ContextSearchEvaluationDatasetMember::query()->create(['dataset_id' => $dataset->id, 'member_type' => ContextSearchEvaluationDatasetMember::TYPE_RESOURCE, 'member_id' => $resource['source_id'], 'source_revision_hash' => $resource['revision_hash'] ?? null]);
            $this->reportProgress($onProgress, 'Mitgliedschaften speichern', ++$completed, $total);
        }
        if ($total === 0) {
            $this->reportProgress($onProgress, 'Mitgliedschaften speichern', 1, 1);
        }
    }

    /** @param null|callable(string, int, int): void $onProgress */
    private function archiveHash(string $path, ?callable $onProgress): string
    {
        if ($onProgress === null) {
            $hash = hash_file('sha256', $path);
            if ($hash === false) {
                throw new RuntimeException('Die Archiv-Prüfsumme konnte nicht berechnet werden.');
            }

            return $hash;
        }

        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException('Das Evaluationsarchiv kann nicht gelesen werden.');
        }
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Das Evaluationsarchiv kann nicht gelesen werden.');
        }

        $context = hash_init('sha256');
        $read = 0;
        $this->reportProgress($onProgress, 'Archiv-Prüfsumme berechnen', 0, max(1, $size));
        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 1024 * 1024);
                if ($chunk === false || ($chunk === '' && ! feof($stream))) {
                    throw new RuntimeException('Das Evaluationsarchiv kann nicht vollständig gelesen werden.');
                }
                hash_update($context, $chunk);
                $read += strlen($chunk);
                $this->reportProgress($onProgress, 'Archiv-Prüfsumme berechnen', $read, max(1, $size));
            }
        } finally {
            fclose($stream);
        }

        if ($size === 0) {
            $this->reportProgress($onProgress, 'Archiv-Prüfsumme berechnen', 1, 1);
        } elseif ($read !== $size) {
            throw new RuntimeException('Das Evaluationsarchiv hat sich während der Prüfsummenberechnung geändert.');
        }

        return hash_final($context);
    }

    /** @param null|callable(string, int, int): void $onProgress */
    private function reportProgress(?callable $onProgress, string $phase, int $current, int $total): void
    {
        if ($onProgress !== null) {
            $onProgress($phase, $current, max(1, $total));
        }
    }

    private function hash(array $manifest): string
    {
        $copy = $manifest;
        unset($copy['manifest_hash']);

        return hash('sha256', $this->json($copy));
    }

    private function json(array $value): string
    {
        return json_encode($this->canonicalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function canonicalize(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalize($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    private function assertSafeArchivePath(string $relativePath): void
    {
        if ($relativePath === '' || str_contains($relativePath, '..') || str_starts_with($relativePath, '/') || ! str_ends_with($relativePath, '.zip')) {
            throw new \InvalidArgumentException('Der Archivpfad ist ungültig.');
        }
    }
}
