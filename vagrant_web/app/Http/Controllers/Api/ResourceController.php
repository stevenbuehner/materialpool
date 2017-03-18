<?php

namespace App\Http\Controllers\Api;

use App\Models\Resource;
use Illuminate\Routing\Controller as BaseController;

class ResourceController extends BaseController {


	/**
	 * Display the specified resource.
	 *
	 * @param  Resource $resource
	 * @return Resource
	 */
	public function show(Resource $resource) {
		return $resource;
	}

}
