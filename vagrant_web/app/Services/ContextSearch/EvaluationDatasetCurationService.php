<?php

namespace App\Services\ContextSearch;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\Material;
use App\Models\Resource;
use App\Models\Text;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Curates evaluation data as connected material/resource components.
 *
 * The member table is intentionally the single ownership ledger. Its unique
 * key is the final concurrency guard; the preview is only advisory.
 */
final class EvaluationDatasetCurationService
{
    /** @var array<string, array{materials: int, resources: int, quotas: array<string, int>}> */
    private const DEFAULT_TARGETS = [
        'calibration' => ['materials' => 300, 'resources' => 450, 'quotas' => ['pdf' => 60, 'text' => 300, 'public' => 150, 'private' => 150]],
        'acceptance' => ['materials' => 170, 'resources' => 250, 'quotas' => ['pdf' => 30, 'text' => 170, 'public' => 80, 'private' => 80]],
        'ocr' => ['materials' => 75, 'resources' => 100, 'quotas' => ['pdf' => 100]],
        'load' => ['materials' => 1400, 'resources' => 2000, 'quotas' => ['pdf' => 300, 'text' => 1400, 'public' => 700, 'private' => 700]],
        'capacity' => ['materials' => 5600, 'resources' => 8000, 'quotas' => ['pdf' => 800, 'text' => 5600, 'public' => 2800, 'private' => 2800]],
    ];

    /** @return array<string, array{materials: int, resources: int, quotas: array<string, int>}> */
    public static function defaultTargets(): array
    {
        return self::DEFAULT_TARGETS;
    }

    public function create(string $purpose, ?string $title = null): ContextSearchEvaluationDataset
    {
        $targets = self::defaultTargets()[$purpose] ?? null;
        if ($targets === null) {
            throw new \InvalidArgumentException('Der Zweck des Evaluationsdatensatzes ist ungültig.');
        }

        return ContextSearchEvaluationDataset::query()->create([
            'purpose' => $purpose,
            'title' => $title,
            'status' => ContextSearchEvaluationDataset::STATUS_DRAFT,
            'version' => 1,
            'includes_private' => false,
            'manifest' => [],
            'manifest_hash' => hash('sha256', Str::uuid()->toString()),
            'material_count' => 0,
            'resource_count' => 0,
            'target_material_count' => $targets['materials'],
            'target_resource_count' => $targets['resources'],
            'target_quotas' => $targets['quotas'],
        ]);
    }

