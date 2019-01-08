<?php

namespace App\Services\PreviewGeneration;

use App\Models\Resource;
use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Generators\NoPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use Intervention\Image\AbstractFont;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;

class ResourcePreviewService extends AbstractPreviewService {


	public function __construct(ImageManager $imageManager) {
		parent::__construct($imageManager);
	}

	public function hasPreview(ResourceEntity $resource) {

		/** @var PreviewGeneratorInterface $generator */
		$generator = $resource->getPreviewGenerator();

		return $generator->imagePreviewAble($resource);
	}

	/**
	 *
	 * @param ResourceEntity $resource
	 * @param null           $width
	 * @param null           $height
	 * @param null           $pageOrSeconds
	 * @return Image
	 */
	public function getImagePreviewByWidthAndHeight(ResourceEntity $resource, $width = NULL, $height = NULL, $pageOrSeconds = NULL) {

		if ($width === NULL) {
			$width = config('app.resource.preview.maxWidth');
		}

		if ($height === NULL) {
			$height = config('app.resource.preview.maxHeight');
		}

		$size = new Size($width, $height);

		return $this->getFreshImagePreview($resource, $size, $pageOrSeconds);
	}

	/**
	 * Wraps the generator function of the resource and catches errors to log them but not show them in the frontend
	 *
	 * @param ResourceEntity $resource
	 * @param Size           $size
	 * @param null|int       $pageOrSeconds Page counting from 1 or seconds offset of Video/Audio
	 * @return Image
	 */
	public function getFreshImagePreview(ResourceEntity $resource, Size $size, $pageOrSeconds = NULL) {

		try {
			/** @var PreviewGeneratorInterface $generator */
			$generator = $resource->getPreviewGenerator();
			$image     = $generator->getImagePreview($resource, $size, $pageOrSeconds);

		} catch (NotPreviewAbleException $e) {

			if ($e->getPrevious() instanceof FileNotFoundException) {
				return $this->getImageWithText('Resource missing', $size->getWidth(), $size->getHeight());
			}

			$generator = resolve(NoPreviewGenerator::class);

			try {
				$image = $generator->getImagePreview($resource, $size);
			} catch (NotPreviewAbleException $e) {
				Log::error($e->getMessage(), $e->getTraceAsString());
			}

		}


		return $image;

	}

	/**
	 * @param     $text
	 * @param int $width
	 * @param int $height
	 * @return \Intervention\Image\Image
	 */
	public function getImageWithText($text, $width = 200, $height = 200) {
		$useWidth  = max($width, 200);
		$useHeight = max($height, 200);
		$image     = $this->imageManager->canvas($useWidth, $useHeight, '#ffff');
		$image->text($text, 50, 50, function ($font) {
			/** @var $font AbstractFont */
			$font->valign('top');
			$font->size(14);
			$font->file(resource_path('assets/fonts/Courier New.ttf'));
		});

		return $image;
	}

	/**
	 * @param ResourceEntity $resource
	 * @param Size           $size
	 * @param null           $pageOrSeconds
	 * @return Image
	 */
	public function getCachedImage(ResourceEntity $resource, Size $size, $pageOrSeconds = NULL) {

		$cacheKey = $this->getCacheKey($resource, [$size, $pageOrSeconds]);

		// Load the preview
		if (NULL !== $encodedImage = $this->getImageObjectFromCache($cacheKey)) {

			return $encodedImage;

		} else {

			$rawImage = $this->getFreshImagePreview($resource, $size, $pageOrSeconds);

			$this->putImageObjectToCache($rawImage, $cacheKey);

			return $rawImage;
		}

	}

	/**
	 * Wraps the generator function of the resource and catches errors to log them but not show them in the frontend
	 *
	 * @param ResourceEntity                   $resource
	 * @param ResourceLimitationInterface|NULL $limitation
	 * @param null                             $context
	 * @return false|string
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ?ResourceLimitationInterface $limitation = NULL, $context = NULL, $size) {

		$generator = $resource->getPreviewGenerator($size);
		$result    = "";

		if ($this->htmlPreviewAble($resource, $size)) {
			$result = $generator->renderHTMLPreview($resource, $limitation, $context);
		} else {
			$result = 'Resource is not previewable';
		}

		return $result;
	}

	/**
	 * @param ResourceEntity $resource
	 * @return bool
	 */
	public function htmlPreviewAble(ResourceEntity $resource, $size) {
		return $resource->getPreviewGenerator($size)->htmlPreviewAble($resource);
	}

	/**
	 * Wraps the generator function of the resource and catches errors to log them but not show them in the frontend
	 *
	 * @param ResourceEntity $resource
	 * @return bool
	 */
	public function imagePreviewAble(ResourceEntity $resource, $size) {
		return $resource->getPreviewGenerator($size)->imagePreviewAble($resource);
	}

	protected function getPreviewPath(ResourceEntity $resource, $fileType, $width = NULL, $height = NULL, $quality = 75, $limitation = NULL) {
		return $this->getPreviewDir($resource)
			. DIRECTORY_SEPARATOR
			. $this->getPreviewName($fileType, $width, $height, $quality, $limitation);
	}

	protected function getPreviewDir(ResourceEntity $resource) {
		return sprintf('res_%d', $resource->id);
	}

	protected function getPreviewName($fileType, $width = NULL, $height = NULL, $quality = 75, $limitation = NULL) {
		return sprintf('thumb_%dx%d_%d_%d.%s', $width, $height, $limitation, $quality, strtolower($fileType));
	}
}