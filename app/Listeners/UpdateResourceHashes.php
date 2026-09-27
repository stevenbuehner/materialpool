<?php

namespace App\Listeners;

use App\Events\ContainsOneResource;
use App\Services\Processors\Exceptions\ResourceNotHashable;
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

	public function handle(ContainsOneResource $event) {
		$resource    = $event->getResource();
		$hashChanged = FALSE;

		try {
			$hashChanged = $this->hashProcessor->updateResourceHash($resource);
		} catch (ResourceNotHashable $e) {
			Log::error('Error when updating Resource-Hash', ['resource_id' => $resource->id]);
		}

		if ($hashChanged) {
			// CheckDuplicateResources::dispatch($resource)->onConnection($this->connection)->onQueue($this->queue);

			Log::info("Hash of resource ({$resource->id}) was updated to: " . $resource->content_hash);
		}
	}
}
