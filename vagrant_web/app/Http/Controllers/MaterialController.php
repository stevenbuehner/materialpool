<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialRequest;
use App\Models\Bibleverse;
use App\Models\Keyword;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller {

	protected $withAttributes = [];

	public function __construct() {
		$this->withAttributes = [
			'keywords'    => function ($q) {
				$q->orderBy('keyword_material.relevance', 'desc');
			},
			'bibleverses' => function ($q) {
				$q->orderBy('bibleverse_material.relevance', 'desc');
			},
			'resources',
			'creator'];

		$this->middleware(['auth']);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		$materials = Material::with($this->withAttributes)->orderBy('updated_at')->paginate(50);
		$title     = "Alle Materialien";

		return view('materials.listing', compact('materials', 'title'));
	}

	public function indexBySingleKeyword($lcKeyword) {

		$kw        = Keyword::where(['lc_title' => $lcKeyword])->first();
		$materials = $kw->materials()
						->with($this->withAttributes)
						->orderBy('pivot_relevance', 'desc')
						->paginate(50);

		$title = "Suche nach " . $kw->title . "'";

		return view('materials.listing', compact('materials', 'title'));
	}

	public function indexByBibleverse(int $from, int $to) {

		$matQuery = Material::query()
							->select('materials.*')
							->distinct()
							->with($this->withAttributes)
							->orderBy('bibleverse_material.relevance', 'asc')
							->where(function ($q) use ($from, $to) {
								$q->orWhereBetween("bibleverses.from", [$from, $to]);
								$q->orWhereBetween("bibleverses.to", [$from, $to]);
								$q->orWhere(function ($q) use ($from, $to) {
									$q->where("bibleverses.from", '>', $from);
									$q->where("bibleverses.to", '<', $to);
								});
							})
							->leftJoin("bibleverse_material as bibleverse_material", 'materials.id', '=',
									   "bibleverse_material.material_id")
							->leftJoin("bibleverses as bibleverses",
									   "bibleverse_material.bibleverse_id", '=',
									   "bibleverses.id");


		try {
			$bibleVerse = new Bibleverse(['from' => $from, 'to' => $to]);
			$title      = "Suche nach " . $bibleVerse->label ;
		} catch (\Exception $e) {
			$title = "Ungültiger Bibelvers";
		}

		return view('materials.listing', ['materials' => $matQuery->paginate(50), 'title' => $title]);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  Material $material
	 * @return \Illuminate\Http\Response
	 */
	public function show(Material $material) {
		$material->load($this->withAttributes);

		return view('materials.show', ['material' => $material]);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  Material $material
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Material $material) {
		$material->load($this->withAttributes);

		return view('materials.edit', ['material' => $material]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  Material                 $material
	 * @return \Illuminate\Http\Response
	 */
	public function update(MaterialRequest $request, Material $material) {

		$material->fill($request->all());
		$material->save();

		return redirect(route('pool.material.show', $material));
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  Material $material
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Material $material) {
		//
	}
}
