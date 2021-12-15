<?php

namespace App\Listeners;

use App\Events\ContainsOneResource;
use App\Models\PdfFile;
use App\Services\ResourceHandling\PdfHandlingService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculatePdfPageSize {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $processor;

	/**
	 *
	 * @param PdfHandlingService $processor
	 */
	public function __construct(PdfHandlingService $processor) {
		$this->processor = $processor;
	}

	/**
	 * Execute the job.
	 *
	 * @param ContainsOneResource $event
	 */
	public function handle(ContainsOneResource $event) {

		$resource = $event->getResource();

		if ($resource instanceof PdfFile) {

			Log::info("Start job: " . self::class . " for Resource", $resource->toArray());

			try {
				$resource = $this->processor->countPdfPages($resource);
			} catch (\Exception $e) {
				Log::error('Error when Counting PDF-Pages in Resource', [
						'exception' => $e->getMessage(),
						'trace'     => $e->getTraceAsString(),
						'resource'  => $resource->toArray()]
				);
			}

			Log::info("End job: " . self::class . " for Resource", $resource->toArray());

		}

	}
}
