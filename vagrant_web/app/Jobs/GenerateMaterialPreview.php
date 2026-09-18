<?php

namespace App\Jobs;

use App\Models\Material;
use App\Services\PreviewGeneration\MaterialPreviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class GenerateMaterialPreview implements ShouldQueue, ShouldBeUnique {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 110;
	public $tries = 3;
	public $backoff = [30, 120];

	public function __construct(protected int $materialId, protected int $resourceId) {
		$this->onConnection('database');
		$this->onQueue('resource-previews-low');
	}

	public function uniqueId(): string {
		return (string)$this->materialId;
	}

	public function middleware(): array {
		return [(new WithoutOverlapping('material-preview:' . $this->materialId))
			->shared()
			->releaseAfter(10)
			->expireAfter(140)];
	}

	public function handle(MaterialPreviewService $previewService): void {
		$material = (new Material())->newQueryWithoutScopes()
			->with('resources')
			->find($this->materialId);

		if ($material === NULL) {
			return;
		}

		$previewResource = $previewService->getPreviewResource($material);
		if ($previewResource?->getKey() !== $this->resourceId) {
			return;
		}

		$previewService->getCachedMaterialPreview($material);
	}
}
