<?php

namespace App\Console\Commands;

use App\Services\ContextSearch\EvaluationDatasetService;
use Illuminate\Console\Command;
use Throwable;

final class ImportContextSearchEvaluationDataset extends Command
{
    protected $signature = 'context-search:dataset:import {archive : Relativer Pfad im privaten Evaluationsspeicher}';
    protected $description = 'Importiert ein geprüftes Archiv ausschließlich in eine explizit freigegebene Evaluationsumgebung.';

    public function handle(EvaluationDatasetService $datasets): int
    {
        try {
            $dataset = $datasets->import((string) $this->argument('archive'));
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Evaluationsdatensatz {$dataset->getKey()} wurde isoliert importiert.");

        return self::SUCCESS;
    }
}
