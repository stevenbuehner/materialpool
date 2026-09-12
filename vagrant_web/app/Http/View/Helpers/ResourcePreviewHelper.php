<?php

namespace App\Http\View\Helpers;

use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PreviewGeneration\ResourcePreviewService;

class ResourcePreviewHelper {

	protected $previewService;

	public function __construct(ResourcePreviewService $previewService) {
		$this->previewService = $previewService;
	}

	public function imagePossible(ResourceEntity $resource, $size = 'large') {
		return $this->previewService->imagePreviewAble($resource, $size);
	}

	public function htmlPossible(ResourceEntity $resource, $size = 'large') {
		return $this->previewService->htmlPreviewAble($resource, $size);
	}

	public function image(ResourceEntity $resource, $width = NULL, $height = NULL) {
		return $this->previewService->getImagePreviewByWidthAndHeight($resource, $width, $height);
	}

	public function html(ResourceEntity $resource, ?ResourceLimitationInterface $limitation = NULL, $context = NULL, $size = 'large') {
		return $this->previewService->renderHTMLPreview($resource, $limitation, $context, $size);
	}

}
