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
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Illuminate\Support\Facades\View;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;

class ImagePreviewGenerator implements PreviewGeneratorInterface {

	protected $imageManager;

	public function __construct(ImageManager $imageManager) {
		$this->imageManager = $imageManager;
	}

	/**
	 * @param ResourceEntity $resource
	 * @param Size           $size
	 * @param null           $page
	 * @return \Imagick
	 * @throws NotPreviewAbleException
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size, $page = NULL) {
		/** @var $resource File */

		if ($resource->hasLocalFile()) {

			try {

				$localFile = $resource->getLocalFile();
				$image     = $this->imageManager->make($localFile);


				// Todo: Noch besser wäre direkt via convert -verbose -density 144 /home/vagrant/web/storage/app/resources/1/doc/DaZzBkRHHMdBr7IU4JC5sCz5EG5Ppbh0Ko6HFYrs.pdf[1] -quality 90 -flatten -trim test.png

				// $image = new \Imagick();
				// $image->setResolution(config('app.preview.resolution'), config('app.preview.resolution'));
				// $image->readImage($localFile);

				// Hintergrund im bei transparenten Geschichten (z.B. in PDFs) weiß nehmen und AlphaChannel entfernen
				// $im->setBackgroundColor('white');
				// $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
				// $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);

				// $im->setFormat(config('app.preview.outputFormat', 'png'));


				// $image = $this->imageManager->make($localFile);

			} catch (\Exception $e) {
				throw new NotPreviewAbleException("Error while creating preview image", 0, $e);
			}

		} else if ($resource->hasRemoteFile()) {

			$image = $this->imageManager->make($resource->remote_path);

		} else {
			throw new NotPreviewAbleException('Neither local nor remote file exist to generate preview from');
		}

		return $image->resize($size->getWidth(), $size->getHeight(), function (Constraint $constraint) {
			$constraint->aspectRatio();
			$constraint->upsize();
		});

	}

	/**
	 * @param ResourceEntity              $resource
	 * @param ResourceLimitationInterface $limitation
	 * @param string|null                 $context
	 * @return string|false
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ResourceLimitationInterface $limitation = NULL, $context = NULL) {

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
					->with('context', $context)
					->with('src', $src)
					->with('title', empty($resource->notes) ? 'Bild' : $resource->notes);

		return $view->render();
	}

	/**
	 * @param ResourceEntity $resource
	 * @return bool|mixed
	 */
	public function htmlPreviewAble(ResourceEntity $resource) {
		return $this->imagePreviewAble($resource);
	}

	/**
	 * @param ResourceEntity $resource
	 * @return bool|mixed
	 */
	public function imagePreviewAble(ResourceEntity $resource) {
		return ($resource instanceof ImageFile && $resource->hasLocalFile() && $resource->localFileExists());
	}
}