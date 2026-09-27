<?php

namespace App\Http\Controllers\Api;

use App\Events\MaterialWasChanged;
use App\Http\Requests\MaterialUsageRequest;
use App\Models\Material;
use App\Models\MaterialUsage;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;


class MaterialUsageController extends BaseController {

	protected $usageRelationsToLoad = [
		'usedBy'
	];

	public function __construct() {
		$this->middleware(['auth:api']);
	}

	public function index(Material $material) {

		$material->load(['usages.usedBy']);

		return $material->usages;

	}


	/**
	 * Store a newly created materialusage in storage.
	 *
	 */
	public function store(MaterialUsageRequest $request, Material $material) {

		$mUsage = new MaterialUsage($request->all());

		$mUsage->created_by = Auth::id();
		$mUsage->updated_by = Auth::id();
		$mUsage->material()->associate($material);

		$mUsage->save();

		// Reload from DB with Relations
		// $relations = array_merge($this->usageRelationsToLoad, ['material']);
		event(new MaterialWasChanged($material));

		return $mUsage->fresh($this->usageRelationsToLoad);;
	}


	/**
	 * Update the specified materialusage in storage.
	 *
	 * @param MaterialUsageRequest $request
	 * @param MaterialUsage $materialUsage
	 * @return MaterialUsage|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|object|null
	 */
	public function update(MaterialUsageRequest $request, Material $material, MaterialUsage $materialUsage) {

		if ($material->id !== $materialUsage->material_id) {
			// $materialUsage->material()->associate($material);
			return response(['error' => 'Material-ID missmatch.'])->setStatusCode(400);
		}

		$materialUsage->fill($request->all());

		if ($materialUsage->isDirty()) {
			$materialUsage->updated_by = Auth::id();
			$materialUsage->save();


			// Reload from DB with Relations?
			event(new MaterialWasChanged($material));
		}

		return $materialUsage->fresh($this->usageRelationsToLoad);;

	}

	/**
	 * Remove the specified materialusage from storage.
	 *
	 * @param MaterialUsage $materialUsage
	 * @return array
	 */
	public function destroy(Material $material, MaterialUsage $materialUsage) {

		$success = $materialUsage->delete();

		event(new MaterialWasChanged($material));

		return ['success' => $success];
	}

}
