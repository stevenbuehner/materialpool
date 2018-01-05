<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Services\PreviewGeneration\ResourcePreviewService;
use Intervention\Image\Size;

class ResourcePreviewController extends Controller {

	protected $previewService;

	public function __construct(ResourcePreviewService $previewService) {
		$this->previewService = $previewService;
	}

	public function getImage(Resource $resource, $width = NULL, $height = NULL) {

		return $this->previewService->getImagePreviewByWidthAndHeight($resource, $width, $height);
	}
}
