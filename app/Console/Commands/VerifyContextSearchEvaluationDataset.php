<?php

namespace App\Console\Commands;

use App\Services\ContextSearch\EvaluationDatasetService;
use Illuminate\Console\Command;
use Throwable;

final class VerifyContextSearchEvaluationDataset extends Command
{
    protected $signature = 'context-search:dataset:verify {archive : Relativer Pfad im privaten Evaluationsspeicher}';
    protected $description = 'Prüft Archiv- und Manifest-Integrität eines Evaluationsdatensatzes.';

    public function handle(EvaluationDatasetService $datasets): int
    {
        $progress = new EvaluationDatasetProgress($this);
        try {
            $result = $datasets->verify((string) $this->argument('archive'), $progress);
            $progress->finish();
        } catch (Throwable $exception) {
            $progress->abort();
            report($exception);
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Manifest-Prüfsumme: {$result['manifest_hash']}");
        $this->components->info("Archiv-Prüfsumme: {$result['archive_hash']}");

        return self::SUCCESS;
    }
}
