<?php

namespace App\Console\Commands;

use App\Models\ContextSearchEvaluationDataset;
use App\Services\ContextSearch\EvaluationDatasetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class FreezeCuratedContextSearchEvaluationDataset extends Command
{
    protected $signature = 'context-search:dataset:freeze-curated {dataset : UUID eines im Browser vollständig kuratierten Datensatzes}';

    protected $description = 'Friert einen bestehenden vollständigen Datensatz mit seiner gespeicherten Auswahl ein.';

    public function handle(EvaluationDatasetService $datasets): int
    {
        $datasetId = (string) $this->argument('dataset');
        try {
            $dataset = ContextSearchEvaluationDataset::query()->find($datasetId);
            if ($dataset === null) {
                $this->components->error('Der Datensatz wurde nicht gefunden.');

                return self::FAILURE;
            }

            $dataset = $datasets->freezeCurated($dataset);
        } catch (Throwable $exception) {
            if ($exception::class === RuntimeException::class) {
                $this->components->error($exception->getMessage());

                return self::FAILURE;
            }

            Log::error('Ein Evaluationsdatensatz konnte nicht eingefroren werden.', [
                'dataset_id' => $datasetId,
                'exception_type' => $exception::class,
            ]);
            $this->components->error('Das Einfrieren ist fehlgeschlagen. Vor einem erneuten Versuch den Datensatzstatus prüfen.');

            return self::FAILURE;
        }

        $this->components->info("Evaluationsdatensatz {$dataset->getKey()} ist eingefroren ({$dataset->material_count} Materialien, {$dataset->resource_count} Ressourcen).");
        $this->components->warn('Für ein Archiv den Datensatz anschließend ausdrücklich exportieren.');

        return self::SUCCESS;
    }
}
