<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\Material;
use App\Models\Resource;
use App\Services\ContextSearch\EvaluationDatasetCurationService;
use App\Services\ContextSearch\EvaluationDatasetOverlapPolicy;
use App\Services\ContextSearch\EvaluationDatasetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

final class ContextSearchEvaluationDatasetController extends Controller {
	public function index(EvaluationDatasetCurationService $curation): array {
		return ['datasets' => $curation->summaries()];
	}

	public function store(Request $request, EvaluationDatasetCurationService $curation): JsonResponse {
		$data    = $request->validate([
			'purpose' => ['required', 'string', Rule::in(ContextSearchEvaluationDataset::PURPOSES)],
			'title'   => ['nullable', 'string', 'max:255'],
		]);
		$dataset = $curation->create($data['purpose'], $data['title'] ?? NULL);
		return response()->json(['dataset' => $curation->summary($dataset)], 201);
	}

	public function candidates(Request $request): array {
		$data          = $request->validate([
			'page'    => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
			'search'  => ['nullable', 'string', 'max:255'], 'visibility' => ['nullable', Rule::in(['all', 'public', 'private'])],
			'type'    => ['nullable', Rule::in(['all', 'pdf', 'text'])], 'assignment' => ['nullable', Rule::in(['all', 'free', 'assigned'])],
			'bundle'  => ['nullable', 'string', 'max:20', 'regex:/^(all|user|[1-9][0-9]*)$/'],
			'dataset' => ['nullable', 'uuid', 'exists:context_search_evaluation_datasets,id'],
		]);
		$targetDataset = isset($data['dataset']) ? ContextSearchEvaluationDataset::query()->findOrFail($data['dataset']) : NULL;
		$type          = $data['type'] ?? 'all';
		$visibility    = $data['visibility'] ?? 'all';
		$query         = Material::query()->withoutGlobalScopes()
			->join('material_resource as candidate_links', 'candidate_links.material_id', '=', 'materials.id')
			->join('resources as candidate_resources', 'candidate_resources.id', '=', 'candidate_links.resource_id')
			->whereIn('candidate_resources.type', $type === 'all' ? ['pdf', 'text'] : [$type])
			->distinct()->orderBy('materials.id');
		if ($visibility !== 'all') $query->where('candidate_resources.is_public', $visibility === 'public');
		if (filled($data['search'] ?? NULL)) {
			$escaped = str_replace(['%', '_'], ['\\%', '\\_'], $data['search']);
			$query->where('materials.title', 'like', "%{$escaped}%");
		}
		if (($data['bundle'] ?? 'all') === 'user') {
			$query->whereDoesntHave('foreignIds', fn($foreignIds) => $foreignIds->whereNotNull('bundle_id'));
		} elseif (($data['bundle'] ?? 'all') !== 'all') {
			$query->join('material_foreign_ids as candidate_foreign_ids', 'candidate_foreign_ids.material_id', '=', 'materials.id')
				->where('candidate_foreign_ids.bundle_id', (int)$data['bundle']);
		}
		$total       = $query->toBase()->getCountForPagination(['materials.id']);
		$page        = $query->paginate($data['per_page'] ?? 25, ['materials.id'], total: $total);
		$materialIds = $page->getCollection()->modelKeys();
		$materials   = Material::query()->withoutGlobalScopes()->whereKey($materialIds)->with(['resources' => function ($resources) use ($type, $visibility): void {
			$resources->withoutGlobalScopes()->whereIn('type', $type === 'all' ? ['pdf', 'text'] : [$type])->select(['resources.id', 'type', 'notes', 'is_public', 'options']);
			if ($visibility !== 'all') $resources->where('is_public', $visibility === 'public');
		}])->get(['materials.id', 'materials.title', 'materials.description', 'materials.is_public'])->keyBy('id');
		$page->setCollection($page->getCollection()->map(fn(Material $material): Material => $materials[$material->id]));
		$resourceIds = $page->getCollection()->flatMap(fn(Material $material) => $material->resources->modelKeys())->all();
		$assignments = $this->assignments($materialIds, $resourceIds, $targetDataset);
		if (($data['assignment'] ?? 'all') !== 'all') {
			$wantAssigned = $data['assignment'] === 'assigned';
			$page->setCollection($page->getCollection()->filter(fn(Material $material): bool => !empty($assignments['material:' . $material->id]['locked']) === $wantAssigned)->values());
		}
		$page->getCollection()->transform(fn(Material $material): array => [
			'id'          => $material->id, 'title' => $material->title, 'description' => $material->description, 'is_public' => (bool)$material->is_public,
			'assignment'  => $assignments['material:' . $material->id]['assignment'] ?? NULL,
			'assignments' => $assignments['material:' . $material->id]['all'] ?? [],
			'resources'   => $material->resources->map(fn(Resource $resource): array => [
				'id'          => $resource->id, 'type' => $resource->type, 'notes' => $resource->notes, 'is_public' => (bool)$resource->is_public,
				'page_count'  => $resource->type === 'pdf' ? ($resource->options['pdfPageCount'] ?? NULL) : NULL,
				'assignment'  => $assignments['resource:' . $resource->id]['assignment'] ?? NULL,
				'assignments' => $assignments['resource:' . $resource->id]['all'] ?? [],
			])->values(),
		]);
		return $page->toArray() + [
				'bundles' => Bundle::query()->orderBy('name')->get(['id', 'name'])->all(),
			];
	}

