<?php

namespace App\Listeners;

use App\Events\ContainsOneResource;
use App\Jobs\PlanResourcePreviews;

class QueueResourcePreviewGeneration {
	public function handle(ContainsOneResource $event): void {
		PlanResourcePreviews::dispatch($event->getResource()->getKey())->afterCommit();
	}
}
