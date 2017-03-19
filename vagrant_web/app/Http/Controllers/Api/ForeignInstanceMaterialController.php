<?php

namespace App\Http\Controllers\Api;

use App\Models\ForeignInstance;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;


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
		$matData = $mat->toArray();

		$keywords = [];
		foreach ($matData['keywords'] as $k) {
			$keywords[] = [
				'id'                 => $k['id'],
				'title'              => $k['title'],
				'type'               => $k['type'],
				'mat_keyword_rating' => $k['pivot']['rating']
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
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {

	}

}
