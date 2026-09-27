<?php

namespace App\Services\ContextSearch\Extraction;

use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\Text;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\PdfHandlingService;
use InvalidArgumentException;

final class ResourceTextExtractor
{
    public function __construct(
        private readonly PdfHandlingService $pdfs,
        private readonly FileHandlingService $files,
        private readonly OcrProcessor $ocr,
        private readonly int $nativeTextMinimumCharacters,
        private readonly OcrQualityGate $qualityGate,
    ) {
    }

    /** @return array<int, ExtractedPage> */
    public function extract(Resource $resource): array
    {
        $pages = [];
        for ($pageNumber = 1; $pageNumber <= $this->pageCount($resource); $pageNumber++) {
            $pages[] = $this->extractPage($resource, $pageNumber);
        }

        return $pages;
    }

    public function pageCount(Resource $resource): int
    {
        if ($resource instanceof Text) {
            return 1;
        }

        if (! $resource instanceof PdfFile) {
            throw new InvalidArgumentException('Only PDF and text resources can be indexed.');
        }

        return (int) $this->pdfs->countPdfPagesInFilepath($this->files->getLocalFilePath($resource));
    }

    public function extractPage(Resource $resource, int $pageNumber): ExtractedPage
    {
        if ($pageNumber < 1) {
            throw new InvalidArgumentException('PDF page numbers are one-based.');
        }

        if ($resource instanceof Text) {
            if ($pageNumber !== 1) {
                throw new InvalidArgumentException('Text resources have exactly one page.');
            }

            return new ExtractedPage(1, (string) $resource->getContent(), 'native', 1.0, 'text-resource-v1');
        }

        if (! $resource instanceof PdfFile) {
            throw new InvalidArgumentException('Only PDF and text resources can be indexed.');
        }

        $path = $this->files->getLocalFilePath($resource);
        $nativeText = $this->pdfs->pdfToText($path, $pageNumber, $pageNumber);

        if (mb_strlen(trim($nativeText)) >= $this->nativeTextMinimumCharacters) {
            return new ExtractedPage($pageNumber, $nativeText, 'native', 1.0, 'pdftotext-v1');
        }

        $ocr = $this->ocr->extractPage($path, $pageNumber);
        $assessment = $this->qualityGate->assess($ocr->metrics);

        return new ExtractedPage($pageNumber, $ocr->text, 'ocr', $ocr->quality, $ocr->version, $assessment['accepted'], $ocr->metrics, $assessment['reasons']);
    }
}
