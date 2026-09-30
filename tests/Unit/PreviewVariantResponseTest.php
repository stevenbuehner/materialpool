<?php

namespace Tests\Unit;

use App\Http\Controllers\MaterialPreviewController;
use App\Http\Controllers\ResourcePreviewController;
use App\Http\Middleware\CacheControlHeaders;
use App\Models\Material;
use App\Models\Text;
use App\Services\PreviewGeneration\MaterialPreviewService;
use App\Services\PreviewGeneration\AbstractPreviewService;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\PreviewSize;
use App\Services\PreviewGeneration\ResourcePreviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\ImageManager;
use Mockery;
use Tests\TestCase;

class PreviewVariantResponseTest extends TestCase {
	public function test_preview_sizes_use_the_configured_small_and_large_dimensions(): void {
		$this->assertSame(config('app.preview.small.maxWidth'), PreviewSize::small()->getWidth());
		$this->assertSame(config('app.preview.small.maxHeight'), PreviewSize::small()->getHeight());
		$this->assertSame(config('app.preview.large.maxWidth'), PreviewSize::large()->getWidth());
		$this->assertSame(config('app.preview.large.maxHeight'), PreviewSize::large()->getHeight());
		$this->assertSame(config('app.preview.large.maxWidth'), PreviewSize::constrained(9999, 9999)->getWidth());
		$this->assertSame(config('app.preview.small'), PreviewSize::profile(PreviewSize::small()));
		$this->assertSame(config('app.preview.large'), PreviewSize::profile(PreviewSize::large()));
	}

	public function test_preview_profiles_are_selected_for_requested_dimensions(): void {
		config()->set('app.preview.small.maxWidth', 480);
		config()->set('app.preview.small.maxHeight', 320);
		config()->set('app.preview.small.outputFormat', 'png');
		config()->set('app.preview.large.maxWidth', 1800);
		config()->set('app.preview.large.maxHeight', 1200);

		$this->assertSame(480, PreviewSize::small()->getWidth());
		$this->assertSame(1200, PreviewSize::large()->getHeight());
		$this->assertSame('png', PreviewSize::profile(PreviewSize::constrained(400, 300))['outputFormat']);
		$this->assertSame('jpg', PreviewSize::profile(PreviewSize::constrained(500, 300))['outputFormat']);
	}

	public function test_preview_encoding_uses_each_profiles_output_format(): void {
		config()->set('app.preview.small.outputFormat', 'png');
		config()->set('app.preview.small.quality', 75);
		config()->set('app.preview.large.quality', 90);
		$image = Mockery::mock(\Intervention\Image\Image::class);
		$image->shouldReceive('encode')->once()->with('png', 75)->andReturn('small-bytes');
		$image->shouldReceive('encode')->once()->with('jpg', 90)->andReturn('large-bytes');
		$manager = Mockery::mock(ImageManager::class);
		$manager->shouldReceive('canvas')->twice()->andReturn($image);
		$service = new class($manager) extends AbstractPreviewService {
			public function encodeForTest(\Intervention\Image\Size $size, string $key): string {
				$this->putImageObjectToCache($this->imageManager->canvas(2, 2, '#fff'), $key, $size);

				return $this->getImageDataFromCache($key);
			}
		};

		$this->assertSame('small-bytes', $service->encodeForTest(PreviewSize::small(), 'preview-encoding-small'));
		$this->assertSame('large-bytes', $service->encodeForTest(PreviewSize::large(), 'preview-encoding-large'));
	}

	public function test_resource_preview_response_uses_cached_bytes_and_honours_an_etag(): void {
		$imageData = 'small-preview-bytes';
		$service = Mockery::mock(ResourcePreviewService::class);
		$service->shouldReceive('getCachedImageData')->twice()->andReturn($imageData);
		$controller = new ResourcePreviewController($service);
		$resource = new Text();

		$response = $controller->getImage(Request::create('/resource/1/image/640/640'), $resource, 640, 640);
		$this->assertSame($imageData, $response->getContent());
		$this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
		$this->assertSame('"' . hash('sha256', $imageData) . '"', $response->getEtag());

		$notModified = $controller->getImage(
			Request::create('/resource/1/image/640/640', 'GET', [], [], [], ['HTTP_IF_NONE_MATCH' => $response->getEtag()]),
			$resource,
			640,
			640
		);
		$this->assertSame(304, $notModified->getStatusCode());
	}