    /** @return array{material_ids: array<int, int>, resource_ids: array<int, int>, excluded_resource_count: int, conflicts: array<int, array<string, mixed>>, counts: array<string, int>} */
    public function preview(array $materialIds, array $resourceIds): array
    {
        $closure = $this->closure($materialIds, $resourceIds);
        $conflicts = ContextSearchEvaluationDatasetMember::query()
            ->where(function ($query) use ($closure): void {
                $query->where(fn ($members) => $members->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_MATERIAL)->whereIn('member_id', $closure['material_ids']))
                    ->orWhere(fn ($members) => $members->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_RESOURCE)->whereIn('member_id', $closure['resource_ids']));
            })
            ->with('dataset:id,purpose,title,status')
            ->orderBy('member_type')
            ->orderBy('member_id')
            ->get()
            ->map(fn (ContextSearchEvaluationDatasetMember $member): array => [
                'member_type' => $member->member_type,
                'member_id' => $member->member_id,
                'dataset' => $member->dataset?->only(['id', 'purpose', 'title', 'status']),
            ])->all();

        return $closure + ['conflicts' => $conflicts, 'counts' => $this->counts($closure['material_ids'], $closure['resource_ids'])];
    }

    public function assign(ContextSearchEvaluationDataset $dataset, int $expectedVersion, array $materialIds, array $resourceIds, bool $includePrivate, ?string $privateReason): ContextSearchEvaluationDataset
    {
        return DB::transaction(function () use ($dataset, $expectedVersion, $materialIds, $resourceIds, $includePrivate, $privateReason): ContextSearchEvaluationDataset {
            $this->assertNoUnreconciledFrozenDatasets();
            $dataset = ContextSearchEvaluationDataset::query()->lockForUpdate()->findOrFail($dataset->getKey());
            $this->assertMutable($dataset, $expectedVersion);
            $closure = $this->closure($materialIds, $resourceIds);
            if ($closure['resource_ids'] === [] || $closure['material_ids'] === []) {
                throw new RuntimeException('Die Auswahl enthält keinen vollständigen Block aus Material und geeigneter PDF- oder Textressource.');
            }

            $this->assertNoConflicts($dataset, $closure);
            $owned = $dataset->members()->get(['member_type', 'member_id']);
            $closure['material_ids'] = array_values(array_diff($closure['material_ids'], $owned->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_MATERIAL)->pluck('member_id')->map(fn ($id) => (int) $id)->all()));
            $closure['resource_ids'] = array_values(array_diff($closure['resource_ids'], $owned->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_RESOURCE)->pluck('member_id')->map(fn ($id) => (int) $id)->all()));
            if ($closure['material_ids'] === [] && $closure['resource_ids'] === []) {
                throw new RuntimeException('Der vollständige zusammenhängende Block gehört bereits zu diesem Entwurf.');
            }
            $counts = $this->counts($closure['material_ids'], $closure['resource_ids']);
            $hasPrivate = $counts['private_materials'] > 0 || $counts['private_resources'] > 0;
            if ($hasPrivate && (! $includePrivate || blank($privateReason))) {
                throw new RuntimeException('Private Inhalte verlangen die ausdrückliche Freigabe und eine Begründung.');
            }

            $resources = Resource::query()->withoutGlobalScopes()->whereIn('id', $closure['resource_ids'])->get()->keyBy('id');
            foreach ($closure['material_ids'] as $id) {
                ContextSearchEvaluationDatasetMember::query()->create([
                    'dataset_id' => $dataset->getKey(),
                    'member_type' => ContextSearchEvaluationDatasetMember::TYPE_MATERIAL,
                    'member_id' => $id,
                ]);
            }
            foreach ($closure['resource_ids'] as $id) {
                ContextSearchEvaluationDatasetMember::query()->create([
                    'dataset_id' => $dataset->getKey(),
                    'member_type' => ContextSearchEvaluationDatasetMember::TYPE_RESOURCE,
                    'member_id' => $id,
                    'source_revision_hash' => $this->revisionHash($resources->get($id)),
                ]);
            }

            return $this->refreshState($dataset, $hasPrivate, $privateReason);
        });
    }

    public function removeConnectedBlock(ContextSearchEvaluationDataset $dataset, int $expectedVersion, string $memberType, int $memberId): ContextSearchEvaluationDataset
    {
        return DB::transaction(function () use ($dataset, $expectedVersion, $memberType, $memberId): ContextSearchEvaluationDataset {
            $dataset = ContextSearchEvaluationDataset::query()->lockForUpdate()->findOrFail($dataset->getKey());
            $this->assertMutable($dataset, $expectedVersion);
            $exists = $dataset->members()->where('member_type', $memberType)->where('member_id', $memberId)->exists();
            if (! $exists) {
                throw new RuntimeException('Die Zuordnung ist nicht Teil dieses Entwurfs.');
            }
            $closure = $this->closure($memberType === ContextSearchEvaluationDatasetMember::TYPE_MATERIAL ? [$memberId] : [], $memberType === ContextSearchEvaluationDatasetMember::TYPE_RESOURCE ? [$memberId] : []);
            $dataset->members()->where(function ($query) use ($closure): void {
                $query->where(fn ($members) => $members->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_MATERIAL)->whereIn('member_id', $closure['material_ids']))
                    ->orWhere(fn ($members) => $members->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_RESOURCE)->whereIn('member_id', $closure['resource_ids']));
            })->delete();

            return $this->refreshState($dataset, false, null);
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function summaries(): array
    {
        return ContextSearchEvaluationDataset::query()->withCount('members')->orderBy('purpose')->orderBy('created_at')->get()
            ->map(fn (ContextSearchEvaluationDataset $dataset): array => $this->summary($dataset))->all();
    }

    /** @return array<string, mixed> */
    public function summary(ContextSearchEvaluationDataset $dataset): array
    {
        $memberIds = $dataset->members()->get(['member_type', 'member_id']);
        $materialIds = $memberIds->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_MATERIAL)->pluck('member_id')->map(fn ($id) => (int) $id)->all();
        $resourceIds = $memberIds->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_RESOURCE)->pluck('member_id')->map(fn ($id) => (int) $id)->all();
        $counts = $this->counts($materialIds, $resourceIds);
        $targetQuotas = $dataset->target_quotas ?? [];
        $quotas = collect($targetQuotas)->map(fn (int $target, string $key): array => ['actual' => $counts[$key] ?? 0, 'target' => $target, 'remaining' => max(0, $target - ($counts[$key] ?? 0))])->all();

        return [
            'id' => $dataset->getKey(), 'purpose' => $dataset->purpose, 'title' => $dataset->title, 'status' => $dataset->status,
            'version' => $dataset->version, 'includes_private' => $dataset->includes_private, 'private_reason' => $dataset->private_reason,
            'material_count' => $counts['materials'], 'resource_count' => $counts['resources'],
            'target_material_count' => $dataset->target_material_count, 'target_resource_count' => $dataset->target_resource_count,
            'materials_remaining' => max(0, (int) $dataset->target_material_count - $counts['materials']),
            'resources_remaining' => max(0, (int) $dataset->target_resource_count - $counts['resources']),
            'quotas' => $quotas, 'ready_at' => $dataset->ready_at?->toAtomString(), 'frozen_at' => $dataset->frozen_at?->toAtomString(),
        ];
    }

    /** @return array{material_ids: array<int, int>, resource_ids: array<int, int>, excluded_resource_count: int} */
    private function closure(array $materialIds, array $resourceIds): array
    {
        $materialIds = array_values(array_unique(array_map('intval', $materialIds)));
        $resourceIds = array_values(array_unique(array_map('intval', $resourceIds)));
        $seenMaterials = []; $seenResources = [];
        $pendingMaterials = $materialIds; $pendingResources = $resourceIds;

        while ($pendingMaterials !== [] || $pendingResources !== []) {
            $newMaterials = array_values(array_diff($pendingMaterials, $seenMaterials));
            $newResources = array_values(array_diff($pendingResources, $seenResources));
            $pendingMaterials = []; $pendingResources = [];
            if ($newMaterials === [] && $newResources === []) {
                break;
            }
            $seenMaterials = array_values(array_unique([...$seenMaterials, ...$newMaterials]));
            $seenResources = array_values(array_unique([...$seenResources, ...$newResources]));

            $links = DB::table('material_resource')
                ->where(function ($query) use ($newMaterials, $newResources): void {
                    if ($newMaterials !== []) $query->whereIn('material_id', $newMaterials);
                    if ($newResources !== []) $newMaterials === [] ? $query->whereIn('resource_id', $newResources) : $query->orWhereIn('resource_id', $newResources);
                })->get(['material_id', 'resource_id']);
            $linkedResourceIds = $links->pluck('resource_id')->map(fn ($id) => (int) $id)->unique()->all();
            $linkedMaterialIds = $links->pluck('material_id')->map(fn ($id) => (int) $id)->unique()->all();
            $eligibleResourceIds = Resource::query()->withoutGlobalScopes()->whereIn('id', $linkedResourceIds)->whereIn('type', ['pdf', 'text'])->pluck('id')->map(fn ($id) => (int) $id)->all();
            $pendingMaterials = array_values(array_diff($linkedMaterialIds, $seenMaterials));
            $pendingResources = array_values(array_diff($eligibleResourceIds, $seenResources));
        }

        $eligibleDirect = Resource::query()->withoutGlobalScopes()->whereIn('id', $resourceIds)->whereIn('type', ['pdf', 'text'])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $seenResources = array_values(array_unique([...$seenResources, ...$eligibleDirect]));
        $seenMaterials = Material::query()->withoutGlobalScopes()->whereIn('id', $seenMaterials)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $excluded = max(0, count($resourceIds) - count($eligibleDirect));

        return ['material_ids' => $seenMaterials, 'resource_ids' => $seenResources, 'excluded_resource_count' => $excluded];
    }

    /** @param array<int, int> $materialIds @param array<int, int> $resourceIds @return array<string, int> */
    private function counts(array $materialIds, array $resourceIds): array
    {
        $materials = Material::query()->withoutGlobalScopes()->whereIn('id', $materialIds)->get(['id', 'is_public']);
        $resources = Resource::query()->withoutGlobalScopes()->whereIn('id', $resourceIds)->get(['id', 'type', 'is_public']);
        return [
            'materials' => $materials->count(), 'resources' => $resources->count(),
            'private_materials' => $materials->where('is_public', false)->count(), 'private_resources' => $resources->where('is_public', false)->count(),
            'public' => $resources->where('is_public', true)->count(), 'private' => $resources->where('is_public', false)->count(),
            'pdf' => $resources->where('type', 'pdf')->count(), 'text' => $resources->where('type', 'text')->count(),
        ];
    }

    private function assertNoConflicts(ContextSearchEvaluationDataset $dataset, array $closure): void
    {
        $conflict = ContextSearchEvaluationDatasetMember::query()->where('dataset_id', '!=', $dataset->getKey())
            ->where(function ($query) use ($closure): void {
                $query->where(fn ($members) => $members->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_MATERIAL)->whereIn('member_id', $closure['material_ids']))
                    ->orWhere(fn ($members) => $members->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_RESOURCE)->whereIn('member_id', $closure['resource_ids']));
            })->exists();
        if ($conflict) throw new RuntimeException('Mindestens ein Material oder eine Ressource ist bereits dauerhaft einem anderen Datensatz zugeordnet.');
    }

    private function assertMutable(ContextSearchEvaluationDataset $dataset, int $expectedVersion): void
    {
        if ($dataset->status !== ContextSearchEvaluationDataset::STATUS_DRAFT && $dataset->status !== ContextSearchEvaluationDataset::STATUS_READY) throw new RuntimeException('Nur Entwürfe können verändert werden.');
        if ($dataset->version !== $expectedVersion) throw new RuntimeException('Der Entwurf wurde zwischenzeitlich geändert. Bitte aktualisieren und erneut prüfen.');
    }

    private function assertNoUnreconciledFrozenDatasets(): void
    {
        $exists = ContextSearchEvaluationDataset::query()->whereIn('status', [ContextSearchEvaluationDataset::STATUS_FROZEN, ContextSearchEvaluationDataset::STATUS_EXPORTED])
            ->where('resource_count', '>', 0)->whereDoesntHave('members')->exists();
        if ($exists) throw new RuntimeException('Eingefrorene Alt-Datensätze besitzen noch keine Mitgliedschaftsbilanz. Zuerst context-search:dataset:reconcile-memberships prüfen und gegebenenfalls mit --apply abgleichen.');
    }

    private function refreshState(ContextSearchEvaluationDataset $dataset, bool $newPrivate, ?string $privateReason): ContextSearchEvaluationDataset
    {
        $summary = $this->summary($dataset);
        $ready = $summary['materials_remaining'] === 0 && $summary['resources_remaining'] === 0 && collect($summary['quotas'])->every(fn (array $quota): bool => $quota['remaining'] === 0);
        $dataset->forceFill([
            'status' => $ready ? ContextSearchEvaluationDataset::STATUS_READY : ContextSearchEvaluationDataset::STATUS_DRAFT,
            'version' => $dataset->version + 1,
            'includes_private' => $dataset->includes_private || $newPrivate,
            'private_reason' => $dataset->private_reason ?? $privateReason,
            'material_count' => $summary['material_count'], 'resource_count' => $summary['resource_count'],
            'ready_at' => $ready ? ($dataset->ready_at ?? now()) : null,
        ])->save();
        return $dataset->fresh();
    }

    private function revisionHash(?Resource $resource): ?string
    {
        if ($resource === null) return null;
        if ($resource instanceof Text) return hash('sha256', (string) $resource->content);
        return $resource->content_hash ?: hash('sha256', implode('|', [(string) $resource->local_path, (string) $resource->filesize, (string) $resource->updated_at]));
    }
}
