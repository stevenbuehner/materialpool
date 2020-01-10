<?php

namespace App\Listeners;

use App\Events\ContainsOneResource;
use App\Models\Traits\PageCountTrait;
use App\Services\PreviewGeneration\ResourcePreviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Size;

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

		// Das funktioniert nicht, weil die Ressource bereits in der DB gelöscht ist und keine Information über ihre Seitenanzahl mehr vorliegt ...
		/*
		if (in_array(PageCountTrait::class, class_uses_recursive($resource))) {
			if ($resource->getPageCountAttribute() !== NULL && $resource->getPageCountAttribute() > 0 && $resource->getPageCountAttribute() < 2000) {

				$width  = config('app.resource.preview.maxWidth');
				$height = config('app.resource.preview.maxHeight');
				$size   = new Size($width, $height);

				// Alle Einzelseiten löschen
				for ($i = $resource->getPageCountAttribute(); $i > 0; $i--) {
					$this->resourcePreviewService->clearImageCache($resource, [$size, (int)$i]);
				}

			}
		}
		*/


		Log::info("Image cache cleared for resource ({$resource->id})");
	}
}
