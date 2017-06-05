<?php
/**
 * This file was created by  steven
 * Created: 05.06.17 21:21
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PreviewGeneration\Generators;


use App\Models\Resource;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Intervention\Image\Image;
use Intervention\Image\Size;

class NoPreviewGenerator implements PreviewGeneratorInterface {

	/**
	 * @param Resource $resource
	 * @return bool
	 */
	public function previewAble(Resource $resource) {
		return FALSE;
	}

	/**
	 * @param  Resource $resource
	 * @param  int      $maxWidth
	 * @param  int      $maxHeight
	 * @return Image
	 */
	public function getImagePreview(Resource $resource, Size $size) {
		throw new NotPreviewAbleException();
	}
}