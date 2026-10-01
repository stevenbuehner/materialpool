<?php

namespace App\Http\Controllers\Api;

use App\Events\MaterialWasChanged;
use App\Events\MaterialWasCreated;
use App\Http\Requests\MaterialRequest;
use App\Jobs\DeleteMaterialDownload;
use App\Jobs\GenerateMaterialDownload;
use App\Models\Exceptions\InvalidKeywordTypeException;
use App\Models\Material;
use App\Services\MaterialHandling\MaterialHandlingService;
use App\Services\MaterialHandling\MaterialDownloadStore;
use App\Services\MaterialHandling\MaterialUserRankingService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;


class MaterialController extends BaseController {

	use MaterialHelperTrait, ResourceMaterialTrait;

	protected $bibleVerseService;
	protected $materialHandlingService;
	protected $materialUserRankingService;

	public function __construct(BibleVerseService $bibleVerseService, MaterialHandlingService $materialHandlingService, MaterialUserRankingService $materialUserRankingService) {

		$this->bibleVerseService          = $bibleVerseService;
		$this->materialHandlingService    = $materialHandlingService;
		$this->materialUserRankingService = $materialUserRankingService;
		$this->middleware(['auth:api']);
	}

	/**
	 * Display a listing of the material.
	 *
	 * @return Response
	 */
	public function index() {

		$materials = Material::query()
			->visibleTo(Auth::user())
			->with($this->visibleWithAttributes())
			->orderBy('updated_at')
			->paginate(50);

		$this->materialUserRankingService->presentCollection($materials->getCollection(), Auth::user());

		return $materials;
	}

	protected function visibleWithAttributes(): array {
		return \App\Http\Controllers\MaterialController::withVisibleAttributes(Auth::user());
	}

	/*
		public function associateResources(Material $material, Request $request) {

			$resourceIds         = $request->get('resource_id', []);
			$response            = $this->doSync($material, $resourceIds);
			$response['success'] = TRUE;

			return $response;
		}
	*/

	/**
	 * Store a newly created material in storage.
	 *
	 */
	public function store(MaterialRequest $request) {

		$material              = new Material($request->all());
		$material->created_by  = Auth::id();
		$material->modified_by = Auth::id();
		$material->from_bot    = $request->get('from_bot', TRUE);
		if ($request->has('is_public')) {
			$material->is_public = $request->boolean('is_public');
		}


		$this->fillAuthor($request->get('author'), $material);
		// necessary to assign keywords and bibleverses
		$material->save();

		$this->syncKeywords($request, $material);
		$this->syncBibleverses($request, $material);

		// Reload from DB with Relations
		$material = $material->fresh($this->visibleWithAttributes());

		event(new MaterialWasCreated($material));

		return $this->materialUserRankingService->present($material, Auth::user());
	}

	/**
	 * Display the specified resource.
	 *
	 * @param Material $material
	 * @return Material
	 */
	public function show(Material $material) {

		$material->load($this->visibleWithAttributes());

		return $this->materialUserRankingService->present($material, Auth::user());
	}

	/**
	 * Update the specified material in storage.
	 *
	 * @param MaterialRequest $request
	 * @param Material $material
	 * @return Material|null
	 * @throws InvalidKeywordTypeException
	 */
	public function update(MaterialRequest $request, Material $material) {

		$hasLegacyRating = $request->exists('rating');
		$legacyRating    = $request->input('rating');
		$material->fill($request->except('rating'));
		if ($request->has('is_public')) {
			$material->is_public = $request->boolean('is_public');
		}

		if ($request->has('author')) {
			$this->fillAuthor($request->get('author'), $material);
		}

		$material->save();

		$this->syncKeywords($request, $material);
		$this->syncBibleverses($request, $material);

		if ($hasLegacyRating) {
			if ($legacyRating === NULL) {
				$this->materialUserRankingService->remove($material, Auth::user());
			} else {
				$this->materialUserRankingService->set($material, Auth::user(), (int)$legacyRating);
			}
		}

		event(new MaterialWasChanged($material));

		return $this->materialUserRankingService->present($material->fresh($this->visibleWithAttributes()), Auth::user());
	}

	/**
	 * Remove the specified material from storage.
	 *
	 * @param Material $material
	 * @return array
	 * @throws Exception
	 */
	public function destroy(Material $material) {

		$this->materialHandlingService->deleteMaterialAndDetachAssociations($material);

		return ['success' => TRUE];
	}

	/**
	 * @param Material $material
	 * @return Material
	 */
	public function copy(Material $material) {
		return $this->materialUserRankingService->present($this->materialHandlingService->copyMaterial($material)->fresh($this->visibleWithAttributes()), Auth::user());
	}

	public function createPublicZipDownload(Material $material, Request $request, MaterialDownloadStore $downloads) {
		$validated = $request->validate(['expires_in_days' => ['sometimes', 'integer', 'between:1,365']]);
		$days = (int)($validated['expires_in_days'] ?? config('material_downloads.default_retention_days', 28));
		$until = now()->addDays($days);
		$token = bin2hex(random_bytes(32));

		$downloads->create($token, $material->id, Auth::id(), $until->toIso8601String());
		DeleteMaterialDownload::dispatch($token)->delay($until);
		GenerateMaterialDownload::dispatch($material->id, Auth::id(), $token);

		return response()->json([
			'success' => TRUE,
			'status' => 'pending',
			'status_url' => route('api.v1.materials.downloadStatus', ['material' => $material, 'token' => $token], FALSE),
			'until' => $until,
		], 202);
	}

	public function downloadStatus(Material $material, string $token, MaterialDownloadStore $downloads) {
		$data = $downloads->read($token);
		abort_if($data === NULL || $data['material_id'] !== $material->id || $data['user_id'] !== Auth::id(), 404);
		abort_if(now()->greaterThanOrEqualTo($data['until']), 410);

		$status = $data['status'] === 'ready' && !is_file($downloads->readyPath($token)) ? 'failed' : $data['status'];
		return [
			'success' => TRUE,
			'status' => $status,
			'link' => $status === 'ready' ? $downloads->url($token) : NULL,
			'until' => $data['until'],
		];
	}
}
