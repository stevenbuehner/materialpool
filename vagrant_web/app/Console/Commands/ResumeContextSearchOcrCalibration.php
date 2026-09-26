<?php

namespace App\Console\Commands;

use App\Services\ContextSearch\OcrCalibrationService;
use Illuminate\Console\Command;
use Throwable;

final class ResumeContextSearchOcrCalibration extends Command
{
    protected $signature = 'context-search:ocr-calibration:resume {run : UUID des Kalibrierungslaufs}';

    protected $description = 'Plant nach einem unterbrochenen Dispatch wartende OCR-Kalibrierungsseiten erneut ein.';

    public function handle(OcrCalibrationService $calibration): int
    {
        try {
            $count = $calibration->resumePending((string) $this->argument('run'));
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("{$count} wartende OCR-Seiten wurden erneut eingeplant.");

        return self::SUCCESS;
    }
}
