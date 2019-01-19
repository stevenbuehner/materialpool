<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\KeywordRequest;
use App\Jobs\CheckLonelyKeyword;
use App\Models\Keyword;
use App\Models\Material;
use App\Services\KeywordHandling\KeywordHandlingService;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use App\Services\TagExtraction\TagExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class KeywordController extends BaseController {

	public function __construct() {
		$this->middleware('auth:api');
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @param Request $request
	 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
	 */
	public function index(Request $request) {

		$search_term = $request->input('q');
		$search_type = $request->input('t');


		$query = Keyword::query();

		if ($search_term) {
			$query->where('title', 'LIKE', '%' . $search_term . '%');
		}

		if ($search_type) {
			$query->where('type', '=', $search_type);
		}

		$query->orderBy('_lft');

		return $query->paginate(500);
	}

	public function show(Keyword $keyword) {
		return $keyword;
	}

	public function create(KeywordRequest $keywordRequest) {
		/** @var TagExtractionService $tagExtractionService */
		/** @var Keyword $keyword */
		$keyword = Keyword::firstOrCreate($keywordRequest->validated());
		$type    = $keywordRequest->get('type', FALSE);

		if ($type !== FALSE) {
			// Request does use type attribute -> take type for granted
			$keyword->type = $type;
		}

		// Reload from DB to assign parent_id, icon etc. to the model
		$keyword = $keyword->fresh();

		return $keyword;
	}

	public function update(Request $keywordRequest, Keyword $keyword) {

		// Update title
		if ($keywordRequest->has('title')) {
			$newTitle = trim($keywordRequest->get('title'));

			// is there already an existing keyword with this name

			$alreadyExisting = Keyword::where([
												  'type'  => $keyword->type,
												  'title' => $newTitle])
									  ->where('id', '!=', $keyword->id)->first();

			if ($alreadyExisting) {
				// Merge all other Keywords

				/** @var KeywordHandlingService $service */
				$service = resolve(KeywordHandlingService::class);
				$service->mergeKeywords($alreadyExisting, $keyword);

				$keyword = $alreadyExisting;
			} else {
				$keyword->title = $newTitle;
			}
		}


		// Update parent
		if ($keywordRequest->has('parent_id')) {
			$parentId           = $keywordRequest->get('parent_id', NULL);
			$keyword->parent_id = $parentId;
		}


		if ($keyword->isDirty()) {
			$keyword->save();
		}


		// Update type
		if ($keywordRequest->has('type')) {
			$type    = $keywordRequest->get('type');
			$handler = resolve(KeywordHandlingService::class);

			$keyword = $handler->changeKeywordType($keyword, $type);
		}


		return $keyword;

	}

	public function createOrUpdateAssignment(Material $material, Keyword $keyword, Request $request) {
		// $material->keywords()->updateExistingPivot($keyword->id, ['relevance' => $request->get('relevance')]);

		// Create or Update Relationship without detaching others
		$material->keywords()->sync(
			[
				$keyword->id =>
					[
						'relevance' => $request->get('relevance',
													 RelevanceInterface::RELEVANCE_USER_AVG)
					]
			],
			$doNotDetachOtherRelationships = FALSE);

		$material->from_bot = FALSE;
		$material->save();

		return $material->keywords()->where('keywords.id', '=', $keyword->id)->get()->first();

	}


	public function deleteAssignment(Material $material, Keyword $keyword) {

		$material->from_bot = FALSE;
		$material->save();

		$result = $material->keywords()->detach($keyword);

		CheckLonelyKeyword::dispatch($keyword);

		return $result;

	}

	public function delete(Keyword $keyword) {

		// 1) Prüfe ob das Keyword in Materialien vorkommt, worauf dieser Nutzer keine Rechte hat ==> Abbruch mit Fehlermeldung
		$user              = Auth::user();
		$materialsByOthers = $keyword->materials()->where('materials.created_by', '!=', $user->id)->count();

		if ($materialsByOthers > 0) {
			return response([
								'success' => FALSE,
								'message' => "Keyword could not be deleted. Materials of other owners still use this keyword."
							], 424); // Status-Code: Failed Dependency
		}

		// 2 Nehme das Keyword aus allen Parent-Child Funktionen heraus
		/** @var Keyword[] $allChildren */
		$allChildren = $keyword->children;
		foreach ($allChildren as $childKeyword) {
			# Implicit save
			$childKeyword->insertAfterNode($keyword);
		}

		// 3a) Lösche alle Keyword Assoziationen
		$numDetached = $keyword->materials()->detach();

		// 3b) ToDO: Lösche ggf. icons zu dem Keyword

		// 3) Lösche das Keyword selbst
		$keyword->delete();

		// 4) Success flag setzen als return-Wert
		return [
			'success'             => TRUE,
			'deletedAssociations' => $numDetached
		];

	}


}
