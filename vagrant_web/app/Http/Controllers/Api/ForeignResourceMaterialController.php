<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\MaterialResourceRequest;
use App\Jobs\CheckLonelyMaterial;
use App\Jobs\CheckLonelyResource;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\ResourceLimitations\InvalidLimitationRequestException;
use App\ResourceLimitations\LimitationNotApplicableForResource;
use App\ResourceLimitations\ResourceLimitationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class ForeignResourceMaterialController extends BaseController {

	protected $limitationService;

	public function __construct(ResourceLimitationService $limitationService) {
		$this->middleware(['auth:api']);
		$this->limitationService = $limitationService;
	}

	public function attach(ForeignMaterialId $foreignMaterialId, ForeignResourceId $foreignResourceId, MaterialResourceRequest $request) {

		$material   = $foreignMaterialId->material;
		$resource   = $foreignResourceId->resource;
		$limitation = NULL;

		if ($request->has('limitation.type') && $request->has('limitation.value')) {

			try {
				$this->limitationService->createAndAssignLimitation(
					$request->input('limitation.type'),
					$request->input('limitation.value'),
					$resource,
					$material
				);
			} catch (LimitationNotApplicableForResource $e) {
				return response([])->setStatusCode(405);
			} catch (InvalidLimitationRequestException $e) {
				return response([])->setStatusCode(400);
			}

		} else {
			$material->resources()->syncWithoutDetaching([$resource->id]);
		}

	}

	public function detach(ForeignMaterialId $foreignMaterialId, ForeignResourceId $foreignResourceId) {

		$material = $foreignMaterialId->material;
		$resource = $foreignResourceId->resource;

		$material->resources()->detach($resource->id);

		CheckLonelyResource::dispatch($resource);
		CheckLonelyMaterial::dispatch($material);
	}

	public function sync(ForeignMaterialId $foreignMaterialId, Request $request) {

		$resourceIds = $request->get('resource_id', []);

		foreach ($resourceIds as $id) {
			// Nicht getestet =>
			$foreignResource = ForeignResourceId::has('resource')->findOrFail($id);
			// <= Nicht getestet

			$resource = $foreignResource->resource;

			if (\Auth::user()->cannot('view', $resource)) {
				return response([])->setStatusCode(403);
			}
		}

		$material = $foreignMaterialId->material;

		$material->resources()->sync($resourceIds);
	}

}
