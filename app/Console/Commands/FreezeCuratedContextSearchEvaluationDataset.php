<?php

namespace App\Console\Commands;

use App\Models\ContextSearchEvaluationDataset;
use App\Services\ContextSearch\EvaluationDatasetCurationService;
use App\Services\ContextSearch\EvaluationDatasetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class FreezeCuratedContextSearchEvaluationDataset extends Command {
	protected $signature = 'context-search:dataset:freeze-curated {dataset? : UUID eines im Browser vollständig kuratierten Datensatzes}';

	protected $description = 'Friert einen bestehenden vollständigen Datensatz mit seiner gespeicherten Auswahl ein.';

	public function handle(EvaluationDatasetService $datasets, EvaluationDatasetCurationService $curation): int {
		$datasetId = $this->argument('dataset');
		if ($datasetId === NULL) {
			$datasetId = $this->chooseDataset($curation);
			if ($datasetId === NULL) {
				return self::FAILURE;
			}
		}

		$progress = NULL;
		try {
			$dataset = ContextSearchEvaluationDataset::query()->find($datasetId);
			if ($dataset === NULL) {
				$this->components->error('Der Datensatz wurde nicht gefunden.');

				return self::FAILURE;
			}

			if ($dataset->status === ContextSearchEvaluationDataset::STATUS_READY) {
				$progress = $this->output->createProgressBar($dataset->members()->count() + 1);
				$progress->setFormat('%current%/%max% [%bar%] %percent:3s%% %message%');
				$progress->setMessage('Quellen verarbeiten');
				$progress->start();
			}

			$dataset = $datasets->freezeCurated($dataset, function (string $phase) use ($progress): void {
				$progress?->setMessage($phase);
				$progress?->advance();
			});
			if ($progress !== NULL) {
				$progress->setMessage('Manifest gespeichert');
				$progress->finish();
				$this->newLine();
			}
		} catch (Throwable $exception) {
			if ($progress !== NULL) {
				$this->newLine();
			}
			if ($exception::class === RuntimeException::class) {
				$this->components->error($exception->getMessage());

				return self::FAILURE;
			}

			Log::error('Ein Evaluationsdatensatz konnte nicht eingefroren werden.', [
				'dataset_id'     => $datasetId,
				'exception_type' => $exception::class,
			]);
			$this->components->error('Das Einfrieren ist fehlgeschlagen. Vor einem erneuten Versuch den Datensatzstatus prüfen.');

			return self::FAILURE;
		}

		$this->components->info("Evaluationsdatensatz {$dataset->getKey()} ist eingefroren ({$dataset->material_count} Materialien, {$dataset->resource_count} Ressourcen).");
		$this->components->warn('Für ein Archiv den Datensatz anschließend ausdrücklich exportieren.');

		return self::SUCCESS;
	}

	private function chooseDataset(EvaluationDatasetCurationService $curation): ?string {
		$summaries = $curation->summaries();
		if ($summaries === []) {
			$this->components->warn('Es sind keine Evaluationsdatensätze vorhanden.');

			return NULL;
		}

		$choices = [];
		$rows    = [];
		foreach ($summaries as $summary) {
			$assessment = $this->assessment($summary);
			$id         = (string)$summary['id'];
			$rows[]     = [
				$id,
				$summary['purpose'],
				in_array($summary['status'], [ContextSearchEvaluationDataset::STATUS_DRAFT, ContextSearchEvaluationDataset::STATUS_READY], TRUE) ? 'offen' : 'geschlossen',
				$summary['status'],
				$summary['purpose'] === 'ocr' && $summary['target_material_count'] === 0 ? '—' : $summary['material_count'] . '/' . $summary['target_material_count'],
				$summary['purpose'] === 'ocr' && $summary['target_resource_count'] === 0 ? '—' : $summary['resource_count'] . '/' . $summary['target_resource_count'],
				isset($summary['quotas']['pdf_pages']) ? $summary['quotas']['pdf_pages']['actual'] . '/' . $summary['quotas']['pdf_pages']['target'] : '—',
				$assessment,
			];
			if ($assessment === 'einfrierbar') {
				$choices[] = $id . ' (' . $summary['purpose'] . ')';
			}
		}

		$this->table(['UUID', 'Zweck', 'Phase', 'Status', 'Materialien', 'Ressourcen', 'PDF-Seiten', 'Bewertung'], $rows);
		$this->components->info('Die Bewertung prüft Status, Mengen und Quoten. Quelldateien werden beim Einfrieren geprüft.');

		if ($choices === []) {
			$this->components->warn('Kein Datensatz erfüllt die Voraussetzungen zum Einfrieren.');

			return NULL;
		}
		if (!$this->input->isInteractive()) {
			$this->components->error('Ohne interaktives Terminal bitte eine Datensatz-UUID angeben.');

			return NULL;
		}

		$choices[] = 'Abbrechen';
		$selected  = $this->choice('Welchen Datensatz einfrieren?', $choices);

		return $selected === 'Abbrechen' ? NULL : explode(' ', $selected, 2)[0];
	}

	/** @param array<string, mixed> $summary */
	private function assessment(array $summary): string {
		if ($summary['status'] === ContextSearchEvaluationDataset::STATUS_FROZEN) {
			return 'bereits eingefroren';
		}
		if ($summary['status'] === ContextSearchEvaluationDataset::STATUS_EXPORTED) {
			return 'bereits exportiert';
		}

		$missing = [];
		if ($summary['materials_remaining'] > 0) {
			$missing[] = $summary['materials_remaining'] . ' Materialien';
		}
		if ($summary['resources_remaining'] > 0) {
			$missing[] = $summary['resources_remaining'] . ' Ressourcen';
		}
		foreach ($summary['quotas'] as $name => $quota) {
			if ($quota['remaining'] > 0) {
				$label = ['pdf_pages' => 'PDF-Seiten', 'book' => 'Bücher', 'worksheet' => 'Arbeitsblätter', 'presentation' => 'Präsentationen'][$name] ?? $name;
				$missing[] = $label . ': ' . $quota['remaining'];
			}
		}
		if ($summary['material_count'] === 0 || $summary['resource_count'] === 0) {
			$missing[] = 'Auswahl leer';
		}
		if ($summary['status'] !== ContextSearchEvaluationDataset::STATUS_READY) {
			$missing[] = 'Status ' . $summary['status'];
		}

		return $missing === [] ? 'einfrierbar' : 'offen: ' . implode(', ', $missing);
	}
}
