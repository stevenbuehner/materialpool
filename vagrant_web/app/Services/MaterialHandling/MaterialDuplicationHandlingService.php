<?php

namespace App\Services\MaterialHandling;

use App\Models\Bibleverse;
use App\Models\ForeignMaterialId;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class MaterialDuplicationHandlingService {

	public static function flatToIds(Model $m) {
		return [$m->id];
	}

	/**
	 * @param \App\Models\Resource $resource
	 */
	public function mergeMaterialDublicates(Resource $resource) {

		$resource->load(['materials', 'materials.keywords', 'materials.bibleverses', 'materials.foreignIds']);

		/** @var Collection $materials */
		$materials = $resource->materials;

		if ($materials->count() > 1) {

			$materialsToCheck = $materials->merge([]);

			/** @var Material $m1 */
			while ($m1 = $materialsToCheck->pop()) {

				$subMaterialsToCheck = $materialsToCheck->merge([]);

				/**
				 * @var Material $m2
				 */
				while ($m2 = $subMaterialsToCheck->pop()) {


					// Wenn beide ohne Bot erstellt sind, dann ignorieren
					// Bzw. wenn eins von beiden mit Bot ist, dann mergen
					if ($m1->from_bot === FALSE && $m2->from_bot === FALSE) {
						continue;
					}

					if ($this->doMaterialsHaveTheSameResources($m1, $m2)) {

						// Merge in das Material, das vom User bearbeitet wurde, wenn verfügbar
						if ($m1->from_bot) {
							$main   = $m2;
							$second = $m1;
						} else {
							$main   = $m1;
							$second = $m2;
						}


						DB::beginTransaction();

						try {

							$this->mergeMaterials($main, $second);
							DB::commit();

						} catch (\Exception $e) {

							DB::rollBack();
							throw $e;

						}

					}

				}

			}

		}

	}

	protected function doMaterialsHaveTheSameResources(Material $m1, Material $m2) {

		/** @var Collection $r1 */
		$r1 = $m1->resources;
		$r2 = $m2->resources;

		if ($this->areCollectionIdsEual($r1, $r2) === FALSE) {
			return FALSE;
		}

		// Materials have exactly the same resources
		return TRUE;
	}

	protected function areCollectionIdsEual(Collection $col1, Collection $col2) {

		// Compare resource-count
		if ($col1->count() !== $col2->count()) {
			return FALSE;
		}

		// Compare resource ids
		$r1Ids = $col1->flatMap(function (Resource $r) {
			/** @var \App\Models\Resource $r */
			return [$r->id];
		});
		$r2Ids = $col2->flatMap(function (Resource $r) {
			/** @var \App\Models\Resource $r */
			return [$r->id];
		});

		return ($r1Ids->diff($r2Ids)->count() === 0);
	}

	protected function mergeMaterials(Material $main, Material $second) {

		if ($main->author_id === NULL && $second->author_id !== NULL) {
			$main->author_id = $second->author_id;
		}


		if ($main->rating === NULL && $second->rating !== NULL) {
			$main->rating = $second->rating;
		}

		if ($main->isDirty()) {
			$main->saveOrFail();
		}

		$keywordIds = [];
		$second->keywords->each(function (Keyword $k) use (&$keywordIds) {
			$keywordIds[$k->id] = $k->pivot->relevance;
		});
		$main->keywords()->syncWithoutDetaching($keywordIds);


		$bibleverseIds = [];
		$second->bibleverses->each(function (Bibleverse $b) use (&$bibleverseIds) {
			$bibleverseIds[$b->id] = $b->pivot->relevance;
		});
		$main->bibleverses()->syncWithoutDetaching($bibleverseIds);


		$foreignIds = $second->foreignIds;
		foreach ($foreignIds as $fid) {
			/** @var ForeignMaterialId $fid */
			$fid->material_id = $main->id;
			$fid->saveOrFail();
		}

		$second->delete();


	}


}