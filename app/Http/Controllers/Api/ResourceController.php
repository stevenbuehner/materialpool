<?php

namespace App\Http\Controllers\Api;

use App\Events\ResourceWasChanged;
use App\Http\Controllers\ResourceHelperTrait;
use App\Models\File;
use App\Models\ForeignMaterialId;
use App\Models\Material;
use App\Models\Resource;
use App\Services\ResourceHandling\Exceptions\ResourceNotReplaceable;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\ResourceHandlingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ResourceController extends BaseController {

	use ResourceHelperTrait;

	const DEFAULT_RELATIONS = ['materials', 'materials.keywords', 'materials.bibleverses', 'creator'];
	protected $allowedAssociations = ['materials', 'materials.keywords', 'materials.bibleverses', 'creator'];
	protected $fileHandlingService;
	protected $resourceHandlingService;

	public function __construct(FileHandlingService $fileHandlingService, ResourceHandlingService $resourceHandlingService) {
		$this->middleware(['auth:api']);

		$this->fileHandlingService     = $fileHandlingService;
		$this->resourceHandlingService = $resourceHandlingService;
	}

	/**
	 * Display the specified resource.
	 *
	 * @param Resource $resource
	 * @param Request $request
	 * @return Resource
	 */
	public function show(Resource $resource, Request $request) {
		return $this->useRelations($resource, $request);
	}

	protected function useRelations($resource, Request $request) {

		if (!$resource instanceof Resource) {
			return $resource;
		}

		$useRelations = [];

		if ($request->has('relations')) {
			foreach ($request->get('relations', []) as $rel) {
				if (in_array($rel, $this->allowedAssociations)) {
					$useRelations[] = $rel;
				}
			}
		} else {
			$useRelations = self::DEFAULT_RELATIONS;
		}

		return $resource->load($this->visibleRelations($useRelations));

	}

	public function find(Request $request) {

		/** @var \Illuminate\Validation\Validator $validator */
		$validator = Validator::make($request->all(), [
			'id'           => 'bail|numeric|min:1',
			'remote_path'  => 'bail|nullable|string',
			'is_public'    => 'bail|boolean',
			'content_hash' => 'bail|string|min:191|max:191',
			'ignore_ids.*' => 'bail|numeric|distinct',
			'order_by'     => 'bail|string|in:created_at,updated_at,id',
			'order_dir'    => 'bail|string|in:asc,desc',
		])->validate();


		/** @var Builder $builder */
		// $builder = Resource::query();
		$builder = Resource::query()->visibleTo(Auth::user());

		if ($request->has('id')) {
			$builder->where('id', $request->get('id'));
		}

		if ($request->has('remote_path')) {
			$builder->where('remote_path', $request->get('remote_path', NULL));
		}

		if ($request->has('is_public')) {
			$builder->where('is_public', (bool)$request->get('is_public', FALSE));
		}

		if ($request->has('content_hash')) {
			$builder->where('content_hash', $request->get('content_hash', NULL));
		}

		if ($request->get('missing_materials', FALSE) !== FALSE) {
			// Macht einen Subselect => Super zeitaufwendig (doch nach dem anlegen neuer Indixes wieder performant genug)
			$builder->doesntHave('materials');

			/*
			$tr = new Resource();
			// $tm = new Material();

			$first = $tr->materials()->getParentKeyName();
			$seond = $tr->materials()->getRelatedKeyName();
			$third = $tr->materials()->getForeignPivotKeyName();
			$fourth = $tr->materials()->getRelatedPivotKeyName();


			$builder->leftJoin($tr->materials()->getTable(),
							   $tr->materials()->getParentKeyName(),
							   '=',
							   $tr->materials()->getForeignPivotKeyName());
			*/
		}


		if ($request->has('ignore_ids')) {
			$builder->whereNotIn('id', $request->get('ignore_ids', []));
		}

		$builder->orderBy($request->get('order_by', 'id'), $request->get('order_dir', 'asc'));

		$blub = $builder->with($this->visibleRelations(self::DEFAULT_RELATIONS))->paginate(25);

		// $log = DB::getQueryLog();

		return $blub;
	}

	public function store(Request $request) {
		if ($request->boolean('create_material_from_resource')) {
			Gate::authorize('create', Material::class);
		}

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
		return $resource->fresh($this->visibleRelations(self::DEFAULT_RELATIONS));

	}


	public function createMaterialFromResourceIds(Request $request) {

		$meta        = $request->get('meta', '');
		$resourceIds = $request->get('resourceIds', []);
		$resources   = Resource::findMany($resourceIds);

		$resources->each(fn(Resource $resource) => Gate::authorize('view', $resource));

		$material = $this->createMaterialFromResources($resources->all(), $meta);

		if ($request->has('from_bot')) {
			$material->from_bot = $request->get('from_bot'); // Is casted in $material
		}

		$material->created_by  = Auth::id();
		$material->modified_by = Auth::id();

		$material->save();

		return $material->fresh(\App\Http\Controllers\MaterialController::withVisibleAttributes(Auth::user()));

	}


	/**
	 * @param Request $request
	 * @param \App\Models\Resource $resource
	 * @return Resource
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public function update(Request $request, Resource $resource) {

		$lastUpdated = $resource->updated_at;

		$this->validateResourceRequest($request, get_class($resource), $allowPartialUpdate = TRUE);


		try {
			if ($request->hasFile('file') && $resource instanceof File) {
				$resource = $this->handleSingleResourceFileData($request, $resource);
			} else if ($request->has('content')) {
				$resource = $this->handleContentResourceUpload($request, $resource);
			} else {
				$resource = $this->handleGerneralResourceAttributes($resource, $request);

				event(new ResourceWasChanged($resource));
			}

		} catch (\Exception $e) {
			return response(['message' => $e->getMessage()])->setStatusCode(500);
		}

		$resource = $resource->fresh($this->visibleRelations(self::DEFAULT_RELATIONS));

		return $resource;
	}

	/**
	 * @param Resource $resource
	 * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
	 */
	public function destroy(Resource $resource) {

		$t = Resource::where('id', '=', $resource->id)->has('materials')->get();

		if ($t->count() >= 1) {
			return response(['message' => 'Resource is assigned to materials. Please delete materials first.'])
				->setStatusCode(409);

		}

		$this->fileHandlingService->deleteResourceCompletely($resource);

		return ['success' => TRUE];
	}

	public function replace(Resource $oldResource, Resource $newResource) {

		try {
			$resource = $this->resourceHandlingService->replaceResource($oldResource, $newResource, Auth::user());
		} catch (ResourceNotReplaceable $e) {
			return response()->json(['success' => FALSE, 'message' => $e->getMessage()])->setStatusCode(500);
		}

		$resource->loadMissing($this->visibleRelations(self::DEFAULT_RELATIONS));
		return $resource;
	}

	/**
	 * Eine sichtbare Ressource kann einem für den aktuellen Benutzer unsichtbaren
	 * Material zugeordnet sein. Deshalb wird die Relation in jeder API-Antwort
	 * eingeschränkt geladen.
	 */
	protected function visibleRelations(array $relations): array {
		$loadsMaterials = false;

		foreach ($relations as $key => $relation) {
			$name = is_int($key) ? $relation : $key;
			$loadsMaterials = $loadsMaterials || (is_string($name) && str_starts_with($name, 'materials'));
		}

		if ($loadsMaterials) {
			$relations = array_filter($relations, static fn($relation): bool => $relation !== 'materials');
			$relations['materials'] = fn($query) => $query->visibleTo(Auth::user());
		}

		return $relations;
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
