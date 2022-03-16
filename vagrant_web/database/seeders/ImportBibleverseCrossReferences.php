<?php

namespace Database\Seeders;

use App\Models\BibleverseCrossReference;
use Illuminate\Database\Seeder;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class ImportBibleverseCrossReferences extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {

		// From: https://github.com/scrollmapper/bible_databases
		$filePath = realpath(__DIR__ . '/../../resources/cross_references/cross_reference-mysql.sql');

		$output = new ConsoleOutput();
		$output->writeln('<info>Start importing cross references</info>');

		if (!file_exists($filePath)) {
			$msg = 'Corss-Reference SQL-File does not exist at ' . $filePath;
			$output->writeln('<error>$msg</error>');

			throw new \Exception($msg);
		}

		$progressBar = new ProgressBar($output);
		$progressBar->setFormat(' %current% [%bar%] %elapsed:6s% %memory:6s%');
		$progressBar->minSecondsBetweenRedraws(0.5);
		$progressBar->maxSecondsBetweenRedraws(2);

		$handle           = fopen($filePath, "r");
		$skippedLines     = 0;
		$continueSkipping = TRUE;
		$totalImportLines = 0;
		$batch            = [];
		$batchSize        = 100;

		if ($handle) {

			$line = fgets($handle);

			while ($line !== FALSE) {

				if ($continueSkipping === TRUE) {
					if (str_starts_with($line, 'INSERT INTO `cross_reference`')) {
						$continueSkipping = FALSE;

						// Drop all entries
						$countDropped = BibleverseCrossReference::query()->delete();

						if ($countDropped > 0) {
							$output->writeln("<comment>$countDropped entries where deleted before import</comment>");
						}

						$progressBar->start();

					} else {
						$skippedLines++;
					}

				} else {

					$totalImportLines++;

					// (01001001, 10, 19104030, 00000000),
					if (preg_match('~(?<source>\d+),\s*(?<relevance>\d+),\s*(?<target_from>\d+),\s*(?<target_to>\d+)\s*~', $line, $match) === 1) {

						$batch[] = [
							'source'      => $match['source'],
							'relevance'   => $match['relevance'],
							'target_from' => $match['target_from'],
							'target_to'   => $match['target_to'],
						];


					}

					// Create in one batch
					if (count($batch) >= $batchSize) {
						BibleverseCrossReference::insert(
							$batch
						);
						$progressBar->advance(count($batch));
						$batch = [];
					}


				}

				$line = fgets($handle);

			}

			fclose($handle);

			$progressBar->setProgress($totalImportLines);
			$progressBar->finish();
			$output->writeln('<info>Import finished</info>');

		}
	}

}
