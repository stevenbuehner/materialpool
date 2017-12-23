<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Services\Processors\ResourceHashProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateResourceHashes {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $resource;

	/**
	 * Create a new job instance.
	 *
	 * @param $resource Resource
	 */
	public function __construct(Resource $resource) {
		$this->resource = $resource;
	}

	/**
	 * Execute the job.
	 *
	 * @param $processor ResourceHashProcessor
	 */
	public function handle(ResourceHashProcessor $processor) {
		$processor->updateResourceHash($this->resource);
	}
}
