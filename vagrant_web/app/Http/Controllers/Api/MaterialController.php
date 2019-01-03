<?php

namespace App\Http\Controllers\Api;

use App\Events\MaterialWasChanged;
use App\Events\MaterialWasCreated;
use App\Http\Requests\MaterialRequest;
use App\Models\Material;
use App\Services\MaterialHandling\MaterialHandlingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;


class MaterialController extends BaseController {

	use MaterialHelperTrait, ResourceMaterialTrait;

	protected $withAttributes = [];
	protected $bibleVerseService;
	protected $materialHandlingService;

	public function __construct(BibleVerseService $bibleVerseService, MaterialHandlingService $materialHandlingService) {

		$this->bibleVerseService       = $bibleVerseService;
		$this->materialHandlingService = $materialHandlingService;

		$this->withAttributes = [
			'keywords'    => function ($q) {
				$q->orderBy('keyword_material.relevance', 'desc');
			},
			'bibleverses' => function ($q) {
				$q->orderBy('bibleverse_material.relevance', 'desc');
			},
			'resources',
			'creator',
			'author'];

		$this->middleware(['auth:api']);
	}

	/**
	 * Display a listing of the material.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {

		$materials = Material::where('created_by', Auth::id())
							 ->with($this->withAttributes)
							 ->orderBy('updated_at')
							 ->paginate(50);

		return $materials;
	}


	/**
	 * Store a newly created material in storage.
	 *
	 */
	public function store(Request $request) {

		$material              = new Material($request->all());
		$material->created_by  = Auth::id();
		$material->modified_by = Auth::id();
		$material->from_bot    = $request->get('from_bot', TRUE);


		$this->fillAuthor($request->get('author'), $material);
		// necessary to assign keywords and bibleverses
		$material->save();

		$this->syncKeywords($request, $material);
		$this->syncBibleverses($request, $material);

		// Reload from DB with Relations
		$material = $material->fresh($this->withAttributes);

		event(new MaterialWasCreated($material));

		return $material;
	}

	/*
		public function associateResources(Material $material, Request $request) {

			$resourceIds         = $request->get('resource_id', []);
			$response            = $this->doSync($material, $resourceIds);
			$response['success'] = TRUE;

			return $response;
		}
	*/

	/**
	 * Display the specified resource.
	 *
	 * @param  Material $material
	 * @return Material
	 */
	public function show(Material $material) {

		$material->load($this->withAttributes);

		return $material;
	}

	/**
	 * Update the specified material in storage.
	 *
	 * @param MaterialRequest $request
	 * @param  Material       $material
	 * @return Material|null
	 * @throws \App\Models\Exceptions\InvalidKeywordTypeException
	 */
	public function update(MaterialRequest $request, Material $material) {

		$material->fill($request->all());
		$this->fillAuthor($request->get('author'), $material);
		$material->save();

		$this->syncKeywords($request, $material);
		$this->syncBibleverses($request, $material);

		event(new MaterialWasChanged($material));

		return $material->fresh($this->withAttributes);
	}

	/**
	 * Remove the specified material from storage.
	 *
	 * @param  Material $material
	 * @return array
	 * @throws \Exception
	 */
	public function destroy(Material $material) {

		$this->materialHandlingService->deleteMaterialAndDetachAssociations($material);

		return ['success' => TRUE];
	}

	/**
	 * @param Material $material
	 * @return Material
	 */
	public function copy(Material $material) {
		return $this->materialHandlingService->copyMaterial($material);
	}
}
