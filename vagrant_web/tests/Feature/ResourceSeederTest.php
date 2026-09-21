<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\PdfFile;
use App\ResourceLimitations\PageLimitation;
use Database\Seeders\ResourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_page_limited_document_and_pdf_fixtures_for_duplicate_checks(): void
    {
        $this->seed(ResourceSeeder::class);

        $resources = DocumentFile::query()
            ->with('materials')
            ->get()
            ->merge(PdfFile::query()->with('materials')->get());

        $this->assertCount(9, $resources);

        $resources->each(function (DocumentFile|PdfFile $resource): void {
            $material = $resource->materials->sole();

            $this->assertInstanceOf(PageLimitation::class, $material->pivot->limitation);
            $this->assertSame([1], $material->pivot->limitation->getPages());
        });

        $documents = DocumentFile::query()->get();
        $pdfs = PdfFile::query()->get();
        $this->assertCount(2, $documents->filter(fn (DocumentFile $resource): bool => $resource->original_filename === 'Document1.docx'));
        $this->assertCount(2, $pdfs->filter(fn (PdfFile $resource): bool => $resource->original_filename === 'PDF1.pdf'));
        $this->assertCount(1, $documents->filter(fn (DocumentFile $resource): bool => $resource->original_filename === 'Document2.docx'));
        $this->assertCount(1, $documents->filter(fn (DocumentFile $resource): bool => $resource->original_filename === 'Document3.docx'));
        $this->assertCount(1, $pdfs->filter(fn (PdfFile $resource): bool => $resource->original_filename === 'PDF2.pdf'));
        $this->assertCount(1, $pdfs->filter(fn (PdfFile $resource): bool => $resource->original_filename === 'PDF3.pdf'));

        $missingResource = $documents->sole(fn (DocumentFile $resource): bool =>
            $resource->original_filename === 'Nicht-vorhandenes-Testdokument.docx');

        $this->assertTrue($missingResource->hasLocalFile());
        $this->assertFalse(Storage::disk(config('app.disks.resources'))->exists($missingResource->getLocalFilePath()));
        $this->assertNull($missingResource->content_hash);
    }
}
