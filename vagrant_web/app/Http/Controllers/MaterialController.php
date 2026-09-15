<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialRequest;
use App\Models\Bibleverse;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\User;
use App\ResourceLimitations\ResourceLimitationService;
use App\Services\MaterialHandling\MaterialHandlingService;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\TagExtraction\Properties\Property;
use App\Services\TagExtraction\TagExtractionService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller {

	public function __construct() {
		$this->middleware(['auth']);
		$this->middleware('can:create,App\Models\Material')->only(['create', 'store']);
		$this->middleware('can:view,material')->only(['show']);
		$this->middleware('can:updateMetadata,material')->only(['edit', 'update']);
		$this->middleware('can:delete,material')->only(['delete', 'destroy']);
	}

	public static function withAttributes() {
		return [
			'keywords'    => function ($q) {
				$q->orderBy('keyword_material.relevance', 'desc');
			},
			'bibleverses' => function ($q) {
				$q->orderBy('bibleverse_material.relevance', 'desc');
			},
			'foreignIds',
			'resources',
			'creator',
			'usages.usedBy',
			'author'
		];
	}

	/**
	 * Lädt Materialdetails, ohne Ressourcen sichtbar zu machen, für die der
	 * aktuelle Benutzer kein Leserecht hat. Antwortpfade verwenden diese
	 * Variante anstelle von withAttributes().
	 */
	public static function withVisibleAttributes(User $user): array {
		$relations = array_filter(self::withAttributes(), static fn($relation): bool => $relation !== 'resources');
		$relations['resources'] = static fn($query) => $query->visibleTo($user);

		return $relations;
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		$materials = Material::query()
			->visibleTo(Auth::user())
			->with(self::withVisibleAttributes(Auth::user()))
			->orderBy('updated_at')
			->paginate(50);
		$title     = "Alle Materialien";

		return view('materials.listing', compact('materials', 'title'));
	}

	public function indexBySingleKeyword($lcKeyword) {

		$kw        = Keyword::where(['lc_title' => $lcKeyword])->first();
		$materials = $kw->materials()
			->visibleTo(Auth::user())
			->with(self::withVisibleAttributes(Auth::user()))
			->orderBy('pivot_relevance', 'desc')
			->paginate(50);

		$title = "Suche nach " . $kw->title . "'";

		return view('materials.listing', compact('materials', 'title'));
	}

	public function indexByBibleverse(int $from, int $to) {

		$matQuery = Material::query()
			->select(['materials.*', DB::raw('max(bibleverse_material.relevance) as relevance')])
			->distinct()
			->visibleTo(Auth::user())
			->with(self::withVisibleAttributes(Auth::user()))
			->orderBy('relevance', 'desc')
			->groupBy('materials.id')
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
			$title      = "Suche nach " . $bibleVerse->label;
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
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(MaterialRequest $request) {

		$material              = new Material($request->only(['title', 'rating', 'description', 'from_bot']));
		$material->created_by  = Auth::id();
		$material->modified_by = Auth::id();
		if ($request->has('is_public')) {
			$material->is_public = $request->boolean('is_public');
		}
		$material->save();

		// Extract meta-data from string and assign it to material
		/** @var TagExtractionService $tagExctractionService */
		if ($request->get('meta', FALSE)) {
			$tagExctractionService = resolve(TagExtractionService::class);
			$metaData              = $request->get('meta');
			$properties            = $tagExctractionService->extractPartsFromStrings($metaData, 1);

			$properties->each(function (Property $property) use ($material) {
				$property->insertYourselfToItem($material);
			});
		}

		$resources = [];

		// prepare resource assignment
		$resourceIds = $request->get('resources', FALSE);
		if ($resourceIds !== FALSE && is_array($resourceIds) && count($resourceIds) > 0) {

			foreach ($resourceIds as $id) {
				// Todo Check Authors Resource-Priviledges
				$resources[$id] = [];
			}
		}

		// prepare resource limitation
		$resourceLimits = $request->get('limit', FALSE);
		if ($resourceLimits !== FALSE && is_array($resourceLimits) && count($resourceLimits) > 0) {

			/** @var ResourceLimitationService $limitationService */
			$limitationService = resolve(ResourceLimitationService::class);

			foreach ($resourceLimits as $id => $data) {
				// Todo Check Authors Resource-Priviledges

				if (!isset($resources[$id])) {
					$resources[$id] = [];
				}

				try {
					$limitation                   = $limitationService->createLimitation($data);
					$resources[$id]['limitation'] = serialize($limitation);
				} catch (\Exception $e) {

				}
			}
		}


		// Assign resources with pivot data to material
		$material->resources()->attach($resources);

		return response()->redirectToRoute('pool.material.edit', [$material->id]);

	}

// TODO: protected function assignResourcesToMaterial(){}

	/**
	 * Display the specified resource.
	 *
	 * @param Material $material
	 * @return \Illuminate\Http\Response
	 */
	public function show(Material $material) {
		$material->load(self::withVisibleAttributes(Auth::user()));

		// Zeige andere Materialien, die ebenfalls mit diesen Ressourcen verknüpft sind
		$resourceIds       = $material->resources->pluck('id');
		$andereMaterialien = Material::query()
			->visibleTo(Auth::user())
			->whereKeyNot($material->id)
			->whereHas('resources', fn(Builder $query) => $query->whereIn('resources.id', $resourceIds))
			->select('materials.id')
			->distinct()
			->get();


		return view('materials.show', ['material' => $material, 'andereMaterialien' => $andereMaterialien]);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param Material $material
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Material $material) {
		$material->load(self::withVisibleAttributes(Auth::user()));

		return view('materials.edit', ['material' => $material]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param Material $material
	 * @return \Illuminate\Http\Response
	 */
	public function update(MaterialRequest $request, Material $material) {

		$material->fill($request->all());
		if ($request->has('is_public')) {
			$material->is_public = $request->boolean('is_public');
		}
		$material->save();

		return redirect(route('pool.material.show', $material));
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param Material $material
	 * @return \Illuminate\Http\Response
	 */
	public function delete(Material $material) {

		return view('materials.delete', [
			'material' => $material->load('resources')
		]);

	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param Material $material
	 * @param           $request
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Material $material, Request $request) {

		$delResources     = (bool)$request->get('deleteResources', FALSE);
		$deletedResources = 0;
		$ignoredResources = 0;
		$deletedMaterials = 0;

		if ($delResources === TRUE) {
			foreach ($material->resources as $resource) {
				$this->authorize('delete', $resource);
			}

			/** @var FileHandlingService $service */
			$service = resolve(FileHandlingService::class);

			/** @var \App\Models\Resource $resource */
			foreach ($material->resources as $resource) {

				if ($resource->materials->count() > 1) {
					$material->resources()->detach($resource->id);
					$ignoredResources++;
				} else {
					$service->deleteResourceCompletely($resource);
					$deletedResources++;
				}

			}
		}


		$materialService = resolve(MaterialHandlingService::class);
		$materialService->deleteMaterialAndDetachAssociations($material);
		$deletedMaterials++;

		return view('materials.destroyConfirm',
			compact('deletedResources', 'ignoredResources', 'deletedMaterials')
		);

	}
}
