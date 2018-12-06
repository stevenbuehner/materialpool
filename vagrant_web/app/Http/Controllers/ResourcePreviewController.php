<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidResourceTypeException;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Services\PreviewGeneration\ResourcePreviewService;
use App\Services\ResourceHandling\FileHandlingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Intervention\Image\Size;

class ResourcePreviewController extends Controller {

	protected $previewService;
	protected $fileHandlingService;

	public function __construct(ResourcePreviewService $previewService, FileHandlingService $fhs) {
		$this->previewService      = $previewService;
		$this->fileHandlingService = $fhs;
	}

	/**
	 * @param Resource $resource
	 * @param null     $width
	 * @param null     $height
	 * @return \Illuminate\Http\Response
	 */
	public function getImage(Resource $resource, $width = NULL, $height = NULL) {

		// Get Image (with GD or Imagick Library)
		// $image = $this->previewService->getImagePreviewByWidthAndHeight($resource, $width, $height);

		$encodedImage = $this->previewService->getCachedImage($resource, new Size($width, $height));

		// create response and add encoded image data
		$response = Response::make($encodedImage->getEncoded());

		// set content type
		$response->header('Content-Type', $encodedImage->mime());

		return $response;

	}

	public function getPageImage(Resource $resource, $page, $width = 100) {

		if (!$resource instanceof PdfFile) {
			throw new InvalidResourceTypeException('Only PdfResources can have page-preview images');
		}

		$localPdfPath = $this->fileHandlingService->getLocalFilePath($resource);

		try {
			// Todo: Noch besser wäre direkt via convert -verbose -density 144 /home/vagrant/web/storage/app/resources/1/doc/DaZzBkRHHMdBr7IU4JC5sCz5EG5Ppbh0Ko6HFYrs.pdf[1] -quality 90 -flatten -trim test.png

			$im = new \Imagick();
			$im->setResolution(config('app.preview.resolution'), config('app.preview.resolution'));
			$im->readImage(sprintf('%s[%s]', $localPdfPath, $page - 1));

			// Hintergrund im bei transparenten Geschichten (z.B. in PDFs) weiß nehmen und AlphaChannel entfernen
			$im->setBackgroundColor('white');
			$im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
			$im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);

			$im->setFormat(config('app.preview.outputFormat', 'png'));
		} catch (\ImagickException $e) {

			Log::error('Imagick-Error!', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			]);

			return response('Imagick Error', 500);
		}

		$response = Response::make(
			$im, 200
		);

		$response->header(
			'content-type', config('app.preview.outputFormat')
		);

		return $response;

	}
}
