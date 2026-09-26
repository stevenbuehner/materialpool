<?php

namespace App\Console\Commands;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
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
        $datasets = ContextSearchEvaluationDataset::query()->whereIn('status', [ContextSearchEvaluationDataset::STATUS_FROZEN, ContextSearchEvaluationDataset::STATUS_EXPORTED])->orderBy('created_at')->get();
        $affected = 0;
        foreach ($datasets as $dataset) {
            if ($dataset->members()->exists()) continue;
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
}
