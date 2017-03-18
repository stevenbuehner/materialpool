<?php

namespace App\Http\Controllers\Api;

use App\Models\File;
use App\Models\ForeignInstance;
use App\Models\ForeignResourceKey;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Validator;

class ResourceController extends BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 */
	public function index(ForeignInstance $foreignInstance = NULL) {

		if ($foreignInstance->exists === FALSE) {
			return response()->json(['error' => 'Invalid foreinInstance id.'], 404);
		}

		/** @var LengthAwarePaginator $resources */
		$resources = $foreignInstance->foreignResourceKeys()->paginate(50);


		$subset = $resources->map(function ($fi) {
			return collect($fi->toArray())
				->forget('foreign_instance_id')
				->all();
		});

		$resources->setCollection($subset);

		return $resources;
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int $foreignInstanceId
	 * @param  int $resourceId
	 * @return \Illuminate\Http\Response
	 */
	public function showByRemoteId($foreignInstanceId, $resourceId) {
		$xx = ForeignResourceKey::where('foreign_instance_id', '=', $foreignInstanceId)
								->where('remote_id', '=', $resourceId)
								->first();

		return $xx->resource;
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  Resource $resource
	 * @return Resource
	 */
	public function show(Resource $resource) {
		return $resource;
	}


	public function addByRemoteId(Request $request, ForeignInstance $foreignInstance, $type) {

		// The Remote Id is necessary for assignment
		if ($request->has('remote_id')) {
			$existingResource = $foreignInstance->foreignResourceKeys()
												->where('remote_id', '=', $request->get('remote_id'))->first();

			if ($existingResource !== NULL) {
				return response()->json('You already have a resource with that remote_id. Please delete or update resource instead.',
										400);
			}
		} else {
			return response()->json('remote_id is required', 400);
		}

		$class    = Resource::getSingleTableClass($type);
		$resource = $class !== NULL ? new $class : new Resource();

		$validator = Validator::make($request->all(), $class::getValidationRules());

		if ($validator->fails()) {
			return response()->json($validator->getMessageBag()->toArray(), 400);
		}

		// All Parameters required for the Resource
		$resource->fill($validator->getData());

		$disk = Storage::disk('resources');

		if ($request->hasFile('file')) {
			try {
				DB::beginTransaction();
				/** @var File $resource */
				$resource->save();

				$file                        = $request->file('file');
				$resource->original_filename = $file->getClientOriginalName();

				// $extension     = $file->getClientOriginalExtension();
				$localFilePath = $foreignInstance->id . DIRECTORY_SEPARATOR . $resource->type;

				$localFile            = $disk->putFile($localFilePath, $file);

				if($localFile === false){
					throw new \Exception('File was not stored');
				}

				$resource->local_path = 'resources::' . $localFile;
				$resource->save();

				$key = new ForeignResourceKey([
												  'remote_id'           => $request->get('remote_id'),
												  'foreign_instance_id' => $foreignInstance->id,
												  'resource_id'         => $resource->id
											  ]);
				$key->save();

				DB::commit();

			} catch (\Exception $e) {
				DB::rollBack();

				if (!empty($resource->local_path) && $disk->exists($resource->local_path)) {
					$disk->delete($resource->local_path);
				}
			}
		}


		return $resource;
	}

}
