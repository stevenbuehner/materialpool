<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\MaterialRequest;
use App\Models\Exceptions\InvalidKeywordTypeException;
use App\Models\ForeignInstance;
use App\Models\ForeignResourceKey;
use App\Models\Keyword;
use App\Models\Material;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;


class ForeignInstanceMaterialController extends BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 */
	public function index(ForeignInstance $foreignInstance) {
		/** @var LengthAwarePaginator $materials */
		$materials = $foreignInstance
			->materials()
			->with($this->getRelationLoadingNeccesity($foreignInstance))->orderBy('id')
			->paginate(20);


		$materials->each(function ($m) use (&$hiddenMat, &$hiddenFRK) {
			if (!$hiddenMat) {
				$hiddenMat = array_merge($m->getHidden(), [ 'created_by', 'modified_by']);
			}

			$m->setHidden($hiddenMat);


			$m->resources->each(function ($r) use (&$hiddenFRK) {
				$r->foreignResourceKeys->each(function ($frk) use (&$hiddenFRK) {
					if (!$hiddenFRK) {
						$hiddenFRK = array_merge($frk->getHidden(), ['resource_id', 'foreign_instance_id']);
					}

					$frk->setHidden($hiddenFRK);
				});
			});

		});

		return $materials;

	}

	protected function getRelationLoadingNeccesity(ForeignInstance $foreignInstance, Material $mat = NULL) {

		// Preload missing relations
		$afterLoading = [];

		if ($mat == NULL || !isset($mat->relation['keywords'])) {
			$afterLoading[] = 'keywords';
		}

		if ($mat == NULL || !isset($mat->relation['resources'])) {
			$afterLoading['resources.foreignResourceKeys'] = function ($query) use ($foreignInstance) {
				// Only show the foreign key information of this $foreignInstance
				$query->where('foreign_resource_keys.foreign_instance_id',
							  $foreignInstance->id);
			};
		}

		if ($mat == NULL || !isset($mat->relation['bibleverses'])) {
			$afterLoading[] = 'bibleverses';
		}

		// if (count($afterLoading) > 0) {
		// 	$mat->load($afterLoading);
		// }

		return $afterLoading;
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 */
	public function store(ForeignInstance $foreignInstance, int $remoteResourceId, MaterialRequest $request) {

		$frk = ForeignResourceKey::findOneWhere($foreignInstance->id, NULL, $remoteResourceId);

		if ($frk === NULL) {
			return response()->json(['error' => 'No Resource with this remote_id existant'])->setStatusCode(403);
		}

		$mat = new Material();
		$mat->fill($request->all());
		$mat->from_bot    = TRUE;
		$mat->created_by  = $foreignInstance->user_id;
		$mat->modified_by = $foreignInstance->user_id;


		try {
			DB::beginTransaction();

			$mat->save();
			$mat->resources()->attach($frk->resource_id);

			$keywords    = collect();
			$bibleverses = [];

			if ($request->has('keywords') && is_array($request->get('keywords'))) {
				foreach ($request->get('keywords', []) as $k) {
					if (!is_array($k) || !isset($k['title'])) {
						return response()->json(['error' => 'keywords with wrong format'])->setStatusCode(422);
					}

					$keywordTitle = $k['title'];
					$keywordType  = isset($k['type']) ? $k['type'] : 'key';
					$relevance    = isset($k['relevance']) ? (int) $k['relevance'] : Keyword::$defaultRelevance;

					try {
						$newKeyword = Keyword::create($keywordTitle, $keywordType);
					} catch (InvalidKeywordTypeException $e) {
						return response()->json(['error' => 'Invalid Keyword-Type'])->setStatusCode(422);
					}

					if (!$keywords->has($newKeyword->id)) {
						$keywords->put($newKeyword->id, $newKeyword);
						$mat->keywords()->attach($newKeyword->id, ['relevance' => $relevance]);
					}
				}
			}

			// Todo: Add Bibleverses to Material creation


			DB::commit();
		} catch (\Exception $e) {
			DB::rollBack();

			throw  $e;
		}

		return $mat->load($this->getRelationLoadingNeccesity($foreignInstance, $mat));

	}

}
