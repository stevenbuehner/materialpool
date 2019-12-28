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
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use App\Services\TagExtraction\ResourceHandles\TextContentInterface;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Intervention\Image\AbstractFont;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;

class TextLargePreviewGenerator implements PreviewGeneratorInterface {

	protected $imageManager;

	public function __construct(ImageManager $imageManager) {
		$this->imageManager = $imageManager;
	}

	/**
	 * @param ResourceEntity $resource
	 * @param Size $size
	 * @param null $page
	 * @return \Intervention\Image\Image
	 * @throws NotPreviewAbleException
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size, $page = NULL) {

		if (!$resource instanceof TextContentInterface) {
			throw new NotPreviewAbleException('Resource apparently has no content to preview');
		}

		$text  = trim(Str::limit($resource->getContent(), 500));
		$image = $this->imageManager
			->canvas($size->getWidth(), $size->getHeight(), '#fff')
			->text(wordwrap($text, round($size->width / 10)), 5, 5, function ($font) {
				/** @var $font AbstractFont */
				$font->valign('top');
				$font->size(14);
				$font->file(resource_path('fonts/Courier New.ttf'));
				// $font->align('left');
				// $font->valign('center');
			});

		/* Error in INtervention Image for Imagic\Font

					case 'top':
					$posY = $posY + $dimensions['textHeight'] * 0.65;
					break;

		Needs to be changed to:
		        case 'top':
                $posy = $posy + $dimensions['characterHeight'];
                break;
		 */


		return $image;

		/*

		$text      = Str::limit($resource->content, 500);
		$hAlgin    = 'center';
		$vAlign    = 'middle';
		$fontSize  = 12;
		$textColor = '#fff';
		$angle     = 0.0;
		$posY      = 0;
		$posX      = 0;


		// The draw settings
		// see: http://php.net/manual/en/imagick.annotateimage.php
		$draw = new \ImagickDraw();
		$draw->setStrokeAntialias(TRUE);
		$draw->setTextAntialias(TRUE);

		// set font file
		if ($this->hasApplicableFontFile()) {
			$draw->setFont($this->file);
		} else {
			throw new \Intervention\Image\Exception\RuntimeException(
				"Font file must be provided to apply text to image."
			);
		}

		$draw->setFontSize($fontSize);

		// see: http://php.net/manual/en/imagickpixel.construct.php
		$draw->setFillColor(new \ImagickPixel($textColor));

		// align horizontal
		switch (strtolower($hAlgin)) {
			case 'center':
				$align = \Imagick::ALIGN_CENTER;
				break;

			case 'right':
				$align = \Imagick::ALIGN_RIGHT;
				break;

			default:
				$align = \Imagick::ALIGN_LEFT;
				break;
		}
		$draw->setTextAlignment($align);

		// align vertical
		if (strtolower($vAlign) != 'bottom') {

			// calculate box size
			$dimensions = $image->getCore()->queryFontMetrics($draw, $text);

			// corrections on y-position
			switch (strtolower($vAlign)) {
				case 'center':
				case 'middle':
					$posY = $posY + $dimensions['textHeight'] * 0.65 / 2;
					break;

				case 'top':
					$posY = $posY + $dimensions['textHeight'] * 0.65;
					break;
			}
		}

		// apply to image
		$image->getCore()->annotateImage($draw, $posX, $posY, $angle * (-1), $text);


		return $image;

		*/
	}

	/**
	 * @param ResourceEntity $resource
	 * @param ResourceLimitationInterface $limitation
	 * @param string|null $context
	 * @return string|false
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ResourceLimitationInterface $limitation = NULL, $context = NULL) {

		/** @var $resource Text */

		$view = View::make('resources.generators.text-large')
			->with('resource', $resource)
			->with('context', $context)
			->with('content', $resource->content)
			->with('title', 'Textschnipsel');

		return $view->render();
	}

	/**
	 * @param Resource $resource
	 * @return bool
	 */
	public function imagePreviewAble(ResourceEntity $resource) {
		return FALSE;
	}

	/**
	 * @param Resource $resource
	 * @return bool
	 */
	public function htmlPreviewAble(ResourceEntity $resource) {
		return $resource instanceof Text;
	}
}