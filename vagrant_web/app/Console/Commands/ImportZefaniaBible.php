<?php

namespace App\Console\Commands;

use App\Services\Bibles\Exceptions\FileDoesNotExistException;
use App\Services\Bibles\Import\ZefaniaImportService;
use Illuminate\Console\Command;

class ImportZefaniaBible extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'import:zefaniabible {xmlfile : Absolute Path to XML-File of ZefaniaBible to import}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import Zefania Bible';


	protected $zefaniaImportService;

	/**
	 * Create a new command instance.
	 *
	 * @param ZefaniaImportService $zefaniaImportService
	 */
	public function __construct(ZefaniaImportService $zefaniaImportService) {
		parent::__construct();
		$this->zefaniaImportService = $zefaniaImportService;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 * @throws FileDoesNotExistException
	 */
	public function handle() {

		$xmlFile = $this->argument('xmlfile');

		if (!file_exists($xmlFile)) {
			throw new FileDoesNotExistException();
		}

		$result = $this->zefaniaImportService->import($xmlFile);

	}
}
