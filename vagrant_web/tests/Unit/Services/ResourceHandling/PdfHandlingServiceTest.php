<?php

namespace Tests\Unit\Services\ResourceHandling;

use App\Services\ResourceHandling\Exceptions\InvalidPageNoException;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\PdfHandlingService;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Tests\TestCase;

class PdfHandlingServiceTest extends TestCase
{
    public function test_extracts_requested_page_into_a_reopenable_pdf_with_preserved_geometry(): void
    {
        $service = new PdfHandlingService(new FileHandlingService());

        $pdf = $service->extractPdfPagesInFilepath(base_path('tests/testFiles/PDF.pdf'), [1]);
        $output = $pdf->Output('S');

        $this->assertInstanceOf(Fpdi::class, $pdf);
        $this->assertSame(1, $pdf->PageNo());
        $this->assertEqualsWithDelta(209.9, $pdf->GetPageWidth(), 0.1);
        $this->assertEqualsWithDelta(296.7, $pdf->GetPageHeight(), 0.1);
        $this->assertNotSame('', $output);

        $reopenedPdf = new Fpdi();

        $this->assertSame(1, $reopenedPdf->setSourceFile(StreamReader::createByString($output)));
    }

    public function test_extracts_all_pages_when_page_selection_is_omitted(): void
    {
        $service = new PdfHandlingService(new FileHandlingService());

        $pdf = $service->extractPdfPagesInFilepath(base_path('tests/testFiles/PDF.pdf'));
        $output = $pdf->Output('S');

        $this->assertSame(1, $pdf->PageNo());

        $reopenedPdf = new Fpdi();

        $this->assertSame(1, $reopenedPdf->setSourceFile(StreamReader::createByString($output)));
    }

    public function test_rejects_page_number_outside_the_source_document(): void
    {
        $service = new PdfHandlingService(new FileHandlingService());

        $this->expectException(InvalidPageNoException::class);

        $service->extractPdfPagesInFilepath(base_path('tests/testFiles/PDF.pdf'), [2]);
    }
}
