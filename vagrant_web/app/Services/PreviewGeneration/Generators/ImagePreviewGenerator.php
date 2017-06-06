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
use Illuminate\Support\Facades\View;
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

	/**
	 * @param ResourceEntity $resource
	 * @param string|null    $context
	 * @return string|false
	 */
	public function renderHTMLPreview(ResourceEntity $resource, $context = NULL) {

		/** @var $resource File */
		if ($resource->hasRemoteFile()) {
			$src = $resource->remote_path;
		} else {
			$maxWidth  = config('app.resource.preview.maxWidth');
			$maxHeight = config('app.resource.preview.maxHeight');
			$src       = route('resource.image.preview',
							   ['resource' => $resource->id,
								'width'    => $maxWidth,
								'height'   => $maxHeight]);
		}

		$view = View::make('resources.generators.image')
					->with('resource', $resource)
					->with('src', $src)
					->with('title', empty($resource->notes) ? 'Bild' : $resource->notes);

		return $view->render();
	}

	/**
	 * @param Resource $resource
	 * @return bool
	 */
	public function previewAble(ResourceEntity $resource) {
		return $resource instanceof ImageFile;
	}
}