	/** @param array<int, int> $materialIds @param array<int, int> $resourceIds @return array<string, array{all: array<int, array<string, mixed>>, assignment: ?array<string, mixed>, locked: bool}> */
	private function assignments(array $materialIds, array $resourceIds, ?ContextSearchEvaluationDataset $targetDataset): array {
		$memberships = ContextSearchEvaluationDatasetMember::query()->where(function ($query) use ($materialIds, $resourceIds): void {
			$query->where(fn($members) => $members->where('member_type', 'material')->whereIn('member_id', $materialIds))
				->orWhere(fn($members) => $members->where('member_type', 'resource')->whereIn('member_id', $resourceIds));
		})->with('dataset:id,purpose,title,status')->get()->groupBy(fn(ContextSearchEvaluationDatasetMember $member): string => $member->member_type . ':' . $member->member_id);

		return $memberships->mapWithKeys(function ($members, string $key) use ($targetDataset): array {
			$all        = $members->map(fn(ContextSearchEvaluationDatasetMember $member): array => [
				'id' => $member->dataset_id, 'purpose' => $member->dataset?->purpose, 'title' => $member->dataset?->title, 'status' => $member->dataset?->status, 'document_type' => $member->document_type,
			])->values()->all();
			$current    = $targetDataset === NULL ? NULL : collect($all)->first(fn(array $assignment): bool => $assignment['id'] === $targetDataset->getKey());
			$blocking   = collect($all)->first(fn(array $assignment): bool => $targetDataset === NULL
				|| ($assignment['id'] !== $targetDataset->getKey() && !EvaluationDatasetOverlapPolicy::allows($targetDataset->purpose, (string)$assignment['purpose'])));
			$primary    = $current ?? $blocking ?? ($all[0] ?? NULL);
			$locked     = $current !== NULL || $blocking !== NULL;
			$purposes   = collect($all)->pluck('purpose')->unique()->values();
			$assignment = $primary === NULL ? NULL : array_merge($primary, [
				'purpose'         => $purposes->join('|'),
				'memberships'     => $all,
				'overlap_allowed' => $primary !== NULL && !$locked,
			]);

			return [$key => ['all' => $all, 'assignment' => $assignment, 'locked' => $locked]];
		})->all();
	}

