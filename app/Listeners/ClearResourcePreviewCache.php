<?php

namespace App\Listeners;

use App\Events\ContainsOneResource;
use App\Services\PreviewGeneration\ResourcePreviewService;
use Illuminate\Support\Facades\Log;

class ClearResourcePreviewCache {
	protected $resourcePreviewService;

	/**
	 * Create a new job instance.
	 *
	 * @param ResourcePreviewService $resourcePreviewService
	 */
	public function __construct(ResourcePreviewService $resourcePreviewService) {
		$this->resourcePreviewService = $resourcePreviewService;
	}

	public function handle(ContainsOneResource $event) {
		$resource = $event->getResource();

		$this->resourcePreviewService->clearAllImageCaches($resource);


		Log::info("Image cache cleared for resource ({$resource->id})");
	}
}
