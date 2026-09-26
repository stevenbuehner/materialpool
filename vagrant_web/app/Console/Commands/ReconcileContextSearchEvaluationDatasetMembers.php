<?php

namespace App\Console\Commands;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\Resource;
use App\Services\ContextSearch\EvaluationDatasetCurationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ReconcileContextSearchEvaluationDatasetMembers extends Command
{
    protected $signature = 'context-search:dataset:reconcile-memberships {--apply : Bestätigt das Anlegen konfliktfreier Mitgliedschaften}';

    protected $description = 'Prüft eingefrorene Alt-Datensätze auf fehlende, eindeutige Mitgliedschaften; Standard ist ausschließlich lesend.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->renderCapacityOverview();
        $datasets = ContextSearchEvaluationDataset::query()->whereIn('status', [ContextSearchEvaluationDataset::STATUS_FROZEN, ContextSearchEvaluationDataset::STATUS_EXPORTED])->orderBy('created_at')->get();
        $datasetsWithMembers = ContextSearchEvaluationDatasetMember::query()->whereIn('dataset_id', $datasets->modelKeys())->distinct()->pluck('dataset_id')->flip();
        $affected = 0;
        foreach ($datasets as $dataset) {
            if ($datasetsWithMembers->has($dataset->id)) continue;
            $affected++;
            $materials = collect($dataset->manifest['materials'] ?? [])->pluck('source_id')->filter()->map(fn ($id) => (int) $id)->all();
            $resources = collect($dataset->manifest['resources'] ?? [])->filter(fn ($entry) => isset($entry['source_id']))->values();
            $resourceIds = $resources->pluck('source_id')->map(fn ($id) => (int) $id)->all();
            $conflict = ContextSearchEvaluationDatasetMember::query()->where(function ($query) use ($materials, $resourceIds): void {
                $query->where(fn ($members) => $members->where('member_type', 'material')->whereIn('member_id', $materials))
                    ->orWhere(fn ($members) => $members->where('member_type', 'resource')->whereIn('member_id', $resourceIds));
            })->exists();
            if ($conflict) {
                $this->components->error("{$dataset->id}: Konflikt; keine Mitgliedschaften angelegt.");
                continue;
            }
            $this->components->warn("{$dataset->id}: {$dataset->material_count} Materialien, {$dataset->resource_count} Ressourcen ohne Mitgliedschaft.");
            if (! $apply) continue;
            try {
                DB::transaction(function () use ($dataset, $materials, $resources): void {
                    foreach ($materials as $id) ContextSearchEvaluationDatasetMember::query()->create(['dataset_id' => $dataset->id, 'member_type' => 'material', 'member_id' => $id]);
                    foreach ($resources as $resource) ContextSearchEvaluationDatasetMember::query()->create(['dataset_id' => $dataset->id, 'member_type' => 'resource', 'member_id' => $resource['source_id'], 'source_revision_hash' => $resource['revision_hash'] ?? null]);
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

    private function renderCapacityOverview(): void
    {
        $targets = EvaluationDatasetCurationService::defaultTargets();
        $datasets = ContextSearchEvaluationDataset::query()->get(['id', 'purpose', 'material_count', 'resource_count']);
        $memberCounts = ContextSearchEvaluationDatasetMember::query()
            ->selectRaw("dataset_id, SUM(member_type = 'material') as materials, SUM(member_type = 'resource') as resources")
            ->groupBy('dataset_id')->get()->keyBy('dataset_id');
        $actual = [];
        foreach ($datasets as $dataset) {
            $counts = $memberCounts->get($dataset->id);
            $actual[$dataset->purpose]['materials'] = ($actual[$dataset->purpose]['materials'] ?? 0) + ($counts?->materials ?? $dataset->material_count);
            $actual[$dataset->purpose]['resources'] = ($actual[$dataset->purpose]['resources'] ?? 0) + ($counts?->resources ?? $dataset->resource_count);
        }
        $eligibleResources = Resource::query()->withoutGlobalScopes()->whereIn('type', ['pdf', 'text'])->count();
        $targetResources = array_sum(array_column($targets, 'resources'));
        $targetMaterials = array_sum(array_column($targets, 'materials'));

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
        $this->line(sprintf('  %-12s Ziel: %d Ressourcen / %d Materialien · aktuell geeignete Ressourcen: %d · Reserve: %d', 'Gesamt', $targetResources, $targetMaterials, $eligibleResources, $reserve));
        if ($reserve < 0) {
            $this->components->warn('Die aktuelle Anzahl geeigneter Ressourcen reicht nicht für alle empfohlenen, disjunkten Datensätze. Ziele vor der Kuratierung anpassen.');
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
