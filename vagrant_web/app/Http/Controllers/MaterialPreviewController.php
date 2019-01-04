<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\PreviewGeneration\MaterialPreviewService;

class MaterialPreviewController {

	protected $materialPreviewService;

	public functioN __construct(MaterialPreviewService $materialPreviewService) {
		$this->materialPreviewService = $materialPreviewService;
	}

	public function getMaterialPreview(Material $material) {

		$image = $this->materialPreviewService->getCachedMaterialPreview($material);

		return $image->response(config('app.preview.outputFormat'));

	}


}
