<?php

namespace App\Http\Controllers;

use App\Models\PdfFile;
use App\Services\PdfPreview\LocalPdfFileProvider;
use Illuminate\Support\Facades\Cache;
use StevenBuehner\PdfPreview\Interfaces\LocalPdfProviderInterface;

class PdfMaterialAssignmentController extends Controller {

	public function index(PdfFile $resource) {

		// Get Number of PDF-Pages
		/** @var LocalPdfFileProvider $pdfProvider */
		$pdfProvider  = resolve(LocalPdfProviderInterface::class);
		$localPdfPath = $pdfProvider->getLocalPdfPath($resource->id);
		$pageCount    = $this->countPdfPages($localPdfPath);

		return view('resources.assign.pdf.index', $this->getViewParams($resource, $pageCount));
	}

	public function countPdfPages($localPdfPath, $useCache = TRUE) {
		$cacheKey = 'pageNum:' . $localPdfPath;

		$numPages = Cache::remember($cacheKey, 60 * 24 * 7, function () use ($localPdfPath) {
			$im = new \Imagick();
			$im->pingImage($localPdfPath);

			return $im->getNumberImages();
		});


		return $numPages;
	}

	public function getViewParams($resource, $pageCount) {
		return [
			'fileId'            => $resource->id,
			'pageCount'         => $pageCount,
			'imagePreviewRoute' => config('pdfpreview.imagePreviewRoute'),
		];
	}


}
