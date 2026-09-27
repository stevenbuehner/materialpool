<?php

namespace App\Http\Controllers\Api;

use App\Events\MaterialWasChanged;
use App\Events\MaterialWasCreated;
use App\Http\Requests\FullMaterialRequest;
use App\Models\Bibleverse;
use App\Models\Exceptions\InvalidKeywordTypeException;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Resource;
use App\Services\MaterialHandling\MaterialHandlingService;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use App\Services\TagExtraction\MaterialExtractionService;
use App\Services\TagExtraction\Properties\AuthorProperty;
use App\Services\TagExtraction\Properties\KeywordProperty;
use App\Services\TagExtraction\Properties\OcrTextProperty;
use App\Services\TagExtraction\Properties\RatingProperty;
use App\Services\TagExtraction\Properties\TitleProperty;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;


class ForeignMaterialController extends BaseController {

	use MaterialHelperTrait;

	protected $withAttributes = [];
	protected $bibleVerseService;
	protected $materialHandlingService;

	public function __construct(BibleVerseService $bibleVerseService, MaterialHandlingService $materialHandlingService) {

		$this->bibleVerseService       = $bibleVerseService;
		$this->materialHandlingService = $materialHandlingService;

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
	 * @return Response
	 */
	public function index() {

	}


	/**
	 * Display the specified resource.
	 *
	 * @param ForeignMaterialId $foreignMaterialId
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
		$result['bibleverses'] = $material->bibleverses->map(function (Bibleverse $bibleverse) {
			return [
				'from'     => $bibleverse->from,
				'to'       => $bibleverse->to,
				'bible_id' => $bibleverse->bible_id,
				'label'    => $bibleverse->label,
				'pivot'    => [
					'relevance' => $bibleverse->pivot->relevance
				]
			];
		});
		$result['resources']   = $material->resources->reject(function (Resource $resource) {
			return $resource->foreignIds->count() == 0;
		})->map(function (Resource $resource) {

			$result = [
				'id'           => $resource->foreignIDs->first()->foreign_id,
				'remote_path'  => $resource->remote_path,
				'content_hash' => $resource->content_hash,
				'pivot'        => [
					'limitation' => $resource->pivot->limitation
				],
				'other_ids'    => $resource->foreignIds->splice(1)->map(function (ForeignResourceId $fi) {
					return $fi->foreign_id;
				})->toArray()
			];

			return $result;
		});
		$result['author']      = $material->author_id !== NULL ? $material->author->title : NULL;

		return $result;
	}


	public function createFromResources(Request $request, $foreignMaterialId) {

		$foreignMaterialIdObj = ForeignMaterialId::where('foreign_id', $foreignMaterialId)
			->where('user_id', Auth::id())
			->first();

		if ($foreignMaterialIdObj !== NULL) {
			return response('ForeignMaterialId exists already!!!', 409);
		}


		$rules = [
			'title'          => 'bail|string|min:3|max:255',
			'rating'         => 'bail|nullable|numeric|between:0,20',
			'from_bot'       => 'bail|boolean',
			'is_public'      => 'bail|sometimes|boolean',
			'description'    => 'bail|nullable|string',
			'author'         => 'bail|nullable|string|min:2|max:191',
			'resources.*.id' => 'required|max:255',

			'keywords.*.title'     => 'bail|required|string|min:2|max:191',
			'keywords.*.relevance' => 'bail|nullable|numeric|between:0,300',

			'bibleverses.*.label'     => 'bail|required|string|min:3|max:191',
			'bibleverses.*.relevance' => 'bail|nullable|numeric|between:0,300',
		];

		$validator = Validator::make($request->all(), $rules);

		$validator->after(function (\Illuminate\Validation\Validator $validator) {

			$data = $validator->getData();

			$resObjs = [];

			foreach ($data['resources'] as $key => $resource) {

				$foreignResource = ForeignResourceId::where('foreign_id', $resource['id'])
					->where('user_id', Auth::id())
					->with('resource')
					->first();

				if ($foreignResource === NULL) {
					$validator->errors()->add("resources.{$key}", 'This user does not own the foreignResource');
				} else {
					$resObjs[$key] = $foreignResource;
				}
			}

			$data['foreignResources'] = $resObjs;
			$validator->setData($data);

		});

		// Auto redirect on error ...
		$validator->validate();


		// Alles fertig geprüft. Lasst Taten folgen

		/** @var MaterialExtractionService $materialService */
		$materialService                       = resolve(MaterialExtractionService::class);
		$data                                  = $validator->getData();
		$tagExtractionProperties               = [];
		$tagExtractionProperties['properties'] = [];

		if (isset($data['title']) && $data['title'] !== NULL) {
			$tagExtractionProperties['properties'][] = new TitleProperty($data['title'],
				RelevanceInterface::RELEVANCE_USER_MAX);
		}

		if (isset($data['description']) && $data['description'] !== NULL) {
			$tagExtractionProperties['properties'][] = new OcrTextProperty($data['description'],
				RelevanceInterface::RELEVANCE_USER_MAX);
		}

		if (isset($data['rating']) && $data['rating'] !== NULL) {
			$tagExtractionProperties['properties'][] = new RatingProperty($data['rating'],
				RelevanceInterface::RELEVANCE_USER_MAX);
		}

		if (isset($data['author']) && $data['author'] !== NULL) {
			$tagExtractionProperties['properties'][] = new AuthorProperty($data['author'],
				RelevanceInterface::RELEVANCE_USER_MAX);
		}

		if (isset($data['keywords']) && is_array($data['keywords'])) {
			foreach ($data['keywords'] as $kw) {
				$tagExtractionProperties['properties'][] = new KeywordProperty($kw['title'], Keyword::class,
					$kw['relevance']);
			}

		}

		$resources = [];
		foreach ($data['foreignResources'] as $fi) {
			$resources[] = $fi->resource;
		}

		$material = $materialService->createGuessedMaterialFromResource($resources, $tagExtractionProperties);
		if (isset($data['is_public'])) {
			$material->is_public = (bool)$data['is_public'];
			$material->save();
		}

		if (isset($data['from_bot'])) {
			// Todo: Does this work or is there parsing needed?
			$material->from_bot = $data['from_bot'];
			$material->save();
		}

		$foreignMaterialIdObj = ForeignMaterialId::create([
			'foreign_id'  => $foreignMaterialId,
			'user_id'     => Auth::id(),
			'material_id' => $material->id
		]);

		return $this->turnForeignMaterialIdIntoCustomFormat($foreignMaterialIdObj);

	}


