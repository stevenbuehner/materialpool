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
use Intervention\Image\Size;

class NoPreviewGenerator implements PreviewGeneratorInterface {

	/**
	 * @param ResourceEntity $resource
	 * @return bool
	 */
	public function imagePreviewAble(ResourceEntity $resource) {
		return FALSE;
	}


	public function getImagePreview(ResourceEntity $resource, Size $size, $page = NULL) {
		throw new NotPreviewAbleException();
	}

	/**
	 * @param ResourceEntity $resource
	 * @param ResourceLimitationInterface|NULL $limitation
	 * @param null $context
	 * @return false|string|void
	 * @throws NotPreviewAbleException
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ?ResourceLimitationInterface $limitation = NULL, $context = NULL) {
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
