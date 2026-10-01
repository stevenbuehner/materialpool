<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\MaterialHandling;


use App\Events\MaterialWasCreated;
use App\Events\MaterialWasDeleted;
use App\Events\ResourceWasDetached;
use App\Http\Controllers\MaterialController;
use App\Jobs\CheckLonelyBibleverse;
use App\Jobs\CheckLonelyKeyword;
use App\Jobs\CheckLonelyResource;
use App\Models\Keyword;
use App\Models\Material;
use Exception;
use Illuminate\Support\Arr;

class MaterialHandlingService {

	/**
	 * @param Material $material
	 * @throws Exception
	 */
	public function deleteMaterialAndDetachAssociations(Material $material) {

		// Important for MaterialWasDeleted event (!)
		$material->load(['resources', 'bibleverses', 'keywords', 'foreignIds']);

		$this->detachAllResources($material);
		$this->detachAllBibleverses($material);
		$this->detachAllKeywords($material);
		$this->detachAllForeignIds($material);
		$this->detachAuthor($material);

		$material->delete();
		event(new MaterialWasDeleted($material));

	}

	public function detachAllResources(Material $material) {

		$material->resources;
		$material->resources()->detach();

		foreach ($material->resources as $resource) {
			CheckLonelyResource::dispatch($resource);
			event(new ResourceWasDetached($material, $resource));
		}

	}

	public function detachAllBibleverses(Material $material) {

		$material->bibleverses;
		$material->bibleverses()->detach();

		foreach ($material->bibleverses as $bv) {
			CheckLonelyBibleverse::dispatch($bv);
		}

	}

	public function detachAllKeywords(Material $material) {

		$material->keywords;
		$material->keywords()->detach();

		foreach ($material->keywords as $kw) {
			CheckLonelyKeyword::dispatch($kw);
		}

	}

	public function detachAllForeignIds(Material $material) {

		$material->foreignIds;
		$material->foreignIds()->delete();

	}

	public function detachAuthor(Material $material) {

		$author = $material->author;

		if ($author instanceof Keyword) {
			$material->author()->dissociate();    // Keep this information for MaterialWasDeleted-Event
			CheckLonelyKeyword::dispatch($author);
		}

	}

	/**
	 * @param Material $material
	 * @return Material
	 */
	public function copyMaterial(Material $material) {

		/** @var Material $clone */
		$clone             = $material->replicate();
		$clone->created_at = $material->created_at;
		$clone->save();
		// $clone->setRelations([]);

		$relationsToSync = [
			'keywords',
			'bibleverses',
			'resources'
		];

		$material->load($relationsToSync);

		// Once the model has been saved with a new ID, we can get its children
		foreach ($relationsToSync as $relationName) {
			$attachKeys = [];

			foreach ($material->getRelation($relationName) as $item) {
				// Now we get the extra attributes from the pivot tables, but
				// we intentionally leave out the foreignKey, as we already
				// have it in the newModel
				$extra_attributes            = Arr::except($item->pivot->getAttributes(),
					[$item->pivot->getForeignKey(), $item->pivot->getRelatedKey()]);
				$attachKeys[$item->getKey()] = $extra_attributes;
			}

			$clone->{$relationName}()->sync($attachKeys);

		}

		event(new MaterialWasCreated($clone));

		return $clone->fresh(MaterialController::withAttributes());

	}

}
