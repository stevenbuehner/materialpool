<?php

namespace App\Services\ContextSearch\Extraction;

interface OcrProcessor {
	public function extractPage(string $pdfPath, int $pageNumber): OcrResult;
}
