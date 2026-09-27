<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Services\PreviewGeneration\PreviewSize;
use App\Services\PreviewGeneration\ResourcePreviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class GenerateResourcePreviewVariant implements ShouldQueue, ShouldBeUnique {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 110;
	public $tries   = 3;
	public $backoff = [30, 120];

	public function __construct(protected int $resourceId, protected ?int $page = NULL) {
		$this->onConnection('database');
		$this->onQueue('resource-previews-low');
	}

	public function uniqueId(): string {
		return $this->resourceId . ':' . ($this->page ?? 'cover');
	}

	public function middleware(): array {
		return [(new WithoutOverlapping('resource-preview:' . $this->resourceId))
			        ->shared()
			        ->releaseAfter(10)
			        ->expireAfter(140)];
	}

	public function handle(ResourcePreviewService $previewService): void {
		$resource = (new Resource())->newQueryWithoutScopes()->find($this->resourceId);
		if ($resource === NULL) {
			return;
		}

		$size = PreviewSize::small();

		$previewService->getCachedImageData($resource, $size, $this->page);
	}
}
