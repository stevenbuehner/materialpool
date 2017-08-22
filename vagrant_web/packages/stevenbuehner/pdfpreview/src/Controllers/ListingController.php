<?php

namespace StevenBuehner\PdfPreview\Controllers;

use App\Http\Controllers\Controller;
use StevenBuehner\PdfPreview\Interfaces\LocalPdfProviderInterface;

class ListingController extends Controller {
	//

	public function index($resource) {

		$view = view('PdfPreview::preview', [
			'fileId'            => $resource,
			'imagePreviewRoute' => config('pdfpreview.imagePreviewRoute'),
		]);

		// Get Number of PDF-Pages
		$pdfProvider  = resolve(LocalPdfProviderInterface::class);
		$localPdfPath = $pdfProvider->getLocalPdfPath($resource);
		// Best Way to get Number of Pages
		$im = new \Imagick();
		$im->pingImage($localPdfPath);
		$view->with('pageCount', $im->getNumberImages());

		return $view;
	}

	protected function countPdfPages() {

	}
}
