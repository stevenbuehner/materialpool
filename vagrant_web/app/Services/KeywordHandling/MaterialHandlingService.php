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


}