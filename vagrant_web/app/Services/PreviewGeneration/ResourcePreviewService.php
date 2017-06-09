<?php

namespace App\Services\PreviewGeneration;

use App\Models\Resource as ResourceEntity;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Intervention\Image\Gd\Font;
use Intervention\Image\ImageManagerStatic;
use Intervention\Image\Size;

class ResourcePreviewService {

	// Not implemented yet
	protected $usePreviewImageCache = FALSE;

	public function hasPreview(ResourceEntity $resource) {

		/** @var PreviewGeneratorInterface $generator */
		$generator = $resource->getPreviewGenerator();

		return $generator->previewAble($resource);
	}

	public function getPreviewImage(ResourceEntity $resource, $maxWidth = NULL, $maxHeight = NULL) {

		if ($maxWidth === NULL) {
			$maxWidth = config('app.resource.preview.maxWidth');
		}

		if ($maxHeight === NULL) {
			$maxHeight = config('app.resource.preview.maxHeight');
		}

		if ($this->usePreviewImageCache) {
			// Not implemented yet
		}

		$size = new Size($maxWidth, $maxHeight);

		try {
			/** @var PreviewGeneratorInterface $generator */
			$generator = $resource->getPreviewGenerator();
			$image     = $generator->getImagePreview($resource, $size);
		} catch (NotPreviewAbleException $e) {
			$image = ImageManagerStatic::canvas(max($size->getWidth(), 100), max($size->getHeight(), 100), '#33ffff');
			$image->text('No Preview', 50, 50, function (Font $font) {
				$font->valign('top');
			});
		}

		return $image->response('jpg');
	}


}