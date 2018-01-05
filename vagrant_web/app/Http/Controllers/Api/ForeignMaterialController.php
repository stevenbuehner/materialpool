<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\FullMaterialRequest;
use App\Jobs\CheckLonelyBibleverse;
use App\Jobs\CheckLonelyKeyword;
use App\Jobs\CheckLonelyResource;
use App\Models\Bibleverse;
use App\Models\ForeignMaterialId;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Person;
use App\Models\Resource;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;


class ForeignMaterialController extends BaseController {

	use MaterialHelperTrait;

	protected $withAttributes = [];
	protected $bibleVerseService;

	public function __construct(BibleVerseService $bibleVerseService) {

		$this->bibleVerseService = $bibleVerseService;

		$this->withAttributes = [
			'material.keywords'    => function ($q) {
				$q->orderBy('keyword_material.relevance', 'desc');
			},
			'material.bibleverses' => function ($q) {
				$q->orderBy('bibleverse_material.relevance', 'desc');
			},
			'material.resources',
			'material.author'
		];

		$this->middleware(['auth:api']);
	}

	/**
	 * Display a listing of the material.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {

	}


	/**
	 * Display the specified resource.
	 *
	 * @param  ForeignMaterialId $foreignMaterialId
	 * @return ForeignMaterialId
	 */
	public function show(ForeignMaterialId $foreignMaterialId) {

		return $this->turnForeignMaterialIdIntoCustomFormat($foreignMaterialId);
	}

	/**
	 * @param ForeignMaterialId $foreignMaterialId
	 * @return array
	 */
	public function turnForeignMaterialIdIntoCustomFormat(ForeignMaterialId $foreignMaterialId) {
		/** @var Material $material */
		$material = $foreignMaterialId->material;

		$material->load([
							'keywords',
							'bibleverses',
							'resources.foreignIds' => function ($query) use ($material) {
								$query->where("user_id", "=", $material->created_by);
							}
						]);

		// Remove Resources without foreignResourceId
		$material->resources->each(function () {

		});

		$hidden = ['created_at', 'updated_at', 'icon'];
		$material->bibleverses->each(function (Bibleverse $bv) use (&$hidden) {
			$bv->setHidden($hidden);
		});

		$result                = $material->attributesToArray();
		$result['id']          = $foreignMaterialId->foreign_id;
		$result['keywords']    = $material->keywords;
		$result['bibleverses'] = $material->bibleverses;
		$result['resources']   = $material->resources->reject(function (Resource $resource) {
			$count    = $resource->foreignIds->count();
			$countAll = $resource->foreignIds;

			return $resource->foreignIds->count() == 0;
		});
		$result['author']      = $material->author_id !== NULL ? $material->author->title : NULL;

		return $result;
	}


	/**
	 * @param FullMaterialRequest $request
	 * @param                     $foreignMaterialId
	 */
	public function store(FullMaterialRequest $request, $foreignMaterialId) {

		/** @var ForeignMaterialId $fm */
		$fm = ForeignMaterialId::firstOrNew(
			['foreign_id' => $foreignMaterialId,
			 'user_id'    => Auth::id()]
		);

		if (TRUE === $fm->exists) {
			return response('Material with this id exists already for this user', 409);
		}

		/** @var Material $mat */
		$mat              = new Material(
			array_merge(
				$defaults = ['from_bot' => TRUE],
				$request->all()
			)
		);
		$mat->created_by  = Auth::id();
		$mat->modified_by = Auth::id();

		if ($request->has('author')) {
			$this->fillAuthor($request->get('author'), $mat);
		}

		// necessary to assign keywords and bibleverses
		$mat->save();

		// Foreign-Material-ID gleich mitabspeichern
		$fm->material()->associate($mat)->save();

		$this->syncKeywords($request, $mat);
		$this->syncBibleverses($request, $mat);


		return $this->turnForeignMaterialIdIntoCustomFormat($fm);
	}

	/**
	 * Update the specified material in storage.
	 *
	 * @param  FullMaterialRequest $request
	 * @param  ForeignMaterialId   $foreignMaterialId
	 * @return \Illuminate\Http\Response
	 */
	public function update(FullMaterialRequest $request, ForeignMaterialId $foreignMaterialId) {

		/** @var Material $material */
		$material = Material::withCount('foreignIds')->where(
			['id' => $foreignMaterialId->material_id]
		)->get()->first();

		if ($material->foreign_ids_count > 1) {
			return response('The requested material exists but is connected with multiple other foreignMaterialIds.',
							409);
		}

		// Update data
		$material->fill(
			array_merge(
				$request->all(),
				$override = [
					'modified_by' => Auth::id()
				]
			)
		);


		if ($request->has('author')) {
			$this->fillAuthor($request->get('author'), $material);
		}

		$material->save();

		$this->syncKeywords($request, $material);
		$this->syncBibleverses($request, $material);


		return $this->turnForeignMaterialIdIntoCustomFormat($foreignMaterialId);

	}

	/**
	 * Remove the specified material from storage.
	 *
	 * @param  ForeignMaterialId $foreignMaterialId
	 */
	public function destroy(ForeignMaterialId $foreignMaterialId) {
		/** @var Material $material */
		$material = Material::withCount('foreignIds')->where(
			['id' => $foreignMaterialId->material_id]
		)->get()->first();


		/*
		If this is the only ForeignMaterialId assigned to this material,
		then delete the material and oll the associations to it
		*/
		if ($material->foreign_ids_count == 1) {

			// Store associations for afterward-jobs
			$author      = $material->author;
			$keywords    = $material->keywords;
			$bibleverses = $material->bibleverses;
			$resources   = $material->resources;


			if ($author instanceof Person) {
				$material->author()->dissociate();
				CheckLonelyKeyword::dispatch($author);
			}

			if ($keywords->count() > 0) {
				$material->keywords()->detach();

				$keywords->each(function (Keyword $keyword) {
					CheckLonelyKeyword::dispatch($keyword);
				});
			}

			if ($bibleverses->count() > 0) {
				$material->bibleverses()->detach();

				$bibleverses->each(function (Bibleverse $bibleverse) {
					CheckLonelyBibleverse::dispatch($bibleverse);
				});
			}

			if ($resources->count() > 0) {
				$material->resources()->detach();

				$resources->each(function (Resource $resource) {
					CheckLonelyResource::dispatch($resource);
				});
			}

			$material->delete();
		}


		$foreignMaterialId->delete();

		return ['success' => TRUE];

	}

}
