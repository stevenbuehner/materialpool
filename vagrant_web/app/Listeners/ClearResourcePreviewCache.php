<?php

namespace App\Listeners;

use App\Events\ContainsOneResource;
use App\Services\PreviewGeneration\ResourcePreviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClearResourcePreviewCache {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

		$this->resourcePreviewService->clearImageCache($resource);

		Log::info("Image cache cleared for resource ({$resource->id})");
	}
}
