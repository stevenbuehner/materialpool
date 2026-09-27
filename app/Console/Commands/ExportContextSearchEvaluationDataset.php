<?php

namespace App\Console\Commands;

use App\Models\ContextSearchEvaluationDataset;
use App\Services\ContextSearch\EvaluationDatasetService;
use Illuminate\Console\Command;
use Throwable;

final class ExportContextSearchEvaluationDataset extends Command {
	protected $signature   = 'context-search:dataset:export {dataset? : Eingefrorene Datensatz-ID}';
	protected $description = 'Erzeugt ein privates, hashgesichertes Evaluationsarchiv ohne KI- oder Qdrant-Aufruf.';

	public function handle(EvaluationDatasetService $datasets): int {
		$datasetId = $this->argument('dataset');
		if ($datasetId === NULL) {
			$datasetId = $this->chooseDataset();
			if ($datasetId === NULL) {
				return self::FAILURE;
			}
		}

		$progress = new EvaluationDatasetProgress($this);
		try {
			$dataset = $datasets->export(ContextSearchEvaluationDataset::query()->findOrFail($datasetId), $progress);
			$progress->finish();
		} catch (Throwable $exception) {
			$progress->abort();
			report($exception);
			$this->components->error($exception->getMessage());

			return self::FAILURE;
		}

		$this->components->info("Evaluationsarchiv erstellt: {$dataset->archive_path}");
		$this->components->info("Archiv-Prüfsumme: {$dataset->archive_hash}");

		return self::SUCCESS;
	}

	private function chooseDataset(): ?string {
		$datasets = ContextSearchEvaluationDataset::query()
			->where('status', ContextSearchEvaluationDataset::STATUS_FROZEN)
			->orderBy('created_at')
			->get(['id', 'purpose', 'material_count', 'resource_count']);
		if ($datasets->isEmpty()) {
			$this->components->warn('Es sind keine eingefrorenen Datensätze für den Export vorhanden.');

			return NULL;
		}

		$this->table(['UUID', 'Zweck', 'Materialien', 'Ressourcen'], $datasets->map(fn(ContextSearchEvaluationDataset $dataset): array => [
			$dataset->getKey(), $dataset->purpose, $dataset->material_count, $dataset->resource_count,
		])->all());
		if (!$this->input->isInteractive()) {
			$this->components->error('Ohne interaktives Terminal bitte eine Datensatz-UUID angeben.');

			return NULL;
		}

		$choices   = $datasets->map(fn(ContextSearchEvaluationDataset $dataset): string => $dataset->getKey() . ' (' . $dataset->purpose . ')')->all();
		$choices[] = 'Abbrechen';
		$selected  = $this->choice('Welchen Datensatz exportieren?', $choices);

		return $selected === 'Abbrechen' ? NULL : explode(' ', $selected, 2)[0];
	}
}
