<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\MaterialResourceRequest;
use App\Models\Material;
use App\Models\Resource;
use App\ResourceLimitations\ResourceLimitationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class ResourceMaterialController extends BaseController {

	use ResourceMaterialTrait;

	public function __construct(ResourceLimitationService $limitationService) {
		$this->middleware(['auth:api']);
		$this->setResourceLimitationService($limitationService);
	}

	public function attach(Material $material, Resource $resource, MaterialResourceRequest $request) {

		$material->from_bot = FALSE;
		$material->save();

		return $this->doAttach($material, $resource, $request);

	}

	public function detach(Material $material, Resource $resource) {

		$material->from_bot = FALSE;
		$material->save();

		return $this->doDetach($material, $resource);

	}

	public function sync(Material $material, Request $request) {

		$material->from_bot = FALSE;
		$material->save();

		$resourceIds = $request->get('resource_id', []);

		return $this->doSync($material, $resourceIds);

	}

}
