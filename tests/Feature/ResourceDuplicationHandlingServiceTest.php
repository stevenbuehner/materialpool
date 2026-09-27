<?php

namespace Tests\Feature;

use App\Events\ResourceWasCreated;
use App\Listeners\Queued\CheckDuplicateResources;
use App\Models\DocumentFile;
use App\Models\ForeignResourceId;
use App\Models\Material;
use App\Models\User;
use App\ResourceLimitations\PageLimitation;
use App\Services\ResourceHandling\ResourceDuplicationHandlingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceDuplicationHandlingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_merges_identical_files_into_the_lowest_id_resource_and_clears_affected_caches(): void
    {
        $user = User::factory()->create();
        $master = DocumentFile::factory()->fromTestFile('Document1.docx')->create(['created_by' => $user->id]);
        $slave = DocumentFile::factory()->fromTestFile('Document1.docx')->create(['created_by' => $user->id]);
        $slavePath = $slave->getLocalFilePath();

        $masterMaterial = $this->material($user, 'Master material');
        $slaveMaterial = $this->material($user, 'Slave material');
        $sharedMaterial = $this->material($user, 'Shared material');
        $master->materials()->attach($masterMaterial, ['limitation' => $this->pages([1])]);
        $master->materials()->attach($sharedMaterial, ['limitation' => $this->pages([1])]);
        $slave->materials()->attach($slaveMaterial, ['limitation' => $this->pages([1])]);
        $slave->materials()->attach($sharedMaterial, ['limitation' => $this->pages([2])]);
        $foreignId = ForeignResourceId::factory()->create([
            'resource_id' => $slave->id,
            'user_id' => $user->id,
        ]);
        $this->cacheMaterialPreview($slaveMaterial);
        $this->cacheMaterialPreview($sharedMaterial);
        $this->cacheResourcePreview($slave);

        resolve(ResourceDuplicationHandlingService::class)->mergeDuplicatesOfResource($slave);

        $this->assertModelExists($master);
        $this->assertModelMissing($slave);
        $this->assertSame($master->id, DocumentFile::query()->where('content_hash', $master->content_hash)->sole()->id);
        $this->assertSame($master->id, $foreignId->fresh()->resource_id);
        $this->assertFalse(Storage::disk(config('app.disks.resources'))->exists($slavePath));

        $materials = $master->fresh()->materials()->get()->keyBy('id');
        $this->assertCount(3, $materials);
        $this->assertSame([1], $materials->get($slaveMaterial->id)->pivot->limitation->getPages());
        $this->assertSame([1], $materials->get($sharedMaterial->id)->pivot->limitation->getPages());

        $cache = Cache::store('previewimages');
        $this->assertFalse($cache->has('material-preview-' . $slaveMaterial->id));
        $this->assertFalse($cache->has('material-preview-' . $sharedMaterial->id));
        $this->assertFalse($cache->has('resource-preview-' . $slave->id));
    }

    public function test_keeps_resources_with_different_file_contents_separate(): void
    {
        $user = User::factory()->create();
        $first = DocumentFile::factory()->fromTestFile('Document1.docx')->create(['created_by' => $user->id]);
        $second = DocumentFile::factory()->fromTestFile('Document2.docx')->create(['created_by' => $user->id]);
        $firstMaterial = $this->material($user, 'First material');
        $secondMaterial = $this->material($user, 'Second material');
        $first->materials()->attach($firstMaterial);
        $second->materials()->attach($secondMaterial);

        resolve(ResourceDuplicationHandlingService::class)->mergeDuplicatesOfResource($first);

        $this->assertModelExists($first);
        $this->assertModelExists($second);
        $this->assertSame($first->id, $firstMaterial->fresh()->resources->sole()->id);
        $this->assertSame($second->id, $secondMaterial->fresh()->resources->sole()->id);
    }

    public function test_skips_missing_local_files_without_failing_the_duplicate_queue_listener(): void
    {
        $user = User::factory()->create();
        $resource = DocumentFile::factory()
            ->withMissingLocalFile('Nicht-vorhandenes-Dokument.docx')
            ->create(['created_by' => $user->id]);
        $listener = resolve(CheckDuplicateResources::class);

        $listener->handle(new ResourceWasCreated($resource));

        $this->assertModelExists($resource);
        $this->assertNull($resource->fresh()->content_hash);
        $this->assertTrue($resource->hasLocalFile());
        $this->assertTrue($listener->deleteWhenMissingModels);
        $this->assertTrue($listener->afterCommit);
        $this->assertSame('resource-duplicate-check:' . $resource->id, $listener->uniqueId(new ResourceWasCreated($resource)));
    }

    private function material(User $user, string $title): Material
    {
        return Material::factory()->create([
            'title' => $title,
            'created_by' => $user->id,
            'modified_by' => $user->id,
        ]);
    }

    private function pages(array $pages): PageLimitation
    {
        $limitation = new PageLimitation();
        $limitation->setPages($pages);

        return $limitation;
    }

    private function cacheMaterialPreview(Material $material): void
    {
        $cache = Cache::store('previewimages');
        $cacheKey = 'material-preview-' . $material->id;
        $cache->forever($cacheKey, 'cached preview');
        $cache->forever('material-preview-index:' . $material->id, [$cacheKey]);
    }

    private function cacheResourcePreview(DocumentFile $resource): void
    {
        $cache = Cache::store('previewimages');
        $cacheKey = 'resource-preview-' . $resource->id;
        $cache->forever($cacheKey, 'cached preview');
        $cache->forever('resource-preview-index:' . $resource->id, [$cacheKey]);
    }
}
