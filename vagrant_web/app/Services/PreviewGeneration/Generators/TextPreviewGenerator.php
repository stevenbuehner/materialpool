<?php
/**
 * This file was created by  steven
 * Created: 05.06.17 21:33
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PreviewGeneration\Generators;


use App\Models\Resource as ResourceEntity;
use App\Models\Text;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Illuminate\Support\Facades\View;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;

class TextPreviewGenerator implements PreviewGeneratorInterface {

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

		/** @var $resource Text */

		$image = $this->imageManager->canvas($size->getWidth(), $size->getHeight(), '#000000')
									->text(str_limit($resource->content, 500));

		return $image;
	}

	/**
	 * @param ResourceEntity $resource
	 * @param string|null    $context
	 * @return string|false
	 */
	public function renderHTMLPreview(ResourceEntity $resource, $context = NULL) {

		/** @var $resource Text */

		$view = View::make('resources.generators.text')
					->with('resource', $resource)
					->with('content', $resource->content)
					->with('title', 'Textschnipsel');

		return $view->render();
	}

	/**
	 * @param Resource $resource
	 * @return bool
	 */
	public function previewAble(ResourceEntity $resource) {
		return $resource instanceof Text;
	}
}