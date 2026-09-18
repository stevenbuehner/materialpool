<?php

namespace App\Services\PreviewGeneration;

use App\Models\DocumentFile;
use App\Models\Resource;
use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Generators\NoPreviewGenerator;
use App\Services\PreviewGeneration\Generators\DocumentPreviewGenerator;
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
	 * @param null $width
	 * @param null $height
	 * @param null $pageOrSeconds
	 * @return Image
	 */
	public function getImagePreviewByWidthAndHeight(ResourceEntity $resource, $width = NULL, $height = NULL, $pageOrSeconds = NULL) {

		if ($width === NULL) {
			$width = PreviewSize::large()->getWidth();
		}

		if ($height === NULL) {
			$height = PreviewSize::large()->getHeight();
		}

		$size = new Size($width, $height);

		return $this->getCachedImage($resource, $size, $pageOrSeconds);

	}

	/**
	 * Wraps the generator function of the resource and catches errors to log them but not show them in the frontend
	 *
	 * @param ResourceEntity $resource
	 * @param Size $size
	 * @param null|int $pageOrSeconds Page counting from 1 or seconds offset of Video/Audio
	 * @return Image
	 * @throws NotPreviewAbleException
	 */
	public function getFreshImagePreview(ResourceEntity $resource, Size $size, ?int $pageOrSeconds = NULL) {

		$image = NULL;

		try {
			/** @var PreviewGeneratorInterface $generator */
			$generator = $resource->getPreviewGenerator();
			$image     = $generator->getImagePreview($resource, $size, $pageOrSeconds);

		} catch (NotPreviewAbleException $e) {

			$previous = $e->getPrevious() !== NULL ? $e->getPrevious()->getMessage() : NULL;
			Log::error($e->getMessage(),
				['trace'         => $e->getTraceAsString(),
				 'previousError' => $previous]
			);

			throw $e;

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
			$font->file(resource_path('fonts/Courier New.ttf'));
		});

		return $image;
	}

	/**
	 * @param ResourceEntity $resource
	 * @param Size $size
	 * @param null $pageOrSeconds
	 * @param bool $clearCache
	 * @return Image
	 */
	public function getCachedImage(ResourceEntity $resource, Size $size, $pageOrSeconds = NULL, bool $clearCache = FALSE) {
		return $this->imageManager->make($this->getCachedImageData($resource, $size, $pageOrSeconds, $clearCache));
	}

	public function getCachedImageData(ResourceEntity $resource, Size $size, $pageOrSeconds = NULL, bool $clearCache = FALSE): string {

		$cacheKey = $this->getCacheKey($resource, [$size, (int)$pageOrSeconds]);

		try {
			return $this->cacheImageData(
				$cacheKey,
				fn () => $this->getFreshImagePreview($resource, $size, $pageOrSeconds),
				fn () => $this->registerCacheKey($resource, $cacheKey),
				$clearCache
			);
		} catch (NotPreviewAbleException $e) {
			$generator = resolve(NoPreviewGenerator::class);

			return (string)$generator->getImagePreview($resource, $size, $pageOrSeconds)->encode(
				config('app.preview.outputFormat'),
				config('app.resource.preview.quality')
			);
		}

	}

	public function hasCachedImage(ResourceEntity $resource, Size $size, $pageOrSeconds = NULL): bool {
		$cacheKey = $this->getCacheKey($resource, [$size, (int)$pageOrSeconds]);

		return $this->getCacheStore()->has($cacheKey);
	}

	public function clearAllImageCaches(ResourceEntity $resource): void {
		$cache = $this->getCacheStore();
		$indexKey = $this->getCacheIndexKey($resource);

		$cache->lock($this->getCacheIndexLockKey($resource), 10)->block(5, function () use ($cache, $indexKey): void {
			$cacheKeys = $cache->get($indexKey, []);

			foreach ($cacheKeys as $cacheKey) {
				$cache->delete($cacheKey);
			}

			$cache->delete($indexKey);
		});

		if ($resource instanceof DocumentFile) {
			resolve(DocumentPreviewGenerator::class)->clearTemporaryPreviews($resource);
		}
	}

	protected function registerCacheKey(ResourceEntity $resource, string $cacheKey): void {
		$cache = $this->getCacheStore();
		$indexKey = $this->getCacheIndexKey($resource);

		$cache->lock($this->getCacheIndexLockKey($resource), 10)->block(5, function () use ($cache, $indexKey, $cacheKey): void {
			$cacheKeys = $cache->get($indexKey, []);

			if (!in_array($cacheKey, $cacheKeys, TRUE)) {
				$cacheKeys[] = $cacheKey;
				$cache->forever($indexKey, $cacheKeys);
			}
		});
	}

	protected function getCacheIndexKey(ResourceEntity $resource): string {
		return 'resource-preview-index:' . $resource->getKey();
	}

	protected function getCacheIndexLockKey(ResourceEntity $resource): string {
		return 'resource-preview-index-lock:' . $resource->getKey();
	}

	/**
	 * Wraps the generator function of the resource and catches errors to log them but not show them in the frontend
	 *
	 * @param ResourceEntity $resource
	 * @param ResourceLimitationInterface|NULL $limitation
	 * @param null $context
	 * @return false|string
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ?ResourceLimitationInterface $limitation = NULL, $context = NULL, $size = 'large') {

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
