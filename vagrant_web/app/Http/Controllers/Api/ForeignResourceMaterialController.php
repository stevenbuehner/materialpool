<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\MaterialResourceRequest;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\ResourceLimitations\ResourceLimitationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class ForeignResourceMaterialController extends BaseController {

	use ResourceMaterialTrait;


	public function __construct(ResourceLimitationService $limitationService) {
		$this->middleware(['auth:api']);
		$this->setResourceLimitationService($limitationService);
	}

	public function attach(ForeignMaterialId $foreignMaterialId, ForeignResourceId $foreignResourceId, MaterialResourceRequest $request) {


		$material = $foreignMaterialId->material;
		$resource = $foreignResourceId->resource;

		$this->doAttach($material, $resource, $request);

	}

	public function detach(ForeignMaterialId $foreignMaterialId, ForeignResourceId $foreignResourceId) {

		$material = $foreignMaterialId->material;
		$resource = $foreignResourceId->resource;

		$this->doDetach($material, $resource);

	}

	public function sync(ForeignMaterialId $foreignMaterialId, Request $request) {

		$material    = $foreignMaterialId->material;
		$resourceIds = $request->get('resource_id', []);

		$this->doSync($material, $resourceIds);

	}

}
