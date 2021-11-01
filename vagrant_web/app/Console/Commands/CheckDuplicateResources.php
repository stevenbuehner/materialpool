<?php

namespace App\Console\Commands;

use App\Services\ResourceHandling\ResourceDuplicationHandlingService;
use Illuminate\Console\Command;

class CheckDuplicateResources extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'resources:check:duplicates';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Find Resources with the same sha1 hash and merge assigned materials';

	protected $service;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(ResourceDuplicationHandlingService $service) {
		parent::__construct();
		$this->service = $service;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle() {
		$this->service->mergeAllDuplicates();

		return 0;
	}
}
