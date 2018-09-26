<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidResourceTypeException;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Services\PreviewGeneration\ResourcePreviewService;
use App\Services\ResourceHandling\FileHandlingService;
use Illuminate\Support\Facades\Response;

class ResourcePreviewController extends Controller {

	protected $previewService;
	protected $fileHandlingService;

	public function __construct(ResourcePreviewService $previewService, FileHandlingService $fhs) {
		$this->previewService      = $previewService;
		$this->fileHandlingService = $fhs;
	}

	public function getImage(Resource $resource, $width = NULL, $height = NULL) {

		return $this->previewService->getImagePreviewByWidthAndHeight($resource, $width, $height);
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
			$im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
			$im->setFormat(config('app.preview.outputFormat', 'png'));
		} catch (\ImagickException $e) {
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
