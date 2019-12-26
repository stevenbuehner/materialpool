<?php
/**
 * This file was created by  steven
 * Created: 05.06.17 21:33
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PreviewGeneration\Generators;


use App\Models\Resource as ResourceEntity;
use App\Models\Text;
use App\ResourceLimitations\ResourceLimitationInterface;
use Illuminate\Support\Facades\View;
use Intervention\Image\ImageManager;

class TextThumbPreviewGenerator extends TextLargePreviewGenerator {


	public function __construct(ImageManager $imageManager) {
		parent::__construct($imageManager);
	}

	/**
	 * @param ResourceEntity $resource
	 * @param ResourceLimitationInterface $limitation
	 * @param string|null $context
	 * @return string|false
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ResourceLimitationInterface $limitation = NULL, $context = NULL) {

		/** @var $resource Text */

		$view = View::make('resources.generators.text-thumb')
			->with('resource', $resource)
			->with('context', $context)
			->with('content', $resource->content)
			->with('title', 'Textschnipsel');

		return $view->render();
	}


}