	public function preview(Request $request, EvaluationDatasetCurationService $curation): array {
		$data          = $this->selection($request);
		$targetDataset = $request->validate(['dataset' => ['required', 'uuid', 'exists:context_search_evaluation_datasets,id']]);
		return ['preview' => $curation->preview($data['material_ids'], $data['resource_ids'], ContextSearchEvaluationDataset::query()->findOrFail($targetDataset['dataset']))];
	}

	/** @return array{material_ids: array<int, int>, resource_ids: array<int, int>, expected_version?: int, include_private?: bool, private_reason?: ?string} */
	private function selection(Request $request, bool $withState = FALSE): array {
		$rules = [
			'material_ids' => ['present', 'array', 'max:1000'], 'material_ids.*' => ['integer', 'min:1'],
			'resource_ids' => ['present', 'array', 'max:1000'], 'resource_ids.*' => ['integer', 'min:1'],
		];
		if ($withState) $rules += ['expected_version' => ['required', 'integer', 'min:1'], 'include_private' => ['required', 'boolean'], 'private_reason' => ['nullable', 'string', 'max:500']];
		$data = $request->validate($rules);
		if ($data['material_ids'] === [] && $data['resource_ids'] === []) abort(422, 'Mindestens ein Material oder eine Ressource muss gewählt werden.');
		return $data;
	}

	public function assign(ContextSearchEvaluationDataset $dataset, Request $request, EvaluationDatasetCurationService $curation): JsonResponse|array {
		$data = $this->selection($request, TRUE);
		try {
			$dataset = $curation->assign($dataset, $data['expected_version'], $data['material_ids'], $data['resource_ids'], $data['include_private'], $data['private_reason'] ?? NULL);
		} catch (RuntimeException $exception) {
			return response()->json(['message' => $exception->getMessage()], str_contains($exception->getMessage(), 'zwischenzeitlich') ? 409 : 422);
		}
		return ['dataset' => $curation->summary($dataset)];
	}

	public function remove(ContextSearchEvaluationDataset $dataset, string $memberType, int $memberId, Request $request, EvaluationDatasetCurationService $curation): JsonResponse|array {
		abort_unless(in_array($memberType, [ContextSearchEvaluationDatasetMember::TYPE_MATERIAL, ContextSearchEvaluationDatasetMember::TYPE_RESOURCE], TRUE), 404);
		$data = $request->validate(['expected_version' => ['required', 'integer', 'min:1']]);
		try {
			$dataset = $curation->removeConnectedBlock($dataset, $data['expected_version'], $memberType, $memberId);
		} catch (RuntimeException $exception) {
			return response()->json(['message' => $exception->getMessage()], str_contains($exception->getMessage(), 'zwischenzeitlich') ? 409 : 422);
		}
		return ['dataset' => $curation->summary($dataset)];
	}

	public function classify(ContextSearchEvaluationDataset $dataset, int $memberId, Request $request, EvaluationDatasetCurationService $curation): JsonResponse|array {
		$data = $request->validate([
			'expected_version' => ['required', 'integer', 'min:1'],
			'document_type' => ['required', Rule::in(['book', 'worksheet', 'presentation'])],
		]);
		try {
			$dataset = $curation->classifyOcrResource($dataset, $memberId, $data['expected_version'], $data['document_type']);
		} catch (RuntimeException $exception) {
			return response()->json(['message' => $exception->getMessage()], str_contains($exception->getMessage(), 'zwischenzeitlich') ? 409 : 422);
		}
		return ['dataset' => $curation->summary($dataset)];
	}

	public function freeze(ContextSearchEvaluationDataset $dataset, EvaluationDatasetCurationService $curation, EvaluationDatasetService $datasets): JsonResponse|array {
		try {
			$dataset = $datasets->freezeCurated($dataset);
		} catch (RuntimeException $exception) {
			return response()->json(['message' => $exception->getMessage()], 422);
		}
		return ['dataset' => $curation->summary($dataset)];
	}

}
