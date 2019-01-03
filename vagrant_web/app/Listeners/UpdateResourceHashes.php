<?php

namespace App\Listeners;

use App\Events\ContainsOneResource;
use App\Jobs\CheckDuplicateResources;
use App\Services\Processors\ResourceHashProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateResourceHashes {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $hashProcessor;

	/**
	 * Create a new job instance.
	 *
	 * @param ResourceHashProcessor $hashProcessor
	 */
	public function __construct(ResourceHashProcessor $hashProcessor) {
		$this->hashProcessor = $hashProcessor;
	}

	/**
	 * Execute the job.
	 *
	 * @param $processor ResourceHashProcessor
	 */
	public function handle(ContainsOneResource $event) {
		$resource    = $event->getResource();
		$hashChanged = $this->hashProcessor->updateResourceHash($resource);

		Log::info("Starting Job UpdateResourceHashes for resource ({$resource->id})");

		if ($hashChanged) {
			// CheckDuplicateResources::dispatch($resource)->onConnection($this->connection)->onQueue($this->queue);

			Log::info("Hash of resource ({$resource->id}) was updated to: " . $resource->content_hash);
		}
	}
}
