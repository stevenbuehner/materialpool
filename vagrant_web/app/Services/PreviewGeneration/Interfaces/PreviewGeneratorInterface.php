<?php

namespace App\Services\PreviewGeneration\Interfaces;

use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use Intervention\Image\Image;
use Intervention\Image\Size;

interface PreviewGeneratorInterface {

	/**
	 * @param ResourceEntity $resource
	 * @return mixed
	 */
	public function imagePreviewAble(ResourceEntity $resource);

	/**
	 * @param ResourceEntity $resource
	 * @param Size           $size
	 * @param null|int       $page (optional) Starting from 1 to ... x
	 * @return Image
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size, $page);

	/**
	 * @param ResourceEntity $resource
	 * @return mixed
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