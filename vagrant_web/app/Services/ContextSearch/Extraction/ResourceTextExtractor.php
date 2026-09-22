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
    ) {
    }

    /** @return array<int, ExtractedPage> */
    public function extract(Resource $resource): array
    {
        if ($resource instanceof Text) {
            return [new ExtractedPage(1, (string) $resource->getContent(), 'native', 1.0, 'text-resource-v1')];
        }

        if (! $resource instanceof PdfFile) {
            throw new InvalidArgumentException('Only PDF and text resources can be indexed in context-search stage 1.');
        }

        $path = $this->files->getLocalFilePath($resource);
        $pageCount = $this->pdfs->countPdfPagesInFilepath($path);
        $pages = [];

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $nativeText = $this->pdfs->pdfToText($path, $pageNumber, $pageNumber);

            if (mb_strlen(trim($nativeText)) >= $this->nativeTextMinimumCharacters) {
                $pages[] = new ExtractedPage($pageNumber, $nativeText, 'native', 1.0, 'pdftotext-v1');
                continue;
            }

            $ocr = $this->ocr->extractPage($path, $pageNumber);
            $pages[] = new ExtractedPage($pageNumber, $ocr->text, 'ocr', $ocr->quality, $ocr->version);
        }

        return $pages;
    }
}
