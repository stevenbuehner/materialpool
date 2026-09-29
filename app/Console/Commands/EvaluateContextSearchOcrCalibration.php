<?php

namespace App\Console\Commands;

use App\Models\ContextSearchOcrCalibrationRun;
use App\Services\ContextSearch\OcrCalibrationService;
use Illuminate\Console\Command;
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

		try {
			$results = $calibration->evaluate($run);
		} catch (Throwable $exception) {
			$this->components->error($exception->getMessage());

			return self::FAILURE;
		}

		$this->components->info('OCR-Schwellenwerte wurden ausgewertet und im Lauf gespeichert.');
		if ($results['recommended_threshold'] === NULL) {
			$this->components->warn('Kein Grenzwert erreicht die geforderte Brauchbar-Präzision von 95 %. Das Profil darf nicht freigegeben werden.');
		} else {
			$this->line('Empfohlene mittlere Konfidenz: '.number_format($results['recommended_threshold'] * 100, 0, ',', '.').' %.');
			$this->line('Holdout-Brauchbar-Präzision: '.number_format(($results['holdout']['usable_precision'] ?? 0) * 100, 1, ',', '.').' %.');
		}

		return self::SUCCESS;
	}
}
