<?php

namespace Tests\Feature;

use App\Models\Text;
use App\Models\User;
use App\Services\PreviewGeneration\ResourcePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

	private function textResource(): Text {
		$resource = new Text();
		$resource->created_by = User::factory()->create()->id;
		$resource->setContent('Preview queue test content');
		$resource->save();

		return $resource;
	}
}
