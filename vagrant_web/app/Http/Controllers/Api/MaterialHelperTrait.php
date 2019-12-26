<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\KeywordRequest;
use App\Jobs\CheckLonelyBibleverse;
use App\Jobs\CheckLonelyKeyword;
use App\Models\Bibleverse;
use App\Models\Exceptions\InvalidKeywordTypeException;
use App\Models\Keyword;
use App\Models\Material;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait MaterialHelperTrait {

	protected $bibleVerseService;

	/**
	 * @param null|string|array $author
	 * @param Material $material
	 * @return Keyword|null
	 * @throws InvalidKeywordTypeException
	 */
	protected function fillAuthor($author, Material $material) {

		if (is_array($author) && isset($author['id'])) {
			$author = Keyword::find($author['id']);

			if ($author->type !== 'person') {
				throw new InvalidKeywordTypeException('Expected Keyword with type person here');
			}

		} else if (!empty($author)) {
			$author = Keyword::firstOrCreatePerson($author);

		} else {
			$author = NULL;
		}

		if ($author === NULL) {
			$material->author()->dissociate();
		} else {
			$material->author()->associate($author);
		}


		return $author;
	}

	/**
	 * As 'keywords' => ['type', 'title', 'relevance']
	 *
	 * @param Request $request
	 * @param Material $material
	 * @return int
	 */
	protected function syncKeywords(Request $request, Material $material) {
		$keywordIds = [];

		if ($request->has('keywords')) {

			$keywordRequestRules = (new KeywordRequest())->rules();

			if (is_array($request->get('keywords'))) {

				foreach ($request->get('keywords') as $keyword) {
					$validator = Validator::make($keyword, $keywordRequestRules);

					if ($validator->valid()) {
						$data  = $validator->getData();
						$type  = isset($data['type']) ? $data['type'] : 'key';
						$title = isset($data['title']) ? $data['title'] : NULL;

						try {
							/** @var Keyword $kw */
							$kw                  = Keyword::firstOrCreate(['title' => $title, 'type' => $type]);
							$relevance           = isset($keyword['relevance']) ? $keyword['relevance'] : RelevanceInterface::RELEVANCE_EXIF_MAX;
							$keywordIds[$kw->id] = ['relevance' => $relevance];
						} catch (InvalidKeywordTypeException $e) {
						}

					}
				}
			}

			// Check which keywords have been deleted
			// $oldIds     = $material->keywords->pluck('id')->toArray();
			// $newIds     = array_keys($keywordIds);
			// $deletedIds = array_diff($oldIds, $newIds);


			// Sync keywords
			$result = $material->keywords()->sync($keywordIds);


			foreach ($result['detached'] as $delId) {
				CheckLonelyKeyword::dispatch(Keyword::find($delId));
			}
		}

		return count($keywordIds);

	}

	protected function syncBibleverses(Request $request, Material $material) {
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

			// Check which bibleverses have been deleted
			// $oldIds     = $material->bibleverses->pluck('id')->toArray();
			// $newIds     = array_keys($bibleverseIds);
			// $deletedIds = array_diff($oldIds, $newIds);


			// Sync bibleverses
			$result = $material->bibleverses()->sync($bibleverseIds);


			foreach ($result['detached'] as $delId) {
				CheckLonelyBibleverse::dispatch(Bibleverse::find($delId));
			}
		}

		return count($bibleverseIds);
	}


}
