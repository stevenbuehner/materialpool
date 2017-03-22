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
		$materials = $foreignInstance->materials()->with(['keywords', 'resources.foreignResourceKeys'])->orderBy('id')
									 ->paginate(20);

		$subset = $materials->map(function (Material $mat) use ($foreignInstance) {
			return $this->mapMaterialWithKeywordsAndForeignResourceKeys($mat, $foreignInstance);
		});

		$materials->setCollection($subset);

		return $materials;
	}

	protected function mapMaterialWithKeywordsAndForeignResourceKeys(Material $mat, ForeignInstance $foreignInstance) {

		// Preload missing relations
		$afterLoading = [];
		if (!isset($mat->relation['keywords'])) {
			$afterLoading[] = 'keywords';
		}
		if (!isset($mat->relation['resources'])) {
			$afterLoading[] = 'resources.foreignResourceKeys';
		}
		if (count($afterLoading) > 0) {
			$mat->load($afterLoading);
		}


		$matData = $mat->toArray();

		$keywords = [];
		foreach ($matData['keywords'] as $k) {
			$keywords[] = [
				'id'                 => $k['id'],
				'title'              => $k['title'],
				'type'               => $k['type'],
				'mat_keyword_rating' => $k['pivot']['relevance']
			];
		}
		$matData['keywords'] = $keywords;

		$resources = [];
		foreach ($matData['resources'] as $r) {
			$tmpResource = [
				'id'          => $r['id'],
				'remote_path' => $r['remote_path'],
				'notes'       => $r['notes'],
				'is_public'   => $r['is_public'],
				'type'        => $r['type'],
				'created_at'  => $r['created_at'],
				'updated_at'  => $r['updated_at'],
			];

			// Will have at least one foreignKey
			foreach ($r['foreign_resource_keys'] as $frk) {
				if ($frk['foreign_instance_id'] == $foreignInstance->id) {
					$tmpResource['remote_id'] = $frk['remote_id'];
					break;
				}
			}

			$resources[] = $tmpResource;
		}
		$matData['resources'] = $resources;

		return $matData;
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


		return $this->mapMaterialWithKeywordsAndForeignResourceKeys($mat, $foreignInstance);

	}

}
