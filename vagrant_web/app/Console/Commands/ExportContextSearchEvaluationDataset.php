<?php

namespace App\Console\Commands;

use App\Models\ContextSearchEvaluationDataset;
use App\Services\ContextSearch\EvaluationDatasetService;
use Illuminate\Console\Command;
use Throwable;

final class ExportContextSearchEvaluationDataset extends Command
{
    protected $signature = 'context-search:dataset:export {dataset : Eingefrorene Datensatz-ID}';
    protected $description = 'Erzeugt ein privates, hashgesichertes Evaluationsarchiv ohne KI- oder Qdrant-Aufruf.';

    public function handle(EvaluationDatasetService $datasets): int
    {
        try {
            $dataset = $datasets->export(ContextSearchEvaluationDataset::query()->findOrFail($this->argument('dataset')));
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Evaluationsarchiv erstellt: {$dataset->archive_path}");
        $this->components->info("Archiv-Prüfsumme: {$dataset->archive_hash}");

        return self::SUCCESS;
    }
}
