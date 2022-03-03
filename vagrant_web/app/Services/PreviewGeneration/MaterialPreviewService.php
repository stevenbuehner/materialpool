<?php

namespace App\Services\PreviewGeneration;

use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Resource;
use App\ResourceLimitations\PageLimitation;
use App\ResourceLimitations\TimeLimitation;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;

class MaterialPreviewService extends AbstractPreviewService {

	protected ResourcePreviewService $resourcePreviewService;

	public function __construct(ResourcePreviewService $resourcePreviewService, ImageManager $imageManager) {
		parent::__construct($imageManager);
		$this->resourcePreviewService = $resourcePreviewService;
	}

	/**
	 * @param Material $material
	 * @return bool
	 */
	public function hasPreview(Material $material): bool {

		/** @var Resource $resource */
		foreach ($material->resources as $resource) {
			$generator = $resource->getPreviewGenerator();

			if (!$generator->imagePreviewAble($resource)) {
				continue;
			}

			return TRUE;
		}

		return FALSE;

	}

	/**
	 * @param Material $material
	 * @return \Intervention\Image\Image
	 * @throws NotPreviewAbleException
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
	 * @throws NotPreviewAbleException
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
			$size                 = new Size(
				config('app.resource.preview.maxWidth'),
				config('app.resource.preview.maxHeight')
			);

			$preview = $generator->getImagePreview($resource, $size, $limitationStartValue);

			return $preview;

		}

		throw new NotPreviewAbleException();

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
			$pages  = $limitation->getPages();
			$result = array_shift($pages);
		} else if ($limitation instanceof TimeLimitation) {
			$result = $limitation->getStart();
		}

		return $result;

	}

}