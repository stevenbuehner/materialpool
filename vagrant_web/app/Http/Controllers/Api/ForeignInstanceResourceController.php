<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\File;
use App\Models\ForeignInstance;
use App\Models\ForeignResourceKey;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ForeignInstanceResourceController extends BaseController {


	/**
	 * Display a listing of the resource.
	 *
	 */
	public function index(ForeignInstance $foreignInstance = NULL) {

		if ($foreignInstance->exists === FALSE) {
			return response()->json(['error' => 'Invalid foreinInstance id.'], 404);
		}

		DB::enableQueryLog();
		$resources = $foreignInstance
			->resources()
			->with(['foreignResourceKeys' => function ($query) use ($foreignInstance) {
				// Only load foreign_key from this $foreignInstance
				$query->where('foreign_resource_keys.foreign_instance_id',
							  $foreignInstance->id);
			}])
			->orderBy('resource_id')
			->paginate(50);


		$hidden = ['foreign_instance_id', 'resource_id'];
		if ($resources->count() > 0) {
			$first  = $resources->first();
			$hidden = array_merge($first->foreignResourceKeys->first()->getHidden(), $hidden);
		}

		$resources->each(function ($r) use (&$hidden) {
			$r->foreignResourceKeys->each(function ($frk) use (&$hidden) {
				$frk->setHidden($hidden);
			});
		});

		return $resources;
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int $foreignInstanceId
	 * @param  int $resourceId
	 * @return \Illuminate\Http\Response
	 */
	public function show($foreignInstanceId, $remoteId) {
		$xx = ForeignResourceKey::findOneWhere($foreignInstanceId, NULL, $remoteId);

		return $xx->resource;
	}


	public function store(Request $request, ForeignInstance $foreignInstance, $type) {

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

		$class                = Resource::getSingleTableClass($type);
		$resource             = $class !== NULL ? new $class : new Resource();
		$resource->created_by = $foreignInstance->user_id;


		$this->validate($request, $class::getValidationRules());

		// All Parameters required for the Resource
		$resource->fill($request->all());

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

				$localFile = $disk->putFile($localFilePath, $file);

				if ($localFile === FALSE) {
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

	/**
	 * @param int $foreignInstanceId
	 * @param int $remoteResourceId
	 */
	public function destroy($foreignInstanceId, $remoteResourceId) {
		$key = ForeignResourceKey::findOneWhere($foreignInstanceId, NULL, $remoteResourceId);
		$key->delete();

		return Resource::destroy($key->resource_id);
	}

}
