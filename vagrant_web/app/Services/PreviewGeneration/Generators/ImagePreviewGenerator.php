<?php
/**
 * This file was created by  steven
 * Created: 05.06.17 21:33
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PreviewGeneration\Generators;


use App\Models\File;
use App\Models\ImageFile;
use App\Models\Resource as ResourceEntity;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Intervention\Image\Constraint;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;

class ImagePreviewGenerator implements PreviewGeneratorInterface {

	protected $imageManager;

	public function __construct(ImageManager $imageManager) {
		$this->imageManager = $imageManager;
	}

	/**
	 * @param Resource $resource
	 * @return bool
	 */
	public function previewAble(ResourceEntity $resource) {
		return $resource instanceof ImageFile;
	}

	/**
	 * @param  Resource $resource
	 * @param  int      $maxWidth
	 * @param  int      $maxHeight
	 * @throws NotPreviewAbleException
	 * @return Image
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size) {
		/** @var $resource File */

		if ($resource->hasLocalFile()) {

			try {

				$localFile = $resource->getLocalFile();
				$image     = $this->imageManager->make($localFile);


			} catch (\Exception $e) {
				throw new NotPreviewAbleException("Error while creating preview image", 0, $e);
			}

		} else if ($resource->hasRemoteFile()) {
			$image = $this->imageManager->make($resource->remote_path);
		} else {
			throw new NotPreviewAbleException();
		}

		return $image->resize($size->getWidth(), $size->getHeight(), function (Constraint $constraint) {
			$constraint->aspectRatio();
			$constraint->upsize();
		});

	}
}