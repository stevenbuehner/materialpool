<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\MaterialResourceRequest;
use App\Jobs\CheckLonelyMaterial;
use App\Jobs\CheckLonelyResource;
use App\Models\ForeignMaterialId;
use App\Models\Resource;
use App\ResourceLimitations\InvalidLimitationRequestException;
use App\ResourceLimitations\LimitationNotApplicableForResource;
use App\ResourceLimitations\ResourceLimitationService;
use Illuminate\Routing\Controller as BaseController;

class ResourceMaterialController extends BaseController {

	protected $limitationService;

	public function __construct(ResourceLimitationService $limitationService) {
		$this->middleware(['auth:api']);
		$this->limitationService = $limitationService;
	}

	public function attach(ForeignMaterialId $foreignMaterialId, Resource $resource, MaterialResourceRequest $request) {

		$material   = $foreignMaterialId->material;
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

	public function detach(ForeignMaterialId $foreignMaterialId, Resource $resource) {
		$material = $foreignMaterialId->material;

		$material->resources()->detach($resource->id);

		CheckLonelyResource::dispatch($resource);
		CheckLonelyMaterial::dispatch($material);
	}

}
