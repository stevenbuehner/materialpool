<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\MaterialPreviewService;

class MaterialPreviewController {

	protected MaterialPreviewService $materialPreviewService;

	public functioN __construct(MaterialPreviewService $materialPreviewService) {
		$this->materialPreviewService = $materialPreviewService;
	}

	public function getMaterialPreview(Material $material) {

		try {
			$image = $this->materialPreviewService->getCachedMaterialPreview($material);

			return $image->response(config('app.preview.outputFormat'));

		} catch (NotPreviewAbleException $e) {
		}

		return response()->json(['success' => FALSE], 204); // 204 = Success but no content

	}


}
