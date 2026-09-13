<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Services\Processors\ResourceFilesizeProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateResourceFilesizesBatch implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public function __construct(protected array $resourceIds, protected bool $force = FALSE) {
	}

	public function handle(ResourceFilesizeProcessor $processor): void {
		$query = (new Resource())->newQueryWithoutScopes()
			->whereKey($this->resourceIds)
			->orderBy('id');

		if (!$this->force) {
			$query->whereNull('filesize');
		}

		$query->each(function (Resource $resource) use ($processor): void {
			$processor->updateResourceFilesize($resource);
		});
	}
}