	public function test_resource_and_material_responses_use_the_selected_output_format(): void {
		config()->set('app.preview.small.outputFormat', 'png');
		$resourceService = Mockery::mock(ResourcePreviewService::class);
		$resourceService->shouldReceive('getCachedImageData')->andReturn('png-preview');
		$resourceController = new ResourcePreviewController($resourceService);
		$small = $resourceController->getImage(Request::create('/resource/1/image/640/640'), new Text(), 640, 640);
		$largeWidth = config('app.preview.large.maxWidth');
		$largeHeight = config('app.preview.large.maxHeight');
		$large = $resourceController->getImage(Request::create("/resource/1/image/{$largeWidth}/{$largeHeight}"), new Text(), $largeWidth, $largeHeight);

		$this->assertSame('image/png', $small->headers->get('Content-Type'));
		$this->assertSame('image/jpeg', $large->headers->get('Content-Type'));

		$materialService = Mockery::mock(MaterialPreviewService::class);
		$materialService->shouldReceive('getCachedMaterialPreviewData')->andReturn('png-preview');
		$materialController = new MaterialPreviewController($materialService);
		$materialResponse = $materialController->getMaterialPreview(Request::create('/material/1/preview?width=640&height=640'), new Material());
		$this->assertSame('image/png', $materialResponse->headers->get('Content-Type'));
	}

	public function test_resource_preview_response_has_no_content_when_no_preview_can_be_generated(): void {
		$service = Mockery::mock(ResourcePreviewService::class);
		$service->shouldReceive('getCachedImageData')
			->once()
			->andThrow(new NotPreviewAbleException());
		$controller = new ResourcePreviewController($service);

		$response = $controller->getImage(Request::create('/resource/1/image/640/640'), new Text(), 640, 640);

		$this->assertSame(204, $response->getStatusCode());
		$this->assertSame('', $response->getContent());
	}

	public function test_resource_preview_refreshes_the_requested_cached_variant(): void {
		$service = Mockery::mock(ResourcePreviewService::class);
		$service->shouldReceive('getCachedImageData')
			->once()
			->with(
				Mockery::type(Text::class),
				Mockery::on(fn ($size): bool => $size->getWidth() === 640 && $size->getHeight() === 640),
				NULL,
				TRUE
			)
			->andReturn('refreshed-preview-bytes');
		$controller = new ResourcePreviewController($service);

		$response = $controller->getImage(Request::create('/resource/1/image/640/640?refresh=1'), new Text(), 640, 640);

		$this->assertSame('refreshed-preview-bytes', $response->getContent());
	}

	public function test_material_preview_response_uses_the_requested_small_variant(): void {
		$service = Mockery::mock(MaterialPreviewService::class);
		$service->shouldReceive('getCachedMaterialPreviewData')
			->once()
			->with(Mockery::type(Material::class), Mockery::on(fn ($size): bool => $size->getWidth() === 640 && $size->getHeight() === 640))
			->andReturn('material-preview-bytes');
		$controller = new MaterialPreviewController($service);

		$response = $controller->getMaterialPreview(Request::create('/material/1/preview?width=640&height=640'), new Material());

		$this->assertSame('material-preview-bytes', $response->getContent());
		$this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
	}

	public function test_preview_responses_are_private_for_seven_days(): void {
		$response = (new CacheControlHeaders())->handle(Request::create('/resource/1/image/640/640'), fn () => response('preview'));

		$this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
		$this->assertStringContainsString('max-age=604800', $response->headers->get('Cache-Control'));
	}

	public function test_material_preview_cache_invalidation_removes_registered_variants(): void {
		$material = new Material();
		$material->setAttribute('id', 42);
		$cache = Cache::store('previewimages');
		$cache->forever('material-preview-small', 'small');
		$cache->forever('material-preview-large', 'large');
		$cache->forever('material-preview-index:42', ['material-preview-small', 'material-preview-large']);

		resolve(MaterialPreviewService::class)->clearImageCache($material);

		$this->assertFalse($cache->has('material-preview-small'));
		$this->assertFalse($cache->has('material-preview-large'));
		$this->assertFalse($cache->has('material-preview-index:42'));
	}
}
