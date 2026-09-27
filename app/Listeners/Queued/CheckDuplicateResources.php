<?php

namespace App\Listeners\Queued;

use App\Events\ContainsOneResource;
use App\Services\ResourceHandling\Exceptions\MissingResourceHashException;
use App\Services\ResourceHandling\ResourceDuplicationHandlingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class CheckDuplicateResources implements ShouldQueue, ShouldBeUnique {
	use  SerializesModels;

	public    $connection              = 'database';
	public    $afterCommit             = TRUE;
	public    $deleteWhenMissingModels = TRUE;
	protected $service;

	/**
	 * Create the event listener.
	 *
	 * @param ResourceDuplicationHandlingService $service
	 */
	public function __construct(ResourceDuplicationHandlingService $service) {
		$this->service = $service;
	}

	/**
	 * Execute the job.
	 *
	 * @param ContainsOneResource $event
	 * @throws MissingResourceHashException
	 */
	public function handle(ContainsOneResource $event) {
		$this->service->mergeDuplicatesOfResource($event->getResource());
	}

	public function uniqueId(ContainsOneResource $event): string {
		$resource = $event->getResource();

		return 'resource-duplicate-check:' . ($resource->content_hash ?: $resource->getKey());
	}
}
