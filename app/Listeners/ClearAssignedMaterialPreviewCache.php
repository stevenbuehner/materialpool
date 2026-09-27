<?php

namespace App\Listeners;

use App\Events\ContainsOneResource;
use App\Services\PreviewGeneration\MaterialPreviewService;
use Illuminate\Support\Facades\Log;

class ClearAssignedMaterialPreviewCache {

	protected $materialPreviewService;

	/**
	 * ClearMaterialPreviewCache constructor.
	 * @param MaterialPreviewService $materialPreviewService
	 */
	public function __construct(MaterialPreviewService $materialPreviewService) {
		$this->materialPreviewService = $materialPreviewService;
	}

	public function handle(ContainsOneResource $event) {
		$materials = $event->getResource()->materials;

		foreach ($materials as $m) {
			$this->materialPreviewService->clearImageCache($m);
			Log::info("Image cache cleared for material ({$m->id})");
		}

	}
}
