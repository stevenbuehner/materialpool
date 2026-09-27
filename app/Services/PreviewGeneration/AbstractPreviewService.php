<?php

namespace App\Services\PreviewGeneration;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use RuntimeException;


abstract class AbstractPreviewService {

	// Not implemented yet
	protected ?int         $cacheLifeTimeInMinutes = NULL;
	protected ImageManager $imageManager;


	public function __construct(ImageManager $imageManager) {
		$cacheTime                    = config('app.resource.preview.cacheTime', NULL);
		$this->cacheLifeTimeInMinutes = $cacheTime < 0 ? NULL : $cacheTime;

		$this->imageManager = $imageManager;
	}

	public function clearImageCache(Model $model, $additionalData = NULL) {
		$key = $this->getCacheKey($model, $additionalData);
		$this->clearCache($key);
	}

	protected function getCacheKey(Model $model, $additionalData = NULL) {
		return $model->getTable() . $model->getKey() . json_encode($additionalData);
	}

	protected function clearCache($cacheKey) {
		$cache = $this->getCacheStore();

		$cache->delete($cacheKey);
	}

	/**
	 * @return \Illuminate\Cache\Repository|Repository
	 */
	protected function getCacheStore() {
		return Cache::store('previewimages');
	}

	/**
	 * @param      $cacheKey
	 * @param null $default
	 * @return Image|null
	 */
	protected function getImageObjectFromCache($cacheKey, $default = NULL) {
		// see:  https://github.com/Intervention/imagecache/blob/master/src/Intervention/Image/ImageCache.php

		$cache           = $this->getCacheStore();
		$cachedImageData = $cache->get($cacheKey);

		if (is_string($cachedImageData)) {
			return $this->imageManager->make($cachedImageData);
		}

		return $default;

	}

	protected function cacheImageData(string $cacheKey, callable $generateImage, ?callable $onCached = NULL, bool $clearCache = FALSE): string {
		$cache = $this->getCacheStore();

		if (!$clearCache && ($cachedImageData = $this->getImageDataFromCache($cacheKey)) !== NULL) {
			return $cachedImageData;
		}

		return $cache->lock(
			'preview-image-lock:' . hash('sha256', $cacheKey),
			config('app.resource.preview.cacheLockSeconds')
		)->block(config('app.resource.preview.cacheLockSeconds'), function () use ($cacheKey, $generateImage, $onCached, $clearCache): string {
			if ($clearCache) {
				$this->clearCache($cacheKey);
			}

			if (($cachedImageData = $this->getImageDataFromCache($cacheKey)) !== NULL) {
				return $cachedImageData;
			}

			$image = $generateImage();
			$this->putImageObjectToCache($image, $cacheKey);
			if ($onCached !== NULL) {
				$onCached();
			}

			return $this->getImageDataFromCache($cacheKey) ?? throw new RuntimeException('Preview image cache could not be populated.');
		});
	}

	protected function getImageDataFromCache($cacheKey): ?string {
		$cachedImageData = $this->getCacheStore()->get($cacheKey);

		return is_string($cachedImageData) ? $cachedImageData : NULL;
	}

	/**
	 * @param Image $image
	 * @param       $cacheKey
	 * @return Image
	 */
	protected function putImageObjectToCache(Image $image, $cacheKey) {
		// see:  https://github.com/Intervention/imagecache/blob/master/src/Intervention/Image/ImageCache.php

		$cache = $this->getCacheStore();

		// encode image data only if image is not encoded yet
		$encoded = (string)$image->encode(
			config('app.preview.outputFormat'),
			config('app.resource.preview.quality')
		);

		$cache->put($cacheKey, $encoded, $this->cacheLifeTimeInMinutes);

		return $image;
	}
}
