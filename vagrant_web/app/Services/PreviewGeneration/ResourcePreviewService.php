<?php

namespace App\Services\PreviewGeneration;

use App\Models\Resource;
use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Generators\NoPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\AbstractFont;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;
use League\Flysystem\FileNotFoundException;

class ResourcePreviewService {

	// Not implemented yet
	protected $usePreviewImageCache   = NULL;
	protected $cacheLifeTimeInMinutes = NULL;
	protected $imageManager;


	public function __construct(ImageManager $imageManager) {
		$this->usePreviewImageCache   = config('app.resource.preview.useCache');
		$this->cacheLifeTimeInMinutes = config('app.resource.preview.cacheTime');
		$this->imageManager           = $imageManager;
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
	 * @return Image
	 */
	public function getImagePreviewByWidthAndHeight(ResourceEntity $resource, $width = NULL, $height = NULL) {

		if ($width === NULL) {
			$width = config('app.resource.preview.maxWidth');
		}

		if ($height === NULL) {
			$height = config('app.resource.preview.maxHeight');
		}

		$size = new Size($width, $height);

		return $this->getImagePreview($resource, $size);
	}

	/**
	 * Wraps the generator function of the resource and catches errors to log them but not show them in the frontend
	 * It also adds cache functionality and default values
	 *
	 * @param ResourceEntity $resource
	 * @param Size           $size
	 * @param null|int       $pageOrSeconds Page counting from 1 or seconds offset of Video/Audio
	 * @return Image
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size, $pageOrSeconds = NULL) {

		try {
			/** @var PreviewGeneratorInterface $generator */
			$generator = $resource->getPreviewGenerator();
			$image     = $generator->getImagePreview($resource, $size, $pageOrSeconds);

		} catch (NotPreviewAbleException $e) {

			$generator = resolve(NoPreviewGenerator::class);
			$image     = $generator->getImagePreview($resource, $size);

		} catch (FileNotFoundException $e) {

			$image = $this->getImageWithText('Resource missing', $size->getWidth(), $size->getHeight());
		}


		return $image;

	}

	/**
	 * @param     $text
	 * @param int $width
	 * @param int $height
	 * @return \Intervention\Image\Image
	 */
	protected function getImageWithText($text, $width = 200, $height = 200) {
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

	public function getCachedImage(ResourceEntity $resource, Size $size, $pageOrSeconds = NULL, $format = 'jpg', $quality = 75) {

		$cache     = Cache::getStore();
		$format    = strtolower($format);
		$cacheName = $this->getPreviewPath($resource, $format, $size->getWidth(), $size->getHeight(), $quality,
										   $pageOrSeconds);

		// Load the preview
		if ($this->usePreviewImageCache === TRUE && NULL !== $encodedImage = $cache->get($cacheName)) {

		} else {

			$rawImage = $this->getImagePreview($resource, $size, $pageOrSeconds);

			// Store the preview
			if ($this->usePreviewImageCache === TRUE) {

				// Es macht nichts aus, dass nur JPG den Quality-Parameter versteht
				$encodedImage = $rawImage->encode('jpg', $quality);

				$cache->put($cacheName, $encodedImage, $this->cacheLifeTimeInMinutes);
			}
		}

		return $encodedImage;

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

	/**
	 * Wraps the generator function of the resource and catches errors to log them but not show them in the frontend
	 *
	 * @param ResourceEntity                   $resource
	 * @param ResourceLimitationInterface|NULL $limitation
	 * @param null                             $context
	 * @return false|string
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ResourceLimitationInterface $limitation = NULL, $context = NULL, $size) {

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
}