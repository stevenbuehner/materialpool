<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\KeywordRequest;
use App\Http\Requests\MaterialRequest;
use App\Models\Bibleverse;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Person;
use App\Models\Resource;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;


class MaterialController extends BaseController {

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

		return view('materials.listing', compact('materials'));
	}


	/**
	 * Store a newly created material in storage.
	 *
	 */
	public function store(Request $request) {

		$material              = new Material($request->all());
		$material->created_by  = Auth::id();
		$material->modified_by = Auth::id();
		$material->from_bot    = TRUE;


		$this->fillAuthor($request, $material);
		// necessary to assign keywords and bibleverses
		$material->save();

		$this->fillKeywords($request, $material);
		$this->fillBibleverses($request, $material);

		// Reload from DB with Relations
		$material = $material->fresh($this->withAttributes);

		return $material;
	}

	protected function fillAuthor(Request $request, Material $material) {

		if ($request->has('author')) {
			$author = Person::firstOrCreate(['title' => trim($request->get('author'))]);
			$material->author()->associate($author);
		}
	}

	protected function fillKeywords(Request $request, Material $material) {
		$keywordIds = [];

		if ($request->has('keywords')) {

			$keywordRequestRules = (new KeywordRequest())->rules();

			if (is_array($request->get('keywords'))) {

				foreach ($request->get('keywords') as $keyword) {
					$validator = Validator::make($keyword, $keywordRequestRules);

					if ($validator->valid()) {
						$data  = $validator->getData();
						$type  = isset($data['type']) ? $data['type'] : NULL;
						$class = Keyword::getSingleTableClass($type);

						if ($class !== NULL) {
							/** @var Keyword $kw */
							$kw                  = $class::firstOrCreate(['title' => $data['title']]);
							$relevance           = isset($keyword['relevance']) ? $keyword['relevance'] : RelevanceInterface::RELEVANCE_EXIF_MAX;
							$keywordIds[$kw->id] = ['relevance' => $relevance];
						}
					}
				}

			}

			$material->keywords()->sync($keywordIds);
		}

		return count($keywordIds);

	}

	protected function fillBibleverses(Request $request, Material $material) {
		$bibleverseIds = [];

		if ($request->has('bibleverses')) {

			if (is_array($request->get('bibleverses'))) {

				foreach ($request->get('bibleverses') as $bibleverseData) {
					if (isset($bibleverseData['from']) && isset($bibleverseData['to'])) {
						$bv = Bibleverse::firstOrNew(
							[
								'from' => $bibleverseData['from'],
								'to'   => $bibleverseData['to']
							]
						);

						if ($this->bibleVerseService->isBibleVerseValid($bv)) {
							$bv->save();
							$relevance              = isset($bibleverseData['relevance']) ? $bibleverseData['relevance'] : RelevanceInterface::RELEVANCE_EXIF_MAX;
							$bibleverseIds[$bv->id] = ['relevance' => $relevance];
						}

					}
				}

			}

			$material->bibleverses()->sync($bibleverseIds);
		}

		return count($bibleverseIds);
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
		$this->fillAuthor($request, $material);
		$material->save();

		$this->fillKeywords($request, $material);
		$this->fillBibleverses($request, $material);


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
