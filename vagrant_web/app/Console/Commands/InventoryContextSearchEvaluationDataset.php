<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Models\Resource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class InventoryContextSearchEvaluationDataset extends Command
{
    protected $signature = 'context-search:dataset:inventory {--json : Maschinell lesbare, inhaltsfreie Zusammenfassung ausgeben}';
    protected $description = 'Zählt als Evaluationsquelle geeignete Materialien und PDF-/Textressourcen ohne Inhalte auszulesen.';

    public function handle(): int
    {
        $resources = Resource::query()->withoutGlobalScopes()
            ->select('type', 'is_public', DB::raw('count(*) as total'))
            ->whereIn('type', ['pdf', 'text'])
            ->groupBy('type', 'is_public')
            ->orderBy('type')
            ->orderByDesc('is_public')
            ->get()
            ->map(fn (Resource $row): array => ['kind' => $row->type, 'visibility' => $row->is_public ? 'public' : 'private', 'count' => (int) $row->total])
            ->all();

        $materials = Material::query()->withoutGlobalScopes()
            ->select('is_public', DB::raw('count(*) as total'))
            ->whereHas('resources', fn ($query) => $query->withoutGlobalScopes()->whereIn('resources.type', ['pdf', 'text']))
            ->groupBy('is_public')
            ->orderByDesc('is_public')
            ->get()
            ->map(fn (Material $row): array => ['kind' => 'material', 'visibility' => $row->is_public ? 'public' : 'private', 'count' => (int) $row->total])
            ->all();

        $summary = ['materials' => $materials, 'resources' => $resources];
        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $this->table(['Gegenstand', 'Sichtbarkeit', 'Anzahl'], [...$materials, ...$resources]);
        $this->components->info('Die Inventarisierung liest keine Dokumentinhalte und startet weder Ollama noch Qdrant.');

        return self::SUCCESS;
    }
}
