<?php

namespace App\Console\Commands;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\Resource;
use App\Services\ContextSearch\EvaluationDatasetCurationService;
use App\Services\ContextSearch\EvaluationDatasetOverlapPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ReconcileContextSearchEvaluationDatasetMembers extends Command
{
    private const DATASET_BATCH_SIZE = 100;
    private const MEMBER_BATCH_SIZE = 500;

    protected $signature = 'context-search:dataset:reconcile-memberships {--apply : Bestätigt das Anlegen konfliktfreier Mitgliedschaften}';

    protected $description = 'Prüft eingefrorene Alt-Datensätze auf fehlende, zweckkonforme Mitgliedschaften; Standard ist ausschließlich lesend.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->renderCapacityOverview();
        $affected = 0;
        $datasetIds = ContextSearchEvaluationDataset::query()
            ->whereIn('status', [ContextSearchEvaluationDataset::STATUS_FROZEN, ContextSearchEvaluationDataset::STATUS_EXPORTED])
            ->whereDoesntHave('members')
            ->select('id')
            ->lazyById(self::DATASET_BATCH_SIZE);

        foreach ($datasetIds as $datasetId) {
            $dataset = ContextSearchEvaluationDataset::query()->findOrFail($datasetId->id);
            if ($dataset->members()->exists()) continue;
            $affected++;
            $materials = collect($dataset->manifest['materials'] ?? [])->pluck('source_id')->filter()->map(fn ($id) => (int) $id)->all();
            $resources = collect($dataset->manifest['resources'] ?? [])->filter(fn ($entry) => isset($entry['source_id']))->values();
            $resourceIds = $resources->pluck('source_id')->map(fn ($id) => (int) $id)->all();
            $allowedPurposes = EvaluationDatasetOverlapPolicy::allowedPurposes($dataset->purpose);
            $conflict = $this->hasConflict($materials, $resourceIds, $allowedPurposes);
            if ($conflict) {
                $this->components->error("{$dataset->id}: Konflikt; keine Mitgliedschaften angelegt.");
                continue;
            }
            $this->components->warn("{$dataset->id}: {$dataset->material_count} Materialien, {$dataset->resource_count} Ressourcen ohne Mitgliedschaft.");
            if (! $apply) continue;
            try {
                DB::transaction(function () use ($dataset, $materials, $resources): void {
                    $timestamp = now();
                    foreach (array_chunk($materials, self::MEMBER_BATCH_SIZE) as $ids) {
                        ContextSearchEvaluationDatasetMember::query()->insert(array_map(fn ($id): array => [
                            'dataset_id' => $dataset->id, 'member_type' => 'material', 'member_id' => $id,
                            'source_revision_hash' => null, 'created_at' => $timestamp, 'updated_at' => $timestamp,
                        ], $ids));
                    }
                    foreach ($resources->chunk(self::MEMBER_BATCH_SIZE) as $batch) {
                        ContextSearchEvaluationDatasetMember::query()->insert($batch->map(fn ($resource): array => [
                            'dataset_id' => $dataset->id, 'member_type' => 'resource', 'member_id' => $resource['source_id'],
                            'source_revision_hash' => $resource['revision_hash'] ?? null, 'created_at' => $timestamp, 'updated_at' => $timestamp,
                        ])->all());
                    }
                });
                $this->components->info("{$dataset->id}: Mitgliedschaften angelegt.");
            } catch (Throwable $exception) {
                report($exception);
                $this->components->error("{$dataset->id}: {$exception->getMessage()}");
                return self::FAILURE;
            }
        }
        $this->components->info($affected === 0 ? 'Alle eingefrorenen Datensätze besitzen eine Mitgliedschaftsbilanz.' : ($apply ? 'Abgleich abgeschlossen.' : 'Nur Prüfung; mit --apply konfliktfreie Mitgliedschaften explizit anlegen.'));
        return self::SUCCESS;
    }

    /** @param array<int, int> $materials @param array<int, int> $resources @param array<int, string> $allowedPurposes */
    private function hasConflict(array $materials, array $resources, array $allowedPurposes): bool
    {
        foreach (['material' => $materials, 'resource' => $resources] as $type => $ids) {
            foreach (array_chunk($ids, self::MEMBER_BATCH_SIZE) as $batch) {
                if (ContextSearchEvaluationDatasetMember::query()
                    ->where('member_type', $type)
                    ->whereIn('member_id', $batch)
                    ->whereHas('dataset', fn ($existing) => $existing->whereNotIn('purpose', $allowedPurposes))
                    ->exists()) return true;
            }
        }

        return false;
    }

    private function renderCapacityOverview(): void
    {
        $targets = EvaluationDatasetCurationService::defaultTargets();
        $actual = DB::table('context_search_evaluation_datasets as datasets')
            ->leftJoin('context_search_evaluation_dataset_members as members', 'members.dataset_id', '=', 'datasets.id')
            ->groupBy('datasets.purpose')
            ->select('datasets.purpose')
            ->selectRaw("SUM(CASE WHEN members.dataset_id IS NULL THEN datasets.material_count WHEN members.member_type = 'material' THEN 1 ELSE 0 END) as materials")
            ->selectRaw("SUM(CASE WHEN members.dataset_id IS NULL THEN datasets.resource_count WHEN members.member_type = 'resource' THEN 1 ELSE 0 END) as resources")
            ->get()->mapWithKeys(fn ($row): array => [$row->purpose => [
                'materials' => (int) $row->materials, 'resources' => (int) $row->resources,
            ]])->all();
        $eligibleResources = Resource::query()->withoutGlobalScopes()->whereIn('type', ['pdf', 'text'])->count();
        $exclusiveTargets = collect($targets)->only(['calibration', 'acceptance']);
        $targetResources = array_sum(array_column($exclusiveTargets->all(), 'resources'));
        $targetMaterials = array_sum(array_column($exclusiveTargets->all(), 'materials'));

        $this->newLine();
        $this->components->info('Empfohlene Datensatzgrößen und Fortschritt (inhaltsfrei)');
        foreach ($targets as $purpose => $target) {
            $current = $actual[$purpose] ?? ['materials' => 0, 'resources' => 0];
            $resourcePercent = min(100, (int) floor(($current['resources'] / max(1, $target['resources'])) * 100));
            $bar = str_repeat('█', (int) floor($resourcePercent / 5)).str_repeat('░', 20 - (int) floor($resourcePercent / 5));
            $this->line(sprintf(
                '  %-12s [%s] %3d%%  Ressourcen %5d/%-5d  Materialien %5d/%-5d  offen R:%-5d M:%-5d',
                $this->purposeLabel($purpose), $bar, $resourcePercent, $current['resources'], $target['resources'],
                $current['materials'], $target['materials'], max(0, $target['resources'] - $current['resources']), max(0, $target['materials'] - $current['materials']),
            ));
        }
        $reserve = $eligibleResources - $targetResources;
        $overlappingResources = array_sum(array_column(array_intersect_key($targets, array_flip(['ocr', 'load', 'capacity'])), 'resources'));
        $this->line(sprintf('  %-12s Mindestbedarf exklusiv: %d Ressourcen / %d Materialien · OCR/Last/Kapazität: %d Ressourcen (überlappend) · aktuell geeignet: %d · Reserve: %d', 'Gesamt', $targetResources, $targetMaterials, $overlappingResources, $eligibleResources, $reserve));
        if ($reserve < 0) {
            $this->components->warn('Die aktuelle Anzahl geeigneter Ressourcen reicht nicht für Kalibrierung und Abnahme. OCR-, Last- und Kapazitätsziele können auf denselben vollständigen Blöcken liegen und erhöhen den Mindestbedarf nicht.');
        }
        $this->newLine();
    }

    private function purposeLabel(string $purpose): string
    {
        return [
            'calibration' => 'Kalibrierung', 'acceptance' => 'Abnahme', 'ocr' => 'OCR', 'load' => 'Last', 'capacity' => 'Kapazität',
        ][$purpose] ?? $purpose;
    }
}
