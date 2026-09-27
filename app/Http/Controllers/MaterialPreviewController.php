<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\MaterialPreviewService;
use App\Services\PreviewGeneration\PreviewSize;
use Illuminate\Http\Request;

class MaterialPreviewController {

	protected MaterialPreviewService $materialPreviewService;

	public functioN __construct(MaterialPreviewService $materialPreviewService) {
		$this->materialPreviewService = $materialPreviewService;
	}

	public function getMaterialPreview(Request $request, Material $material) {

		try {
			$imageData = $this->materialPreviewService->getCachedMaterialPreviewData(
				$material,
				PreviewSize::constrained(
					$request->has('width') ? $request->integer('width') : NULL,
					$request->has('height') ? $request->integer('height') : NULL
				)
			);
			$response = response($imageData, 200, ['Content-Type' => 'image/jpeg']);
			$response->setEtag(hash('sha256', $imageData));
			$response->isNotModified($request);

			return $response;

		} catch (NotPreviewAbleException $e) {
		}

		return response()->json(['success' => FALSE], 204); // 204 = Success but no content

	}


}
