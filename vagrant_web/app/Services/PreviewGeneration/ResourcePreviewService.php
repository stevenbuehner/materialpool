<?php

namespace App\Services\PreviewGeneration;

use App\Models\Resource;
use App\Models\Resource as ResourceEntity;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Gd\Font;
use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic;
use Intervention\Image\Size;

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

		return $generator->previewAble($resource);
	}

	public function getPreviewImage(ResourceEntity $resource, $width = NULL, $height = NULL) {
		$cache     = Cache::getStore();
		$cacheName = $this->getPreviewPath($resource, $width, $height);

		if ($width === NULL) {
			$maxWidth = config('app.resource.preview.maxWidth');
		}

		if ($height === NULL) {
			$maxHeight = config('app.resource.preview.maxHeight');
		}

		// Load the preview
		if ($this->usePreviewImageCache === TRUE && NULL !== $encoded = $cache->get($cacheName)) {

			$manager = new ImageManager();
			$image   = $manager->make($encoded);
		} else {
			$size = new Size($width, $height);

			try {
				/** @var PreviewGeneratorInterface $generator */
				$generator = $resource->getPreviewGenerator();
				$image     = $generator->getImagePreview($resource, $size);
			} catch (NotPreviewAbleException $e) {
				$useWidth  = max($width, 200);
				$useHeight = max($height, 200);
				$image     = ImageManagerStatic::canvas($useWidth, $useHeight, '#33ffff');
				$image->text('No Preview', 50, 50, function (Font $font) {
					$font->valign('top');
				});
			}

			// Store the preview
			if ($this->usePreviewImageCache === TRUE) {
				$encoded = $image->encoded ? $image->encoded : (string) $image->encode();
				$cache->put($cacheName, $encoded, $this->cacheLifeTimeInMinutes);
			}
		}

		return $image->response();
	}

	protected function getPreviewPath(Resource $resource, $width = NULL, $height = NULL) {
		return $this->getPreviewDir($resource) . DIRECTORY_SEPARATOR . $this->getPreviewName($width, $height);
	}

	protected function getPreviewDir(Resource $resource) {
		return sprintf('res_%d', $resource->id);
	}

	protected function getPreviewName($width = NULL, $height = NULL) {
		return sprintf('thumb_%dx%d.jpg', $width, $height);
	}


}