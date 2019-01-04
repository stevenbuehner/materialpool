<?php

namespace App\Services\PreviewGeneration;

use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Resource;
use App\ResourceLimitations\PageLimitation;
use App\ResourceLimitations\TimeLimitation;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;

class MaterialPreviewService extends AbstractPreviewService {

	protected $resourcePreviewService;

	public function __construct(ResourcePreviewService $resourcePreviewService, ImageManager $imageManager) {
		parent::__construct($imageManager);
		$this->resourcePreviewService = $resourcePreviewService;
	}

	public function hasPreview(Material $material) {

		/** @var Resource $resource */
		foreach ($material->resources as $resource) {
			$generator = $resource->getPreviewGenerator();

			if (!$generator->imagePreviewAble($resource)) {
				continue;
			}

			return TRUE;
		}

	}

	/**
	 * @param Material $material
	 * @return \Intervention\Image\Image
	 */
	public function getCachedMaterialPreview(Material $material) {

		// see:  https://github.com/Intervention/imagecache/blob/master/src/Intervention/Image/ImageCache.php
		$cacheKey  = $this->getCacheKey($material);
		$imageData = $this->getImageObjectFromCache($cacheKey, FALSE);

		if (!$imageData) {
			$imageData = $this->getFreshMaterialPreview($material);
			$this->putImageObjectToCache($imageData, $cacheKey);
		}

		return $imageData;
	}

	/**
	 * @param Material $material
	 * @return \Intervention\Image\Image
	 */
	public function getFreshMaterialPreview(Material $material) {

		/**
		 * @var Resource $resource
		 */
		foreach ($material->resources as $resource) {
			$generator = $resource->getPreviewGenerator();

			if (!$generator->imagePreviewAble($resource)) {
				continue;
			}

			$limitationStartValue = $this->getLimitationPreviewValue($resource);
			$size                 = new Size(config('app.resource.preview.maxWidth'),
											 config('app.resource.preview.maxHeight'));

			$preview = $generator->getImagePreview($resource, $size, $limitationStartValue);

			return $preview;

		}

		return $this->resourcePreviewService->getImageWithText('no Preview', 100, 100);

	}

	/**
	 * @param Resource $resource
	 * @return float|int|null
	 */
	protected function getLimitationPreviewValue(Resource $resource) {

		$limitation = $resource->pivot->limitation;
		$result     = 0;

		if ($limitation === NULL) {
			if ($resource instanceof PdfFile) {
				$result = 1;
			}
		} else if ($limitation instanceof PageLimitation) {
			$result = array_shift($limitation->getPages());
		} else if ($limitation instanceof TimeLimitation) {
			$result = $limitation->getStart();
		}

		return $result;

	}

	public function clearCachedMaterialPreview(Material $material) {


	}
}