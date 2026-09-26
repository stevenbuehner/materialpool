<?php

namespace Tests\Unit\Services\ContextSearch\Extraction;

use App\Models\Text;
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
}
