<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\MaterialResourceRequest;
use App\Jobs\CheckLonelyMaterial;
use App\Jobs\CheckLonelyResource;
use App\Models\ForeignResourceId;
use App\Models\Material;
use App\Models\Resource;
use App\ResourceLimitations\InvalidLimitationRequestException;
use App\ResourceLimitations\LimitationNotApplicableForResource;
use App\ResourceLimitations\ResourceLimitationService;

trait ResourceMaterialTrait {

	/** @var $limitationService ResourceLimitationService */
	protected $limitationService;

	protected function doAttach(Material $material, Resource $resource, MaterialResourceRequest $request) {

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
				return response(['error' => $e->getMessage()])->setStatusCode(405);
			} catch (InvalidLimitationRequestException $e) {
				return response([['error' => $e->getMessage()]])->setStatusCode(400);
			}

		} else {
			$material->resources()->syncWithoutDetaching([$resource->id => ['limitation' => NULL]]);
		}

		return $this->getFreshMatAndResource($material, $resource);
	}

	protected function getFreshMatAndResource(Material $material, Resource $resource) {
		return [
			'material' => $material->fresh(\App\Http\Controllers\MaterialController::withAttributes()),
			'resource' => $resource->fresh(ResourceController::DEFAULT_RELATIONS)];
	}

	protected function doDetach(Material $material, Resource $resource) {

		$material->resources()->detach($resource->id);

		CheckLonelyResource::dispatch($resource);
		CheckLonelyMaterial::dispatch($material);

		return $this->getFreshMatAndResource($material, $resource);
	}

	protected function doSync(Material $material, $resourceIds) {

		foreach ($resourceIds as $id) {
			// Nicht getestet =>
			$foreignResource = ForeignResourceId::has('resource')->findOrFail($id);
			// <= Nicht getestet

			$resource = $foreignResource->resource;

			if (\Auth::user()->cannot('view', $resource)) {
				return response([])->setStatusCode(403);
			}
		}

		$material->resources()->sync($resourceIds);

		return $this->getFreshMatAndResource($material, $resource);

	}

	protected function setResourceLimitationService(ResourceLimitationService $limitationService) {
		$this->limitationService = $limitationService;
	}

}
