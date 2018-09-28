<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Services\ResourceHandling\PdfHandlingService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculatePdfPageSize {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $resource;

	/**
	 *
	 * @param $resource Resource
	 */
	public function __construct(Resource $resource) {
		$this->resource = $resource;
	}

	/**
	 * Execute the job.
	 *
	 * @param $processor PdfHandlingService
	 */
	public function handle(PdfHandlingService $processor) {

		Log::info("Start job: " . self::class . " for Resource", $this->resource);

		$this->resource = $processor->countPdfPages($this->resource);

		Log::info("End job: " . self::class . " for Resource", $this->resource);
	}
}
