<?php

namespace App\Services\PreviewGeneration\Interfaces;


use App\Models\Resource as ResourceEntity;
use Intervention\Image\Image;
use Intervention\Image\Size;

interface PreviewGeneratorInterface {

	/**
	 * @param Resource $resource
	 * @return bool
	 */
	public function previewAble(ResourceEntity $resource);

	/**
	 * @param Resource $resource
	 * @param Size     $size
	 * @return Image
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size);

	/**
	 * @param ResourceEntity $resource
	 * @param string|null    $context
	 * @return string|false
	 */
	public function renderHTMLPreview(ResourceEntity $resource, $context = NULL);

}