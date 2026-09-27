<?php

namespace App\Http\View\Helpers;

use App\Models\Material;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;

class MaterialPreviewHelper {

	public function materialToArray(Material $mat, $with = []) {

		$matArr = $mat->toArray();

		foreach ($with as $option) {
			switch ($option) {
				case 'previewable':
					$this->addResourcePreviewOptions($mat, $matArr);
					break;
				default:

			}
		}

		return $matArr;

	}


	protected function addResourcePreviewOptions(Material $material, &$matArr) {

		if ($material->relationLoaded('resources') && isset($matArr['resources'])) {

			$resources = [];

			foreach ($material->resources as $resource) {

				$data = $resource->toArray();

				/** @var PreviewGeneratorInterface $prevGen */
				$prevGen             = $resource->getPreviewGenerator();
				$data['previewable'] = [
					'image' => $prevGen->imagePreviewAble($resource),
					'html'  => $prevGen->htmlPreviewAble($resource)
				];

				$resources[] = $data;
			}

			$matArr['resources'] = $resources;

		}


	}

}