<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\MaterialHandling;


use App\Events\ResourceWasAttached;
use App\Models\Material;
use App\Models\PdfFile;
use App\ResourceLimitations\PageLimitation;
use App\Services\ResourceHandling\Exceptions\MissingRelationException;

class PdfMaterialHandlingService {

	public function addPageToLimitation(PdfFile $resource, Material $material, $pageNo) {

		/** @var Material $mWithPivot */
		$mWithPivot = $resource->materials()->where('id', $material->id)->first();

		if (!is_array($pageNo)) {
			$pageNo = [$pageNo];
		}

		if (empty($mWithPivot)) {
			throw new MissingRelationException();
		}


		$limitation = $mWithPivot->pivot->limitation;

		if (!$limitation instanceof PageLimitation) {
			$limitation = new PageLimitation();
		}

		foreach ($pageNo as $page) {
			$limitation->addPage($page);
		}

		$resource->materials()->updateExistingPivot($material->id, ['limitation' => $limitation]);
		event(new ResourceWasAttached($material, new PdfFile(['id' => $resource->id])));


		return $limitation;

	}


	public function removePageFromLimitation(PdfFile $resource, Material $material, $pageNo) {

		/** @var Material $mWithPivot */
		$mWithPivot = $resource->materials()->where('id', $material->id)->first();

		if (!is_array($pageNo)) {
			$pageNo = [$pageNo];
		}

		if (empty($mWithPivot)) {
			throw new MissingRelationException();
		}


		$limitation = $mWithPivot->pivot->limitation;

		if ($limitation instanceof PageLimitation) {

			foreach ($pageNo as $page) {
				$limitation->removePage($page);
			}

			$resource->materials()->updateExistingPivot($material->id, ['limitation' => $limitation]);
			event(new ResourceWasAttached($material, new PdfFile(['id' => $resource->id])));

		} else {
			// kein PageLimitation => also auch nichts zum entfernen da!
		}

		return $limitation;

	}


}