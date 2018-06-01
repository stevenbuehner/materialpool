<?php

namespace App\Services\PreviewGeneration;

use App\Models\Resource;
use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Gd\Font;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic;
use Intervention\Image\Size;
use League\Flysystem\FileNotFoundException;

class ResourcePreviewService {

	// Not implemented yet
	protected $usePreviewImageCache   = NULL;
	protected $cacheLifeTimeInMinutes = NULL;

	public function __construct() {
		$this->usePreviewImageCache   = config('app.resource.preview.useCache');
		$this->cacheLifeTimeInMinutes = config('app.resource.preview.cacheTime');
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
	 * @return mixed
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
	 * @return Image
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size) {

		$cache     = Cache::getStore();
		$cacheName = $this->getPreviewPath($resource, $size->getWidth(), $size->getHeight());

		// Load the preview
		if ($this->usePreviewImageCache === TRUE && NULL !== $encoded = $cache->get($cacheName)) {

			$manager = new ImageManager();
			$image   = $manager->make($encoded);
		} else {

			try {
				/** @var PreviewGeneratorInterface $generator */
				$generator = $resource->getPreviewGenerator();
				$image     = $generator->getImagePreview($resource, $size);
			} catch (NotPreviewAbleException $e) {
				$image = $this->getImageWithText('No Preview', $size->getWidth(), $size->getHeight());
			} catch (FileNotFoundException $e) {
				$image = $this->getImageWithText('Resource missing', $size->getWidth(), $size->getHeight());
			}

			// Store the preview
			if ($this->usePreviewImageCache === TRUE) {
				$encoded = $image->encoded ? $image->encoded : (string) $image->encode();
				$cache->put($cacheName, $encoded, $this->cacheLifeTimeInMinutes);
			}
		}

		return $image->response();

	}

	protected function getPreviewPath(ResourceEntity $resource, $width = NULL, $height = NULL) {
		return $this->getPreviewDir($resource) . DIRECTORY_SEPARATOR . $this->getPreviewName($width, $height);
	}

	protected function getPreviewDir(ResourceEntity $resource) {
		return sprintf('res_%d', $resource->id);
	}

	protected function getPreviewName($width = NULL, $height = NULL) {
		return sprintf('thumb_%dx%d.jpg', $width, $height);
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
		$image     = ImageManagerStatic::canvas($useWidth, $useHeight, '#33ffff');
		$image->text($text, 50, 50, function (Font $font) {
			$font->valign('top');
		});

		return $image;
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