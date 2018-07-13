<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\KeywordRequest;
use App\Models\Keyword;
use App\Models\Material;
use App\Services\KeywordHandling\KeywordHandlingService;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use App\Services\TagExtraction\Properties\KeywordProperty;
use App\Services\TagExtraction\Properties\Property;
use App\Services\TagExtraction\TagExtractionService;
use Illuminate\Http\Request;


class KeywordController extends BaseController {
	/**
	 * Display a listing of the resource.
	 *
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


		return $query->paginate(20);
	}

	public function show(Keyword $keyword) {
		return $keyword;
	}

	public function create(KeywordRequest $keywordRequest) {
		/** @var TagExtractionService $tagExtractionService */
		/** @var Keyword $keyword */
		$type    = $keywordRequest->get('type', FALSE);
		$keyword = NULL;


		if ($type !== FALSE) {
			// Request does use type attribute -> take type for granted

			if (in_array($type, array_keys(Keyword::getSingleTableTypeMap()))) {
				$class = Keyword::getSingleTableTypeMap()[$type];
				// $keyword = new $class($keywordRequest->all());
				$keyword = $class::firstOrCreate($keywordRequest->all());
			} else {
				$type = FALSE;
			}
		}

		if ($type === FALSE) {
			// Request does not contain type attribute -> use tagExtractionService
			$tagExtractionService = resolve(TagExtractionService::class);
			$tagProperties        = $tagExtractionService->recognizeTagsFromSingleString($keywordRequest->get('title',
																											  ''));
			$tagProperties        = $tagProperties->filter(function (Property $property) {
				return $property instanceof KeywordProperty;
			});

			if ($tagProperties->count() > 0) {
				/** @var Keyword $keyword */
				$keyword = $tagProperties->first()->getKeywordValue();
				$keyword->save();
			}
		}

		// Reload from DB to assign parent_id, icon etc. to the model
		$keyword = $keyword->fresh();

		return $keyword;
	}

	public function update(KeywordRequest $keywordRequest, Keyword $keyword) {

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

		if ($keyword->isDirty()) {
			$keyword->save();
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
													 RelevanceInterface::RELEVANCE_USER_MAX)
					]
			],
			$doNotDetachOtherRelationships = FALSE);

		return $material->keywords()->where('keywords.id', '=', $keyword->id)->get()->first();
	}

	public function deleteAssignment(Material $material, Keyword $keyword) {
		return $material->keywords()->detach($keyword);
	}


}
