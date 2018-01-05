<?php

namespace App\Services\PreviewGeneration\Interfaces;


use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use Intervention\Image\Image;
use Intervention\Image\Size;

interface PreviewGeneratorInterface {

	/**
	 * @param Resource $resource
	 * @return bool
	 */
	public function imagePreviewAble(ResourceEntity $resource);

	/**
	 * @param Resource $resource
	 * @param Size     $size
	 * @return Image
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size);

	/**
	 * @param Resource $resource
	 * @return bool
	 */
	public function htmlPreviewAble(ResourceEntity $resource);

	/**
	 * @param ResourceEntity              $resource
	 * @param ResourceLimitationInterface $limitation
	 * @param string|null                 $context
	 * @return string|false
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ResourceLimitationInterface $limitation = NULL, $context = NULL);

}