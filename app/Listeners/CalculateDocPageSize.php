<?php

namespace App\Listeners;

use App\Events\ContainsOneResource;
use App\Models\DocumentFile;
use App\Services\ResourceHandling\DocHandlingService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateDocPageSize {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $processor;

	/**
	 *
	 * @param DocHandlingService $processor
	 */
	public function __construct(DocHandlingService $processor) {
		$this->processor = $processor;
	}

	/**
	 * Execute the job.
	 *
	 * @param ContainsOneResource $event
	 */
	public function handle(ContainsOneResource $event) {

		$resource = $event->getResource();

		if ($resource instanceof DocumentFile) {

			Log::info("Start job: " . self::class . " for Resource", ['id' => $resource->id, 'pageCount' => $resource->page_count]);

			try {
				$resource = $this->processor->countDocPages($resource);
			} catch (Exception $e) {
				Log::error('Error when Counting Doc-Pages in Resource', [
					'exception' => $e->getMessage(),
					'trace'     => $e->getTraceAsString(),
					'resource'  => ['id' => $resource->id, 'pageCount' => $resource->page_count]
				]);
			}

			Log::info("End job: " . self::class . " for Resource", ['id' => $resource->id, 'pageCount' => $resource->page_count]);

		}

	}
}
