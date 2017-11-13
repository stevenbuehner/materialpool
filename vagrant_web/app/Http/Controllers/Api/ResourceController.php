<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ResourceHelperTrait;
use App\Jobs\UpdateResourceHashes;
use App\Models\File;
use App\Models\ForeignMaterialId;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ResourceController extends BaseController {

	use ResourceHelperTrait;

	public function __construct() {
		$this->middleware(['auth:api']);
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

	public function find(Request $request) {
		if (Auth::guest()) {
			response('No user given', 404);
		}

		$resourceClass    = Resource::getSingleTableClass($request->get('type', 'res'));
		$query            = $resourceClass::where('created_by', Auth::id());
		$uniqueLimiterSet = FALSE;

		if ($request->has('remote_path')) {
			$query->where('remote_path', $request->get('remote_path', NULL));
		}

		if ($request->has('is_public')) {
			$query->where('is_public', (bool) $request->get('is_public', FALSE));
		}

		if ($request->has('content_hash')) {
			$query->where('content_hash', $request->get('content_hash', NULL));
		}

		return $query->first();
	}

	public function store(Request $request) {

		// Check requirements
		if (!($request->hasFile('file') xor !empty($request->get('content', NULL)))) {
			return response()->json([
										'success' => FALSE,
										'error'   => 'Files XOR Content!'
									])
							 ->setStatusCode(409);
		}

		$uid = $request->get('foreign_material_id', NULL);
		if ($request->get('create_material_from_resource', FALSE) === TRUE) {

			if (empty($uid)) {
				return response()->json([
											'success' => FALSE,
											'error'   => 'Missing parameter foreign_material_id when using create_material_from_ressource=TRUE'
										])
								 ->setStatusCode(409);
			}

			$fid = ForeignMaterialId::where(
				['foreign_id' => $uid,
				 'user_id'    => Auth::id()]
			)->first();

			if ($fid !== NULL) {
				return response()->json([
											'success' => FALSE,
											'error'   => 'The ForeignMaterialID for this user exists already'
										])
								 ->setStatusCode(409);
			}
		}


		if ($request->hasFile('file')) {
			$resource = $this->handleResourceUpload($request);
		} else {
			$resource = $this->handleResourceContent($request);
		}


		if ($request->get('create_material_from_resource', FALSE) === TRUE) {
			$material = $this->createMaterialFromResources($resource);

			$fid = ForeignMaterialId::create(
				['user_id'     => Auth::id(),
				 'foreign_id'  => $uid,
				 'material_id' => $material->id]
			);
		}


		// Also load attributes that have not been touched (like remote_path)
		return $resource->fresh();

	}

	public function update(Request $request, Resource $resource) {

		$resource->fill($request->all());
		$this->handleResourceFileUpload($resource, $request);
		$resource->save();

		return $resource;
	}

	/**
	 * @param Resource $resource
	 * @param Request  $request
	 * @throws \Exception
	 */
	protected function handleResourceFileUpload(Resource $resource, Request $request) {
		$diskName = config('app.disks.resources');
		$disk     = Storage::disk('resources');

		if ($request->hasFile('file')) {

			if (!$resource instanceof File) {
				throw new \Exception("ResourceType is not a filetype");
			}

			try {

				$file = $request->file('file');

				// $extension     = $file->getClientOriginalExtension();
				$localFilePath = $resource->created_by . DIRECTORY_SEPARATOR . $resource->type;

				$localFile = $disk->putFile($localFilePath, $file);

				if ($localFile === FALSE) {
					throw new \Exception('File was not stored');
				}

				// Delete existing file of Resource if available
				/** @var File $resource */
				if ($resource->hasLocalFile()) {
					$resource->deleteLocalFile();
				}

				$resource->local_path        = $diskName . '::' . $localFile;
				$resource->original_filename = $file->getClientOriginalName();

				// FIXME: If Sync-Queue is used, $resource has not been Stored yet and the Process loads a new instance into $resource (with the old paths)
				if (!$resource->exists) {
					$resource->save();
				}
				dispatch(new UpdateResourceHashes($resource));

			} catch (\Exception $e) {

				if (!empty($resource->local_path) && $disk->exists($resource->local_path)) {
					$disk->delete($resource->local_path);
				}

				throw $e;
			}
		}
	}

	/**
	 * @param Request $request
	 * @return Resource
	 */
	protected function getResourceFromRequest(Request $request) {
		$class = Resource::getSingleTableClass($request->get('type', ''));

		if ($class === NULL) {
			if ($request->hasFile('file')) {
				$class = File::class;
			} else {
				$class = Resource::class;
			}
		}

		/** @var Resource $resource */
		$resource = new $class();
		$resource->creator()->associate(Auth::user());

		return $resource;
	}

	protected function assignFieldValues(Resource $resource, Request $request) {
		$resource->fill($request->all());
	}


}
