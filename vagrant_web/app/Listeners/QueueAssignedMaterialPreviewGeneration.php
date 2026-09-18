<?php

namespace App\Listeners;

use App\Events\ContainsOneResource;
use App\Jobs\GenerateMaterialPreview;

class QueueAssignedMaterialPreviewGeneration {
	public function handle(ContainsOneResource $event): void {
		$resource = $event->getResource();

		$resource->materials()
			->select('materials.id')
			->each(function ($material) use ($resource): void {
				GenerateMaterialPreview::dispatch($material->getKey(), $resource->getKey())->afterCommit();
			});
	}
}
