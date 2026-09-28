<?php

namespace Tests\Feature;

use App\Services\ContextSearch\Extraction\TesseractOcrProcessor;
use Tests\TestCase;

final class ContextSearchTesseractOcrTest extends TestCase
{
    public function test_ignores_text_outside_the_pdf_crop_box(): void
    {
        // Eine Zeile liegt nur in der MediaBox. So prüft die synthetische PDF,
        // dass Satzvermerke außerhalb der sichtbaren CropBox nicht in der OCR landen.
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 18);
        $pdf->Text(20, 20, 'HIDDEN HEADER');
        $pdf->Text(20, 220, 'VISIBLE CONTENT');
        $source = $pdf->Output('S');
        $source = preg_replace('/\/MediaBox\s*\[[^\]]+\]/', '$0 /CropBox [0 0 595.28 400]', $source, 1);
        $path = tempnam(sys_get_temp_dir(), 'ocr-cropbox-');
        file_put_contents($path, $source);

        try {
            $result = (new TesseractOcrProcessor('eng', 60))->extractPage($path, 1);

            $this->assertStringContainsString('VISIBLE CONTENT', $result->text);
            $this->assertStringNotContainsString('HIDDEN HEADER', $result->text);
            $this->assertSame(300, $result->metrics['render_dpi']);
        } finally {
            unlink($path);
        }
    }

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
