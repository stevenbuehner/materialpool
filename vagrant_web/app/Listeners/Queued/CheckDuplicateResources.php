<?php

namespace App\Listeners\Queued;

use App\Events\ContainsOneResource;
use App\Services\ResourceHandling\ResourceDuplicationHandlingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class CheckDuplicateResources implements ShouldQueue {
	use  SerializesModels;

	public $connection = 'database';
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
	 * @throws \App\Services\ResourceHandling\Exceptions\MissingResourceHashException
	 */
	public function handle(ContainsOneResource $event) {
		$this->service->mergeDuplicatesOfResource($event->getResource());
	}
}
