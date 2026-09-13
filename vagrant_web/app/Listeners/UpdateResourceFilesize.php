<?php

namespace App\Listeners;

use App\Events\ContainsOneResource;
use App\Services\Processors\ResourceFilesizeProcessor;

class UpdateResourceFilesize {
	public function __construct(protected ResourceFilesizeProcessor $processor) {
	}

	public function handle(ContainsOneResource $event): void {
		$this->processor->updateResourceFilesize($event->getResource());
	}
}
