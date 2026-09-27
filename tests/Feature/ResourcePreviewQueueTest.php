<?php

namespace Tests\Feature;

use App\Events\ResourceWasChanged;
use App\Jobs\GenerateMaterialPreview;
use App\Jobs\GenerateResourcePreviewVariant;
use App\Jobs\PlanResourcePreviews;
use App\Listeners\QueueAssignedMaterialPreviewGeneration;
use App\Models\Material;
use App\Models\Text;
use App\Models\User;
use App\Services\PreviewGeneration\MaterialPreviewService;
use App\Services\PreviewGeneration\ResourcePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ResourcePreviewQueueTest extends TestCase {
	use RefreshDatabase;

	public function test_resource_cache_invalidation_removes_all_registered_preview_variants(): void {
		$resource = $this->textResource();
		$cache = Cache::store('previewimages');
		$indexKey = 'resource-preview-index:' . $resource->id;
		$cache->forever('resource-preview-a', 'a');
		$cache->forever('resource-preview-b', 'b');
		$cache->forever($indexKey, ['resource-preview-a', 'resource-preview-b']);

		resolve(ResourcePreviewService::class)->clearAllImageCaches($resource);

		$this->assertFalse($cache->has('resource-preview-a'));
		$this->assertFalse($cache->has('resource-preview-b'));
		$this->assertFalse($cache->has($indexKey));
	}

	public function test_resource_change_queues_material_preview_for_an_assigned_material(): void {
		[$material, $resource] = $this->materialWithResource();
		Queue::fake([GenerateMaterialPreview::class]);

		resolve(QueueAssignedMaterialPreviewGeneration::class)->handle(new ResourceWasChanged($resource));

		Queue::assertPushed(GenerateMaterialPreview::class, function (GenerateMaterialPreview $job) use ($material): bool {
			return $job->uniqueId() === (string)$material->getKey();
		});
	}

	public function test_material_preview_is_generated_when_changed_resource_is_used_for_preview(): void {
		[$material, $resource] = $this->materialWithResource();
		$previewService = $this->mock(MaterialPreviewService::class);
		$previewService->shouldReceive('getPreviewResource')
			->once()
			->andReturn($resource);
		$previewService->shouldReceive('getCachedMaterialPreviewData')
			->once()
			->with(
				\Mockery::on(fn (Material $loadedMaterial): bool => $loadedMaterial->getKey() === $material->getKey()),
				\Mockery::on(fn ($size): bool => $size->getWidth() === 640 && $size->getHeight() === 640)
			);

		(new GenerateMaterialPreview($material->getKey(), $resource->getKey()))->handle($previewService);
	}

	public function test_resource_preview_planning_queues_only_the_small_variant(): void {
		$resource = $this->textResource();
		Queue::fake([GenerateResourcePreviewVariant::class]);
		$previewService = $this->mock(ResourcePreviewService::class);
		$previewService->shouldReceive('hasPreview')->once()->with(\Mockery::type(Text::class))->andReturnTrue();
		$previewService->shouldReceive('hasCachedImage')
			->once()
			->with(
				\Mockery::type(Text::class),
				\Mockery::on(fn ($size): bool => $size->getWidth() === 640 && $size->getHeight() === 640)
			)
			->andReturnFalse();

		(new PlanResourcePreviews($resource->getKey()))->handle($previewService);

		Queue::assertPushed(GenerateResourcePreviewVariant::class, function (GenerateResourcePreviewVariant $job) use ($resource): bool {
			return $job->uniqueId() === $resource->getKey() . ':cover';
		});
	}

	private function textResource(): Text {
		$resource = new Text();
		$resource->created_by = User::factory()->create()->id;
		$resource->setContent('Preview queue test content');
		$resource->save();

		return $resource;
	}

	/**
	 * @return array{Material, Text}
	 */
	private function materialWithResource(): array {
		$user = User::factory()->create();
		$resource = new Text();
		$resource->created_by = $user->getKey();
		$resource->setContent('Material preview queue test content');
		$resource->save();

		$material = new Material();
		$material->title = 'Material preview queue test';
		$material->created_by = $user->getKey();
		$material->modified_by = $user->getKey();
		$material->save();
		$material->resources()->attach($resource->getKey());

		return [$material, $resource];
	}
}
