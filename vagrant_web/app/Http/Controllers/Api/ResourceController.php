<?php

namespace App\Http\Controllers\Api;

use App\Models\File;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ResourceController extends BaseController {


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
		if ($resource->created_by != Auth::id()) {
			response('Wrong user', 404);
		}

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

		if (Auth::guest()) {
			response('No user given', 404);
		}

		$resource = $this->getResourceFromRequest($request);

		$this->handleResourceFileUpload($resource, $request);

		$this->assignFieldValues($resource, $request);

		$resource->save();

		return $resource;
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

				$file                        = $request->file('file');
				$resource->original_filename = $file->getClientOriginalName();

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

				$resource->local_path = $diskName . '::' . $localFile;

			} catch (\Exception $e) {

				if (!empty($resource->local_path) && $disk->exists($resource->local_path)) {
					$disk->delete($resource->local_path);
				}

				throw $e;
			}
		}
	}

	protected function assignFieldValues(Resource $resource, Request $request) {
		$resource->fill($request->all());
	}

	public function update(Request $request, Resource $resource) {
		if ($resource->creator->id !== Auth::id()) {
			return response('you are not the owner', 404);
		}

		$resource->fill($request->all());
		$this->handleResourceFileUpload($resource, $request);
		$resource->save();

		return $resource;
	}


}
