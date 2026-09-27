<?php

namespace App\Services\PreviewGeneration;

use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Resource;
use App\ResourceLimitations\PageLimitation;
use App\ResourceLimitations\TimeLimitation;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use Illuminate\Database\Eloquent\Model;
use Intervention\Image\Image;
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
		return $this->getPreviewResource($material) !== NULL;

	}

	public function getPreviewResource(Material $material): ?Resource {
		/** @var Resource $resource */
		foreach ($material->resources as $resource) {
			if ($resource->getPreviewGenerator()->imagePreviewAble($resource)) {
				return $resource;
			}
		}

		return NULL;
	}

	/**
	 * @param Material $material
	 * @return Image
	 * @throws NotPreviewAbleException
	 */
	public function getCachedMaterialPreview(Material $material) {
		return $this->imageManager->make($this->getCachedMaterialPreviewData($material, PreviewSize::large()));
	}

	public function getCachedMaterialPreviewData(Material $material, Size $size, bool $clearCache = FALSE): string {
		$cacheKey = $this->getCacheKey($material, [$size]);

		return $this->cacheImageData(
			$cacheKey,
			fn() => $this->getFreshMaterialPreview($material, $size),
			fn() => $this->registerCacheKey($material, $cacheKey),
			$clearCache
		);
	}

	/**
	 * @param Material $material
	 * @return Image
	 * @throws NotPreviewAbleException
	 */
	public function getFreshMaterialPreview(Material $material, ?Size $size = NULL) {

		$resource = $this->getPreviewResource($material);
		if ($resource !== NULL) {
			$generator = $resource->getPreviewGenerator();

			$limitationStartValue = $this->getLimitationPreviewValue($resource);
			$size                 ??= PreviewSize::large();

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

	protected function registerCacheKey(Material $material, string $cacheKey): void {
		$cache    = $this->getCacheStore();
		$indexKey = $this->getCacheIndexKey($material);

		$cache->lock($this->getCacheIndexLockKey($material), 10)->block(5, function () use ($cache, $indexKey, $cacheKey): void {
			$cacheKeys = $cache->get($indexKey, []);

			if (!in_array($cacheKey, $cacheKeys, TRUE)) {
				$cacheKeys[] = $cacheKey;
				$cache->forever($indexKey, $cacheKeys);
			}
		});
	}

	protected function getCacheIndexKey(Material $material): string {
		return 'material-preview-index:' . $material->getKey();
	}

	protected function getCacheIndexLockKey(Material $material): string {
		return 'material-preview-index-lock:' . $material->getKey();
	}

	public function clearImageCache(Model $model, $additionalData = NULL) {
		if (!$model instanceof Material || $additionalData !== NULL) {
			parent::clearImageCache($model, $additionalData);

			return;
		}

		$cache    = $this->getCacheStore();
		$indexKey = $this->getCacheIndexKey($model);

		$cache->lock($this->getCacheIndexLockKey($model), 10)->block(5, function () use ($cache, $indexKey, $model): void {
			foreach ($cache->get($indexKey, []) as $cacheKey) {
				$cache->delete($cacheKey);
			}

			$cache->delete($indexKey);
			parent::clearImageCache($model);
		});
	}

}
