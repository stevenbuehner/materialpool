<?php

namespace App\Console\Commands;

use App\Services\ContextSearch\EvaluationDatasetService;
use Illuminate\Console\Command;
use Throwable;

final class ImportContextSearchEvaluationDataset extends Command {
	protected $signature   = 'context-search:dataset:import {archive : Relativer Pfad im privaten Evaluationsspeicher} {expected_sha256? : Optionale Archiv-Prüfsumme aus dem Export}';
	protected $description = 'Importiert ein geprüftes Archiv ausschließlich in eine explizit freigegebene Evaluationsumgebung.';

	public function handle(EvaluationDatasetService $datasets): int {
		$progress = new EvaluationDatasetProgress($this);
		try {
			$expectedHash = $this->argument('expected_sha256');
			$result       = $datasets->import((string)$this->argument('archive'), $expectedHash, $progress);
			$progress->finish();
		} catch (Throwable $exception) {
			$progress->abort();
			report($exception);
			$this->components->error($exception->getMessage());

			return self::FAILURE;
		}

		$this->components->info("Evaluationsdatensatz {$result['dataset']->getKey()} wurde isoliert importiert.");
		$this->components->info("Archiv-Prüfsumme: {$result['archive_hash']}");

		return self::SUCCESS;
	}
}
