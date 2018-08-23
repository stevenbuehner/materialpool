<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ResourceHelperTrait;
use App\Models\File;
use App\Models\ForeignMaterialId;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class ResourceController extends BaseController {

	use ResourceHelperTrait;

	protected $allowedAssociations = ['materials', 'materials.keywords', 'materials.bibleverses'];


	public function __construct() {
		$this->middleware(['auth:api']);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  Resource $resource
	 * @param Request   $request
	 * @return Resource
	 */
	public function show(Resource $resource, Request $request) {
		return $this->useRelations($resource, $request);
	}

	protected function useRelations($resource, Request $request) {

		if ($resource === NULL) {
			return $resource;
		}

		$useRelations = [];

		foreach ($request->get('relations', []) as $rel) {
			if (in_array($rel, $this->allowedAssociations)) {
				$useRelations[] = $rel;
			}
		}

		return $resource->load($useRelations);

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

		$this->validateResourceRequest($request, Resource::class, $allowPartialUpdate = FALSE);


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
			$resource = $this->handleSingleResourceFileData($request);
		} else {
			$resource = $this->handleContentResourceUpload($request);
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

	/**
	 * @param Request              $request
	 * @param \App\Models\Resource $resource
	 * @return Resource
	 */
	public function update(Request $request, Resource $resource) {

		$this->validateResourceRequest($request, get_class($resource), $allowPartialUpdate = TRUE);

		try {
			if ($request->hasFile('file')) {
				$resource = $this->handleSingleResourceFileData($request, $resource);
			} else {
				$resource = $this->handleContentResourceUpload($request, $resource);
			}


		} catch (\Exception $e) {
			return response(['message' => $e->getMessage()])->setStatusCode(500);
		}

		return $resource;
	}

	/**
	 * @param Resource $resource
	 */
	public function destroy(Resource $resource) {

		$t = Resource::where('id', '=', $resource->id)->has('materials')->get();

		if ($t->count() >= 1) {
			return response(['message' => 'Resource is assigned to materials. Please delete materials first.'])
				->setStatusCode(409);

		}

		// Delete from filesystem
		if ($resource instanceof File) {
			$resource->deleteLocalFile();
		}

		// Delete in DB
		$resource->foreignIds()->delete();
		$resource->delete();


		return [];
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
