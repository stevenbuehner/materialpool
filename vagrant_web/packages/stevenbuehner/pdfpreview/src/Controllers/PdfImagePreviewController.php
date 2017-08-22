<?php

namespace StevenBuehner\PdfPreview\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use StevenBuehner\PdfPreview\Interfaces\LocalPdfProviderInterface;

class PdfImagePreviewController extends Controller {
	//

	public function loadPage($fileId, $page, $width = 100) {

		/** @var LocalPdfProviderInterface $pdfProvider */
		$pdfProvider  = resolve(LocalPdfProviderInterface::class);
		$localPdfPath = $pdfProvider->getLocalPdfPath($fileId);

		// Best Way to get Number of Pages
		/*
		$start = microtime(TRUE);
		$im    = new \Imagick();
		$im->pingImage($localPdfPath);
		$countIm = $im->getNumberImages();
		$timeIM  = microtime(TRUE) - $start;
		*/


		try {
			// Todo: Noch besser wäre direkt via convert -verbose -density 144 /home/vagrant/web/storage/app/resources/1/doc/DaZzBkRHHMdBr7IU4JC5sCz5EG5Ppbh0Ko6HFYrs.pdf[1] -quality 90 -flatten -trim test.png

			$im = new \Imagick();
			$im->setResolution(config('pdfpreview.resolution'), config('pdfpreview.resolution'));
			$im->readImage(sprintf('%s[%s]', $localPdfPath, $page - 1));
			$im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
			$im->setFormat(config('pdfpreview.outputFormat', 'png'));
		} catch (\ImagickException $e) {
			return response('Imagick Error', 500);
		}

		$response = Response::make(
			$im, 200
		);
		$response->header(
			'content-type', config('pdfpreview.outputFormat')
		);

		return $response;
	}
}
