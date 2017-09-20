<?php
/**
 * This file was created by  steven
 * Created: 05.06.17 21:33
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PreviewGeneration\Generators;

use App\Models\PdfFile;
use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PdfPreview\LocalPdfFileProvider;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Illuminate\Support\Facades\View;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;
use StevenBuehner\PdfPreview\Exceptions\FileWasNotRetrieveableException;

class PdfPreviewGenerator implements PreviewGeneratorInterface {

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

		$pdfProvider = new LocalPdfFileProvider();
		try {
			$localPdfPath = $pdfProvider->getLocalPdfPath($resource->id);
		} catch (FileWasNotRetrieveableException $e) {
			throw new NotPreviewAbleException("No Preview can be created from this", 0, $e);
		}


		try {
			// Todo: Noch besser wäre direkt via convert -verbose -density 144 /home/vagrant/web/storage/app/resources/1/doc/DaZzBkRHHMdBr7IU4JC5sCz5EG5Ppbh0Ko6HFYrs.pdf[1] -quality 90 -flatten -trim test.png

			$im = new \Imagick();
			$im->setResolution(config('pdfpreview.resolution'), config('pdfpreview.resolution'));
			$im->readImage(sprintf('%s[%s]', $localPdfPath, 0));
			$im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
			$im->setFormat(config('pdfpreview.outputFormat', 'png'));
		} catch (\ImagickException $e) {
			return response('Imagick Error', 500);
		}

		$image = \Intervention\Image\Facades\Image::make($im->getImagesBlob());

		return $image;
	}

	/**
	 * @param ResourceEntity              $resource
	 * @param ResourceLimitationInterface $limitation
	 * @param string|null                 $context
	 * @return string|false
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ResourceLimitationInterface $limitation = NULL, $context = NULL) {

		/** @var $resource PdfFile */
		$pageCount = $resource->getPdfCountAndSaveCache();

		$view = View::make('resources.generators.pdf')
					->with('resource', $resource)
					->with('context', $context)
					->with('title', empty($resource->notes) ? 'PDF' : $resource->notes)
					->with('limitation', is_null($limitation) ? FALSE : $limitation)
					->with('totalPageCount', $pageCount);

		return $view->render();
	}

	/**
	 * @param Resource $resource
	 * @return bool
	 */
	public function previewAble(ResourceEntity $resource) {
		return $resource instanceof PdfFile;
	}
}