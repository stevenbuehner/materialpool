<?php

namespace Tests\Unit\Services\ContextSearch\Extraction;

use App\Models\Text;
use App\Models\PdfFile;
use App\Services\ContextSearch\Extraction\OcrProcessor;
use App\Services\ContextSearch\Extraction\OcrQualityGate;
use App\Services\ContextSearch\Extraction\OcrResult;
use App\Services\ContextSearch\Extraction\ResourceTextExtractor;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\PdfHandlingService;
use Tests\TestCase;

final class ResourceTextExtractorTest extends TestCase
{
    public function test_treats_a_text_resource_as_a_single_native_page(): void
    {
        $text = new Text();
        $text->setContent('Ein nachvollziehbarer Text mit einer genauen Quelle.');
        $extractor = new ResourceTextExtractor(
            new PdfHandlingService(new FileHandlingService()),
            new FileHandlingService(),
            new class implements OcrProcessor {
                public function extractPage(string $pdfPath, int $pageNumber): OcrResult
                {
                    throw new \RuntimeException('OCR must not run for a text resource.');
                }
            },
            80,
            new OcrQualityGate(0, 1, 0, 1),
        );

        $pages = $extractor->extract($text);

        $this->assertCount(1, $pages);
        $this->assertSame(1, $pages[0]->pageNumber);
        $this->assertSame('native', $pages[0]->method);
        $this->assertSame('Ein nachvollziehbarer Text mit einer genauen Quelle.', $pages[0]->text);
    }

    public function test_extracts_only_the_requested_pdf_page_with_one_based_citation(): void
    {
        $pdf = new PdfFile();
        $pdf->setAttribute('local_path', 'testfiles::PDF1.pdf');
        $extractor = new ResourceTextExtractor(
            new PdfHandlingService(new FileHandlingService()),
            new FileHandlingService(),
            new class implements OcrProcessor {
                public function extractPage(string $pdfPath, int $pageNumber): OcrResult
                {
                    throw new \RuntimeException('Native PDF text should avoid OCR.');
                }
            },
            1,
            new OcrQualityGate(0, 1, 0, 1),
        );

        $page = $extractor->extractPage($pdf, 1);

        $this->assertSame(1, $extractor->pageCount($pdf));
        $this->assertSame(1, $page->pageNumber);
        $this->assertSame('native', $page->method);
        $this->assertNotSame('', trim($page->text));
    }
}
