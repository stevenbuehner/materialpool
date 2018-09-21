<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\MaterialRequest;
use App\Models\Material;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;


class MaterialController extends BaseController {

	use MaterialHelperTrait;

	protected $withAttributes = [];
	protected $bibleVerseService;

	public function __construct(BibleVerseService $bibleVerseService) {

		$this->bibleVerseService = $bibleVerseService;

		$this->withAttributes = [
			'keywords'    => function ($q) {
				$q->orderBy('keyword_material.relevance', 'desc');
			},
			'bibleverses' => function ($q) {
				$q->orderBy('bibleverse_material.relevance', 'desc');
			},
			'resources',
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

		return $material;
	}


	public function associateResources(Material $material, Request $request) {

		$resourceIds = $request->get('resource_id', []);

		// TODO: Check all Resources rights - is this neccessary? - FUnktioniert nicht ?!?
		$allResources = Resource::whereIn('id', [$resourceIds])->get();
		foreach ($allResources as $key => $resource) {
			if ($resource->created_by != Auth::id()) {
				return response('You do not own all of theese resources', 404);
			}
		}

		$material->resources()->sync($resourceIds);

		return ['success' => TRUE];
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  Material $material
	 */
	public function show(Material $material) {

		$material->load($this->withAttributes);

		return $material;
	}

	/**
	 * Update the specified material in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  Material                 $material
	 * @return \Illuminate\Http\Response
	 */
	public function update(MaterialRequest $request, Material $material) {

		$material->fill($request->all());
		$this->fillAuthor($request->get('author'), $material);
		$material->save();

		$this->syncKeywords($request, $material);
		$this->syncBibleverses($request, $material);


		return $material->fresh($this->withAttributes);
	}

	/**
	 * Remove the specified material from storage.
	 *
	 * @param  Material $material
	 */
	public function destroy(Material $material) {

		$material->resources()->detach();
		$material->bibleverses()->detach();
		$material->keywords()->detach();
		$material->delete();

		return TRUE;
	}
}
