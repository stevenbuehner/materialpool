<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Services\ResourceHandling\ResourceDuplicationHandlingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckDuplicateResources implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/** @var \App\Models\Resource $resourceToCheck */
	protected $resourceToCheck;

	/**
	 * Create a new job instance.
	 *
	 * @param $resourceToCheck Resource
	 *
	 */
	public function __construct(Resource $resourceToCheck) {
		$this->resourceToCheck = $resourceToCheck;
	}

	/**
	 * Execute the job.
	 *
	 */
	public function handle(ResourceDuplicationHandlingService $service) {
		$service->mergeDuplicatesOfResource($this->resourceToCheck);
	}
}
