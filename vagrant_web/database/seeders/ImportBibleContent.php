<?php

namespace Database\Seeders;

use App\Models\BibleverseCrossReference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class ImportBibleContent extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {

		$output = new ConsoleOutput();
		$output->writeln('<info>Start importing bible translations</info>');

		$rootPath  = realpath(__DIR__ . '/../../resources/bibles');
		$driver = Storage::createLocalDriver([
			'root' => $rootPath
		]);
		$allFiles  = $driver->files('/');

		$progressBar = new ProgressBar($output);
		$progressBar->setMaxSteps(count($allFiles));
		$progressBar->setFormat(' %current% [%bar%] %elapsed:6s% %memory:6s% --- %message%');
		$progressBar->setMessage('');
		$progressBar->display();

		foreach ($allFiles as $file) {

			$progressBar->setMessage("importing '${file}'");
			$progressBar->display();

			\Artisan::call('import:zefaniabible', [
				'xmlfile' => $rootPath . '/' . $file
			]);

			$progressBar->advance();
			$progressBar->display();

		}

		$progressBar->finish();
		$output->writeln('');
		$output->writeln('<info>Import finished</info>');

	}

}
