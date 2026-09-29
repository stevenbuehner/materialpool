<?php

namespace App\Console\Commands;

use App\Models\ContextSearchOcrCalibrationRun;
use App\Services\ContextSearch\OcrCalibrationService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

final class EvaluateContextSearchOcrCalibration extends Command {
	protected $signature = 'context-search:ocr-calibration:evaluate {run : UUID des Kalibrierungslaufs}';

	protected $description = 'Wertet die OCR-Schwellenwerte eines bestehenden Kalibrierungslaufs im Terminal aus.';

	public function handle(OcrCalibrationService $calibration): int {
		if (app()->isProduction()) {
			$this->components->error('OCR-Kalibrierungen dürfen nicht in Produktion ausgewertet werden.');

			return self::FAILURE;
		}

		$run = ContextSearchOcrCalibrationRun::query()->find((string)$this->argument('run'));
		if ($run === NULL) {
			$this->components->error('Kalibrierungslauf nicht gefunden.');

			return self::FAILURE;
		}

		if ($run->status === ContextSearchOcrCalibrationRun::STATUS_APPROVED || $run->processed_pages < $run->total_pages) {
			$this->components->error('Der Lauf muss vollständig verarbeitet und noch nicht freigegeben sein.');

			return self::FAILURE;
		}

		/** @var ProgressBar|null $bar */
		$bar            = NULL;
		$currentPhase   = NULL;
		$currentMessage = NULL;
		$progress       = function (string $phase, int $current, int $total, ?float $threshold) use (&$bar, &$currentPhase, &$currentMessage): void {
			if ($currentPhase !== $phase) {
				if ($bar !== NULL) {
					$bar->finish();
					$this->newLine();
				}

				$currentPhase  = $phase;
				$currentMessage = NULL;
				if ($phase === 'sweep') {
					$this->line('Kalibrierungssweep: '.intdiv($total, 21).' Seiten je Schwelle, 21 Schwellen');
				} else {
					$this->line('Holdout für die empfohlene Schwelle');
				}
				$bar = $this->getOutput()->createProgressBar(max(1, $total));
				$bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %message%');
				$bar->setRedrawFrequency(max(1, (int)ceil($total / 100)));
				$bar->start();
			}

			$message = 'Konfidenz ≥ '.number_format(($threshold ?? 0) * 100, 0, ',', '.').' %';
			if ($currentMessage !== $message) {
				$currentMessage = $message;
				$bar->setMessage($message);
			}
			$bar->setProgress($current);
		};

		try {
			$results = $calibration->evaluate($run, $progress);
			if ($bar !== NULL) {
				$bar->finish();
				$this->newLine(2);
			}
		} catch (Throwable $exception) {
			if ($bar !== NULL) {
				$this->newLine();
			}
			$this->components->error($exception->getMessage());

			return self::FAILURE;
		}

		$this->components->info('OCR-Schwellenwerte wurden ausgewertet und im Lauf gespeichert.');
		$this->line('Kalibrierungsseiten: '.$results['calibration_pages'].' · Holdout-Seiten: '.$results['holdout_pages'].' · Zielpräzision: 95 %');
		$recommendedThreshold = $results['recommended_threshold'];
		$this->table(
			['Schwelle', 'Angenommen', 'Abdeckung', 'Präzision', 'CER', 'WER', 'Ref.-Seiten', 'Bewertung'],
			array_map(function (array $score) use ($recommendedThreshold): array {
				return [
					number_format($score['threshold'] * 100, 0, ',', '.').' %',
					$score['accepted_pages'].'/'.$score['labeled_pages'],
					$this->formatPercent($score['coverage']),
					$this->formatPercent($score['usable_precision']),
					$this->formatPercent($score['character_error_rate']),
					$this->formatPercent($score['word_error_rate']),
					$score['reference_pages_measured'],
					$score['threshold'] === $recommendedThreshold ? 'Empfohlen' : '',
				];
			}, $results['threshold_sweep']),
		);

		if ($results['recommended_threshold'] === NULL) {
			$this->components->warn('Kein Grenzwert erreicht die geforderte Brauchbar-Präzision von 95 %. Das Profil darf nicht freigegeben werden.');
			$this->line('Der Holdout wurde übersprungen, da kein Kandidat die Mindestpräzision erreicht hat.');
		} else {
			$this->components->info('Empfohlene mittlere Konfidenz: '.number_format($results['recommended_threshold'] * 100, 0, ',', '.').' %.');
			$holdout = $results['holdout'];
			$this->table(['Holdout', 'Angenommen', 'Abdeckung', 'Präzision', 'CER', 'WER', 'Referenzen'], [[
				'Validierung',
				$holdout['accepted_pages'].'/'.$holdout['labeled_pages'],
				$this->formatPercent($holdout['coverage']),
				$this->formatPercent($holdout['usable_precision']),
				$this->formatPercent($holdout['character_error_rate']),
				$this->formatPercent($holdout['word_error_rate']),
				$holdout['reference_pages_measured'],
			]]);
			if (($holdout['usable_precision'] ?? 0) < 0.90
				|| !is_numeric($holdout['character_error_rate'] ?? NULL)
				|| !is_numeric($holdout['word_error_rate'] ?? NULL)) {
				$this->components->warn('Holdout erfüllt die Freigabekriterien nicht; das Profil kann nicht freigegeben werden.');
			} else {
				$this->components->info('Holdout erfüllt die technischen Freigabekriterien. Prüfe zusätzlich die Seitenbeispiele.');
			}
		}

		return self::SUCCESS;
	}

	private function formatPercent(?float $value): string {
		return $value === NULL ? '—' : number_format($value * 100, 1, ',', '.').' %';
	}
}
