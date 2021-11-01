<?php

namespace App\Listeners;

use App\Events\ContainsOneMaterial;
use App\Services\PreviewGeneration\MaterialPreviewService;
use Illuminate\Support\Facades\Log;

class ClearMaterialPreviewCache {

	protected $materialPreviewService;

	/**
	 * ClearMaterialPreviewCache constructor.
	 * @param MaterialPreviewService $materialPreviewService
	 */
	public function __construct(MaterialPreviewService $materialPreviewService) {
		$this->materialPreviewService = $materialPreviewService;
	}

	public function handle(ContainsOneMaterial $event) {
		$material = $event->getMaterial();

		$this->materialPreviewService->clearImageCache($material);

		Log::info("Image cache cleared for material ({$material->id})");
	}
}
