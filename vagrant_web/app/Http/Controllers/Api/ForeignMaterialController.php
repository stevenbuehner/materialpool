<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\MaterialRequest;
use App\Models\ForeignMaterialId;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;


class ForeignMaterialController extends BaseController {

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
			'material.resources'
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

		$foreignMaterialId->load($this->withAttributes);

		return $this->turnForeignMaterialIdIntoCustomFormat($foreignMaterialId);
	}

	/**
	 * @param ForeignMaterialId $foreignMaterialId
	 * @return array
	 */
	public function turnForeignMaterialIdIntoCustomFormat(ForeignMaterialId $foreignMaterialId) {
		$result       = $foreignMaterialId->material->toArray();
		$result['id'] = $foreignMaterialId->foreign_id;

		return $result;
	}


	/**
	 * Store a newly created material in storage.
	 *
	 */
	public function store(Request $request) {

	}


	/**
	 * Update the specified material in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  Material                 $material
	 * @return \Illuminate\Http\Response
	 */
	public function update(MaterialRequest $request, Material $material) {
	}


	/**
	 * Remove the specified material from storage.
	 *
	 * @param  Material $material
	 */
	public function destroy(Material $material) {


	}
}
