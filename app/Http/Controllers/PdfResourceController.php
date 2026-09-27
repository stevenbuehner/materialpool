<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\PdfFile;
use App\ResourceLimitations\PageLimitation;
use App\Services\ResourceHandling\PdfHandlingService;
use Exception;

class PdfResourceController extends Controller {

	use ResourceHelperTrait;

	protected $pdfService;

	public function __construct(PdfHandlingService $pdfHandlingService) {
		$this->middleware(['auth']);

		$this->pdfService = $pdfHandlingService;
	}

	public function downloadPages(PdfFile $resource, Material $material) {

		$resourceWithLimitation = $material->resources()->where('resources.id', '=', $resource->id)->first();
		$limitation             = $resourceWithLimitation->pivot->limitation;
		$pages                  = $limitation instanceof PageLimitation ? $limitation->getPages() : NULL;
		$filename               = 'limited_' . $resource->original_filename;

		try {
			$pdf = $this->pdfService->extractPdfPages($resource, $pages);
			$pdf->Output('D', $filename);
		} catch (Exception $e) {
			throw $e;
		}

	}

}
