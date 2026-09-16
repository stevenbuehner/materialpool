<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\PdfFile;
use App\ResourceLimitations\PageLimitation;
use Database\Seeders\ResourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigns_only_existing_pages_to_single_page_file_fixtures(): void
    {
        $this->seed(ResourceSeeder::class);

        $resources = DocumentFile::query()
            ->with('materials')
            ->get()
            ->merge(PdfFile::query()->with('materials')->get());

        $this->assertCount(10, $resources);

        $resources->each(function (DocumentFile|PdfFile $resource): void {
            $material = $resource->materials->sole();

            $this->assertInstanceOf(PageLimitation::class, $material->pivot->limitation);
            $this->assertSame([1], $material->pivot->limitation->getPages());
        });
    }
}
