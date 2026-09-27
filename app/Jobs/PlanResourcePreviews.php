<?php

namespace App\Jobs;

use App\Models\DocumentFile;
use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Services\PreviewGeneration\ResourcePreviewService;
use App\Services\PreviewGeneration\PreviewSize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PlanResourcePreviews implements ShouldQueue, ShouldBeUniqueUntilProcessing {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 60;
	public $tries = 3;
	public $backoff = [30, 120];

	public function __construct(protected int $resourceId) {
		$this->onConnection('database');
		$this->onQueue('resource-previews-low');
	}

	public function uniqueId(): string {
		return (string)$this->resourceId;
	}

	public function handle(ResourcePreviewService $previewService): void {
		$resource = (new Resource())->newQueryWithoutScopes()->find($this->resourceId);
		if ($resource === NULL) {
			return;
		}

		$this->queueAssignedMaterialPreviews($resource);

		$size = PreviewSize::small();

		if (!$previewService->hasPreview($resource)) {
			return;
		}

		if ($resource instanceof PdfFile || $resource instanceof DocumentFile) {
			$pageCount = (int)($resource->page_count ?? 0);
			if ($pageCount > 0) {
				for ($page = 1; $page <= $pageCount; $page++) {
					if (!$previewService->hasCachedImage($resource, $size, $page)) {
						GenerateResourcePreviewVariant::dispatch($resource->getKey(), $page)->afterCommit();
					}
				}

			}
		} elseif (!$previewService->hasCachedImage($resource, $size)) {
			GenerateResourcePreviewVariant::dispatch($resource->getKey())->afterCommit();
		}
	}

	private function queueAssignedMaterialPreviews(Resource $resource): void {
		$resource->materials()->select('materials.id')->each(function (Material $material) use ($resource): void {
			GenerateMaterialPreview::dispatch($material->getKey(), $resource->getKey())->afterCommit();
		});
	}
}
