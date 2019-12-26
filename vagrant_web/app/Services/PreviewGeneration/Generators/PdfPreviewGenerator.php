<?php
/**
 * This file was created by  steven
 * Created: 05.06.17 21:33
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PreviewGeneration\Generators;

use App\Models\File;
use App\Models\PdfFile;
use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use App\Services\ResourceHandling\Exceptions\LocalFileDoesNotExistException;
use App\Services\ResourceHandling\Exceptions\RemoteFileDoesNotExistException;
use App\Services\ResourceHandling\FileHandlingService;
use Illuminate\Support\Facades\View;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;

class PdfPreviewGenerator implements PreviewGeneratorInterface {

	protected $imageManager;
	protected $resourceFileService;

	public function __construct(ImageManager $imageManager, FileHandlingService $fhs) {
		$this->imageManager        = $imageManager;
		$this->resourceFileService = $fhs;
	}


	/**
	 * @param ResourceEntity $resource
	 * @param Size $size
	 * @param int $page
	 * @return \Imagick|\Symfony\Component\HttpFoundation\Response
	 * @throws NotPreviewAbleException
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size, $page = 1) {

		try {

			if (!$resource instanceof File) {
				throw new NotPreviewAbleException('Resource is not of type file');
			}

			$localPdfPath = $this->resourceFileService->getLocalFilePath($resource);

		} catch (LocalFileDoesNotExistException $e) {
			throw new NotPreviewAbleException("No Preview can be created from the local path", 0, $e);
		} catch (RemoteFileDoesNotExistException $e) {
			throw new NotPreviewAbleException("No Preview can be created from the remote path", 0, $e);
		}

		try {

			$image = $this->getImagePreviewFromPdfPath($localPdfPath, $size, $page);

		} catch (\Exception $e) {
			throw new NotPreviewAbleException('Error when creating Preview', 0, $e);
		}

		return $image;

	}

	/**
	 * @param      $path
	 * @param Size $size
	 * @param int $page
	 * @return \Intervention\Image\Image
	 * @throws NotPreviewAbleException
	 */
	protected function getImagePreviewFromPdfPath($path, Size $size, $page = 1) {

		// Todo: Noch besser wäre direkt via convert -verbose -density 144 /home/vagrant/web/storage/app/resources/1/doc/DaZzBkRHHMdBr7IU4JC5sCz5EG5Ppbh0Ko6HFYrs.pdf[1] -quality 90 -flatten -trim test.png

		try {
			$im = new \Imagick();

			$im->setResolution(config('app.preview.resolution'), config('app.preview.resolution'));
			$im->readImage(sprintf('%s[%s]', $path, max(0, $page - 1)));

			// Hintergrund im bei transparenten Geschichten (z.B. in PDFs) weiß nehmen und AlphaChannel entfernen
			$im->setBackgroundColor('white');
			$im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
			$im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
			$im->scaleImage($size->getWidth(), $size->getHeight(), TRUE);

			$image = $this->imageManager->make($im);

		} catch (\ImagickException $e) {
			throw new NotPreviewAbleException('Could not create PreviewImage', 0, $e);
		}

		return $image;
	}

	/**
	 * @param ResourceEntity $resource
	 * @param ResourceLimitationInterface $limitation
	 * @param string|null $context
	 * @return string|false
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ResourceLimitationInterface $limitation = NULL, $context = NULL) {

		/** @var $resource PdfFile */
		$pageCount = $resource->page_count;

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
	public function htmlPreviewAble(ResourceEntity $resource) {
		return $this->imagePreviewAble($resource);
	}

	/**
	 * @param Resource $resource
	 * @return bool
	 */
	public function imagePreviewAble(ResourceEntity $resource) {
		return ($resource instanceof PdfFile && $resource->hasLocalFile() && $resource->localFileExists());
	}
}