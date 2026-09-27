<?php

namespace Tests\Feature;

use App\Services\ContextSearch\Extraction\TesseractOcrProcessor;
use Tests\TestCase;

final class ContextSearchTesseractOcrTest extends TestCase
{
    public function test_renders_a_normal_pdf_page_at_300_dpi_and_reads_text_with_tsv_metrics(): void
    {
        $processor = new TesseractOcrProcessor('eng', 60);

        $result = $processor->extractPage(base_path('tests/testFiles/PDF.pdf'), 1);

        $this->assertSame(300, $result->metrics['render_dpi']);
        $this->assertNotSame('', trim($result->text));
        $this->assertGreaterThan(0, $result->metrics['recognized_word_count']);
        $this->assertCount(101, $result->metrics['confidence_histogram']);
    }

    public function test_reduces_render_resolution_for_a_large_page_pixel_budget(): void
    {
        $processor = new TesseractOcrProcessor('eng', 60, maxImagePixels: 6_000_000);

        $result = $processor->extractPage(base_path('tests/testFiles/PDF.pdf'), 1);

        $this->assertLessThan(300, $result->metrics['render_dpi']);
        $this->assertGreaterThan(150, $result->metrics['render_dpi']);
        $this->assertNotSame('', trim($result->text));
    }
}
