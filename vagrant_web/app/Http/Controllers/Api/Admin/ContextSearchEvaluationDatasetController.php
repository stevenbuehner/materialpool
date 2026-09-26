<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\Bundle;
use App\Models\Material;
use App\Models\Resource;
use App\Services\ContextSearch\EvaluationDatasetCurationService;
use App\Services\ContextSearch\EvaluationDatasetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

final class ContextSearchEvaluationDatasetController extends Controller
{
    public function index(EvaluationDatasetCurationService $curation): array
    {
        return ['datasets' => $curation->summaries()];
    }

    public function store(Request $request, EvaluationDatasetCurationService $curation): JsonResponse
    {
        $data = $request->validate([
            'purpose' => ['required', 'string', Rule::in(ContextSearchEvaluationDataset::PURPOSES)],
            'title' => ['nullable', 'string', 'max:255'],
        ]);
        $dataset = $curation->create($data['purpose'], $data['title'] ?? null);
        return response()->json(['dataset' => $curation->summary($dataset)], 201);
    }

    public function candidates(Request $request): array
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'], 'visibility' => ['nullable', Rule::in(['all', 'public', 'private'])],
            'type' => ['nullable', Rule::in(['all', 'pdf', 'text'])], 'assignment' => ['nullable', Rule::in(['all', 'free', 'assigned'])],
            'bundle' => ['nullable', 'string', 'max:20', 'regex:/^(all|user|[1-9][0-9]*)$/'],
        ]);
        $type = $data['type'] ?? 'all';
        $visibility = $data['visibility'] ?? 'all';
        $query = Material::query()->withoutGlobalScopes()->whereHas('resources', function ($resources) use ($type, $visibility): void {
            $resources->withoutGlobalScopes()->whereIn('type', $type === 'all' ? ['pdf', 'text'] : [$type]);
            if ($visibility !== 'all') $resources->where('is_public', $visibility === 'public');
        })->with(['resources' => function ($resources) use ($type, $visibility): void {
            $resources->withoutGlobalScopes()->whereIn('type', $type === 'all' ? ['pdf', 'text'] : [$type])->select(['resources.id', 'type', 'notes', 'is_public']);
            if ($visibility !== 'all') $resources->where('is_public', $visibility === 'public');
        }])->orderBy('materials.id');
        if (filled($data['search'] ?? null)) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $data['search']);
            $query->where('title', 'like', "%{$escaped}%");
        }
        if (($data['bundle'] ?? 'all') === 'user') {
            $query->whereDoesntHave('foreignIds', fn ($foreignIds) => $foreignIds->whereNotNull('bundle_id'));
        } elseif (($data['bundle'] ?? 'all') !== 'all') {
            $query->whereHas('foreignIds', fn ($foreignIds) => $foreignIds->where('bundle_id', (int) $data['bundle']));
        }
        $page = $query->paginate($data['per_page'] ?? 25);
        $materialIds = $page->getCollection()->modelKeys();
        $resourceIds = $page->getCollection()->flatMap(fn (Material $material) => $material->resources->modelKeys())->all();
        $assignments = $this->assignments($materialIds, $resourceIds);
        if (($data['assignment'] ?? 'all') !== 'all') {
            $wantAssigned = $data['assignment'] === 'assigned';
            $page->setCollection($page->getCollection()->filter(fn (Material $material): bool => array_key_exists('material:'.$material->id, $assignments) === $wantAssigned)->values());
        }
        $page->getCollection()->transform(fn (Material $material): array => [
            'id' => $material->id, 'title' => $material->title, 'description' => $material->description, 'is_public' => (bool) $material->is_public,
            'assignment' => $assignments['material:'.$material->id] ?? null,
            'resources' => $material->resources->map(fn (Resource $resource): array => [
                'id' => $resource->id, 'type' => $resource->type, 'notes' => $resource->notes, 'is_public' => (bool) $resource->is_public,
                'assignment' => $assignments['resource:'.$resource->id] ?? null,
            ])->values(),
        ]);
        return $page->toArray() + [
            'bundles' => Bundle::query()->orderBy('name')->get(['id', 'name'])->all(),
        ];
    }

    public function preview(Request $request, EvaluationDatasetCurationService $curation): array
    {
        $data = $this->selection($request);
        return ['preview' => $curation->preview($data['material_ids'], $data['resource_ids'])];
    }

    public function assign(ContextSearchEvaluationDataset $dataset, Request $request, EvaluationDatasetCurationService $curation): JsonResponse|array
    {
        $data = $this->selection($request, true);
        try {
            $dataset = $curation->assign($dataset, $data['expected_version'], $data['material_ids'], $data['resource_ids'], $data['include_private'], $data['private_reason'] ?? null);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], str_contains($exception->getMessage(), 'zwischenzeitlich') ? 409 : 422);
        }
        return ['dataset' => $curation->summary($dataset)];
    }

    public function remove(ContextSearchEvaluationDataset $dataset, string $memberType, int $memberId, Request $request, EvaluationDatasetCurationService $curation): JsonResponse|array
    {
        abort_unless(in_array($memberType, [ContextSearchEvaluationDatasetMember::TYPE_MATERIAL, ContextSearchEvaluationDatasetMember::TYPE_RESOURCE], true), 404);
        $data = $request->validate(['expected_version' => ['required', 'integer', 'min:1']]);
        try {
            $dataset = $curation->removeConnectedBlock($dataset, $data['expected_version'], $memberType, $memberId);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], str_contains($exception->getMessage(), 'zwischenzeitlich') ? 409 : 422);
        }
        return ['dataset' => $curation->summary($dataset)];
    }

    public function freeze(ContextSearchEvaluationDataset $dataset, EvaluationDatasetCurationService $curation, EvaluationDatasetService $datasets): JsonResponse|array
    {
        try {
            $dataset = $datasets->freezeCurated($dataset);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
        return ['dataset' => $curation->summary($dataset)];
    }

    /** @return array{material_ids: array<int, int>, resource_ids: array<int, int>, expected_version?: int, include_private?: bool, private_reason?: ?string} */
    private function selection(Request $request, bool $withState = false): array
    {
        $rules = [
            'material_ids' => ['present', 'array', 'max:1000'], 'material_ids.*' => ['integer', 'min:1'],
            'resource_ids' => ['present', 'array', 'max:1000'], 'resource_ids.*' => ['integer', 'min:1'],
        ];
        if ($withState) $rules += ['expected_version' => ['required', 'integer', 'min:1'], 'include_private' => ['required', 'boolean'], 'private_reason' => ['nullable', 'string', 'max:500']];
        $data = $request->validate($rules);
        if ($data['material_ids'] === [] && $data['resource_ids'] === []) abort(422, 'Mindestens ein Material oder eine Ressource muss gewählt werden.');
        return $data;
    }

    /** @param array<int, int> $materialIds @param array<int, int> $resourceIds @return array<string, array<string, mixed>> */
    private function assignments(array $materialIds, array $resourceIds): array
    {
        return ContextSearchEvaluationDatasetMember::query()->where(function ($query) use ($materialIds, $resourceIds): void {
            $query->where(fn ($members) => $members->where('member_type', 'material')->whereIn('member_id', $materialIds))
                ->orWhere(fn ($members) => $members->where('member_type', 'resource')->whereIn('member_id', $resourceIds));
        })->with('dataset:id,purpose,title,status')->get()->mapWithKeys(fn (ContextSearchEvaluationDatasetMember $member): array => [
            $member->member_type.':'.$member->member_id => ['id' => $member->dataset_id, 'purpose' => $member->dataset?->purpose, 'title' => $member->dataset?->title, 'status' => $member->dataset?->status],
        ])->all();
    }
}
