<?php

namespace StevenBuehner\PdfPreview\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use StevenBuehner\PdfPreview\Interfaces\LocalPdfProviderInterface;

class ListingController extends Controller {
	// Example Controller

	public function index($resource) {

		// Get Number of PDF-Pages
		$pdfProvider  = resolve(LocalPdfProviderInterface::class);
		$localPdfPath = $pdfProvider->getLocalPdfPath($resource);
		$pageCount    = $this->countPdfPages($localPdfPath);

		return view('PdfPreview::preview', $this->getViewParams($resource, $pageCount));

	}

	public function countPdfPages($localPdfPath, $useCache = TRUE) {
		$cacheKey = 'pdfPageCount:' . $localPdfPath;

		$numPages = Cache::remember($cacheKey, 60 * 24, function () use ($localPdfPath) {
			$im = new \Imagick();
			$im->pingImage($localPdfPath);

			return $im->getNumberImages();
		});


		return $numPages;
	}

	public function getViewParams($fileId, $pageCount) {
		return [
			'fileId'            => $fileId,
			'pageCount'         => $pageCount,
			'imagePreviewRoute' => config('pdfpreview.imagePreviewRoute'),
		];
	}


}
