<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\MaterialHandling;


use App\Jobs\CheckLonelyBibleverse;
use App\Jobs\CheckLonelyKeyword;
use App\Models\Material;

class MaterialHandlingService {


	/**
	 * @param \App\Models\Resource $resource
	 */
	public function deleteMaterialAndAssociations(Material $material) {

		$keywords    = $material->keywords;
		$bibleverses = $material->bibleverses;

		$material->delete();

		foreach ($keywords as $kw) {
			CheckLonelyKeyword::dispatch($kw);
		}

		foreach ($bibleverses as $bv) {
			CheckLonelyBibleverse::dispatch($bv);
		}

	}

	/**
	 * @param Material $material
	 * @return Material
	 */
	public function copyMaterial(Material $material) {

		/** @var Material $clone */
		$clone = $material->replicate();
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
				$extra_attributes                     = array_except($item->pivot->getAttributes(),
																	 [$item->pivot->getForeignKey(), $item->pivot->getRelatedKey()]);
				$attachKeys[$item->getKey()] = $extra_attributes;
			}

			$clone->{$relationName}()->sync($attachKeys);

		}


		return $clone->fresh($relationsToSync);

	}


}