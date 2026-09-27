<?php

namespace App\Console\Commands;

use App\Services\ContextSearch\EvaluationDatasetService;
use Illuminate\Console\Command;
use Throwable;

final class FreezeContextSearchEvaluationDataset extends Command
{
    protected $signature = 'context-search:dataset:freeze
        {purpose : calibration, acceptance, ocr, load oder capacity}
        {--materials= : Kommagetrennte Material-IDs}
        {--resources= : Kommagetrennte Ressourcen-IDs}
        {--include-private : Private Inhalte ausdrücklich einschließen}
        {--reason= : Pflichtbegründung beim Einschluss privater Inhalte}';

    protected $description = 'Friert einen revisionsgebundenen Evaluationsdatensatz ohne KI- oder Qdrant-Aufruf ein.';

    public function handle(EvaluationDatasetService $datasets): int
    {
        try {
            $dataset = $datasets->freeze(
                (string) $this->argument('purpose'),
                $this->ids('materials'),
                $this->ids('resources'),
                (bool) $this->option('include-private'),
                $this->option('reason'),
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Evaluationsdatensatz {$dataset->getKey()} ist eingefroren ({$dataset->material_count} Materialien, {$dataset->resource_count} Ressourcen).");
        $this->components->warn('Der Datensatz enthält keine Vektoren und hat weder Ollama noch Qdrant aufgerufen.');

        return self::SUCCESS;
    }

    /** @return array<int, int> */
    private function ids(string $option): array
    {
        $value = trim((string) $this->option($option));
        if ($value === '') {
            return [];
        }
        $ids = array_map('trim', explode(',', $value));
        if (array_filter($ids, fn (string $id): bool => filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false)) {
            throw new \InvalidArgumentException("Die Option --{$option} enthält keine gültigen positiven IDs.");
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
