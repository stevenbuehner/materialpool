<?php
/**
 * This file was created by  steven
 * Created: 05.06.17 21:21
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PreviewGeneration\Generators;


use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Intervention\Image\AbstractFont;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;

class NoPreviewGenerator implements PreviewGeneratorInterface {

	protected $imageManager;

	public function __construct(ImageManager $imageManager) {
		$this->imageManager = $imageManager;
	}

	/**
	 * @param ResourceEntity $resource
	 * @return bool
	 */
	public function imagePreviewAble(ResourceEntity $resource) {
		return FALSE;
	}


	public function getImagePreview(ResourceEntity $resource, Size $size, $page = NULL) {

		$useWidth  = max($size->width, 500);
		$useHeight = max($size->height, 500);
		$image     = $this->imageManager->canvas($useWidth, $useHeight, '#ffff');

		$image->text('No Preview', 50, 50, function ($font) {
			/** @var $font AbstractFont */
			$font->valign('top');
			$font->size(14);
			$font->file(resource_path('assets/fonts/Courier New.ttf'));
		});

		return $image;
	}

	/**
	 * @param ResourceEntity                   $resource
	 * @param ResourceLimitationInterface|NULL $limitation
	 * @param null                             $context
	 * @return false|string|void
	 * @throws NotPreviewAbleException
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ResourceLimitationInterface $limitation = NULL, $context = NULL) {
		throw new NotPreviewAbleException();
	}

	/**
	 * @param ResourceEntity $resource
	 * @return bool
	 */
	public function htmlPreviewAble(ResourceEntity $resource) {
		return FALSE;
	}
}