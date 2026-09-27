<?php

namespace App\Listeners\Queued;

use App\Events\ContainsOneResource;
use App\Services\MaterialHandling\MaterialDuplicationHandlingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class CheckDuplicateMaterials implements ShouldQueue {
	use SerializesModels;

	public    $connection = 'database';
	protected $service;

	/**
	 * Create the event listener.
	 *
	 * @param MaterialDuplicationHandlingService $service
	 */
	public function __construct(MaterialDuplicationHandlingService $service) {
		$this->service = $service;
	}

	/**
	 * Handle the event.
	 *
	 * @param ContainsOneResource $event
	 * @return void
	 * @throws \Exception
	 */
	public function handle(ContainsOneResource $event) {
		$this->service->mergeMaterialDublicates($event->getResource());
	}
}
