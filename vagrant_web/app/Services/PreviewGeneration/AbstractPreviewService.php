<?php

namespace App\Services\PreviewGeneration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;


abstract class AbstractPreviewService {

	// Not implemented yet
	protected $cacheLifeTimeInMinutes = NULL;
	protected $imageManager;


	public function __construct(ImageManager $imageManager) {
		$this->cacheLifeTimeInMinutes = config('app.resource.preview.cacheTime');
		$this->imageManager           = $imageManager;
	}

	protected function getCacheKey(Model $model, $additionalData = NULL) {
		return $model->getTable() . $model->getKey() . json_encode($additionalData);
	}

	public function clearImageCache(Model $model, $additionalData = NULL){
		$key = $this->getCacheKey($model, $additionalData);
		$this->clearCache($key);
	}

	/**
	 * @param      $cacheKey
	 * @param null $default
	 * @return \Intervention\Image\Image|null
	 */
	protected function getImageObjectFromCache($cacheKey, $default = NULL) {
		// see:  https://github.com/Intervention/imagecache/blob/master/src/Intervention/Image/ImageCache.php

		$cache           = $this->getCacheStore();
		$cachedImageData = $cache->get($cacheKey);

		if ($cachedImageData) {
			return $this->imageManager->make($cachedImageData);
		} else {
			return $default;
		}

	}

	/**
	 * @return \Illuminate\Cache\Repository|\Illuminate\Contracts\Cache\Repository
	 */
	protected function getCacheStore() {
		return Cache::store('previewimages');
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
		$encoded = $image->encoded ? $image->encoded : (string)$image->encode();

		$cache->put($cacheKey, $encoded, $this->cacheLifeTimeInMinutes);

		return $image;
	}

	protected function clearCache($cacheKey) {
		$cache = $this->getCacheStore();

		$cache->delete($cacheKey);
	}
}