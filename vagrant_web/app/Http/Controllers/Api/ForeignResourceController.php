<?php

namespace App\Http\Controllers\Api;

use App\Models\ForeignResourceId;
use App\Models\Resource;
use Illuminate\Http\Request;

class ForeignResourceController extends ResourceController {


	public function showForeign(ForeignResourceId $foreignResourceId) {

		return $foreignResourceId;
	}

	public function storeForeign(Request $request) {

		$validatedData = $request->validate(
			[
				'id' => 'bail|required|string|min:3|max:191'
			]
		);

		// Check if a resource with this foreignResourceKey exists already

		$foreignResource = ForeignResourceId::where([
														'user_id'    => \Auth::id(),
														'foreign_id' => $validatedData['id']
													])->get()->first();

		if ($foreignResource !== NULL) {
			return response()->json([
										'success' => FALSE,
										'error'   => 'The ForeignResourceId for this user exists already'
									])
							 ->setStatusCode(409);
		}

		$resource = $this->store($request);

		if (!$resource instanceof Resource) {
			return $resource;
		}

		$foreignResourceId = ForeignResourceId::create([
														   'user_id'     => $resource->created_by,
														   'resource_id' => $resource->id,
														   'foreign_id'  => $validatedData['id']
													   ]);

		return $foreignResourceId;
	}


	public function updateForeign(Request $request, ForeignResourceId $foreignResourceId) {
		$resource = $foreignResourceId->resource;

		$otherUsersWithThisResource = $resource->foreignIds()->where('user_id', '!=', $foreignResourceId->user_id)
											   ->first();
		if ($otherUsersWithThisResource !== NULL) {
			// ToDo: We need to copy the resource and update only the copy
			$resource = NULL;
		}

		$resource = $this->update($request, $resource);

		if (!$resource instanceof Resource) {
			return $resource;
		}

		return $foreignResourceId->fresh('resource');
	}

	public function destroyForeign(ForeignResourceId $foreignResourceId) {

		$resource = $foreignResourceId->resource;

		$allForeignIds = $resource->foreignIds;

		if ($allForeignIds->count() >= 2) {
			// Only delete this ForeignResourceId
			$foreignResourceId->delete();

			return [];
		} else {
			return $this->destroy($resource);
		}
	}


}
