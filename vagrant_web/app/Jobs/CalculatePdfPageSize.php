<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Services\ResourceHandling\PdfPageCounterService;
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
	 * @param $processor PdfPageCounterService
	 */
	public function handle(PdfPageCounterService $processor) {
		$this->resource = $processor->countPdfPages($this->resource);
	}
}
