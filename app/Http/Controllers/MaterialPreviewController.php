<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\MaterialPreviewService;
use App\Services\PreviewGeneration\PreviewSize;
use Illuminate\Http\Request;

class MaterialPreviewController {

	protected MaterialPreviewService $materialPreviewService;

	public function __construct(MaterialPreviewService $materialPreviewService) {
		$this->materialPreviewService = $materialPreviewService;
	}

	public function getMaterialPreview(Request $request, Material $material) {
		$size = PreviewSize::constrained(
			$request->has('width') ? $request->integer('width') : NULL,
			$request->has('height') ? $request->integer('height') : NULL
		);

		try {
			$imageData = $this->materialPreviewService->getCachedMaterialPreviewData(
				$material,
				$size
			);
			$format = PreviewSize::profile($size)['outputFormat'];
			$response  = response($imageData, 200, ['Content-Type' => 'image/' . ($format === 'jpg' ? 'jpeg' : $format)]);
			$response->setEtag(hash('sha256', $imageData));
			$response->isNotModified($request);

			return $response;

		} catch (NotPreviewAbleException $e) {
		}

		return response()->json(['success' => FALSE], 204); // 204 = Success but no content

	}


}