	/**
	 * @param FullMaterialRequest $request
	 * @param                     $foreignMaterialId
	 * @return array|ResponseFactory|\Symfony\Component\HttpFoundation\Response
	 * @throws InvalidKeywordTypeException
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
		if ($request->has('is_public')) {
			$mat->is_public = $request->boolean('is_public');
		}

		if ($request->has('author')) {
			$this->fillAuthor($request->get('author'), $mat);
		}

		// necessary to assign keywords and bibleverses
		$mat->save();

		// Foreign-Material-ID gleich mitabspeichern
		$fm->material()->associate($mat)->save();

		$this->syncKeywords($request, $mat);
		$this->syncBibleverses($request, $mat);

		event(new MaterialWasCreated($mat));

		return $this->turnForeignMaterialIdIntoCustomFormat($fm);
	}

	/**
	 * Update the specified material in storage.
	 *
	 * @param FullMaterialRequest $request
	 * @param ForeignMaterialId $foreignMaterialId
	 * @return Response
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

		if ($request->has('is_public')) {
			Gate::authorize('updateMetadata', $material);
		}

		// Update data
		$attributes = $request->all();
		if ($material->userRankings()->exists()) {
			unset($attributes['rating']);
		}
		$material->fill(
			array_merge(
				$attributes,
				$override = [
					'modified_by' => Auth::id()
				]
			)
		);
		if ($request->has('is_public')) {
			$material->is_public = $request->boolean('is_public');
		}


		if ($request->has('author')) {
			$this->fillAuthor($request->get('author'), $material);
		}

		$material->save();

		$this->syncKeywords($request, $material);
		$this->syncBibleverses($request, $material);

		event(new MaterialWasChanged($material));

		return $this->turnForeignMaterialIdIntoCustomFormat($foreignMaterialId);

	}

	/**
	 * Remove the specified material from storage.
	 *
	 * @param ForeignMaterialId $foreignMaterialId
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
			$this->materialHandlingService->deleteMaterialAndDetachAssociations($material);
		}

		$foreignMaterialId->delete();

		return ['success' => TRUE];

	}

}
