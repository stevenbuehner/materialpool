<?php

namespace Tests\Feature\Admin;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Services\ContextSearch\EvaluationDatasetCurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReconcileContextSearchEvaluationDatasetMembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_processes_more_than_one_dataset_batch_and_preserves_overview_counts(): void
    {
        $curation = app(EvaluationDatasetCurationService::class);
        $datasets = [];
        for ($index = 0; $index < 101; $index++) {
            $dataset = $curation->create('calibration');
            $dataset->update([
                'status' => ContextSearchEvaluationDataset::STATUS_FROZEN,
                'manifest' => ['materials' => [['source_id' => $index + 1]]],
                'material_count' => 1,
            ]);
            $datasets[] = $dataset;
        }

        $this->artisan('context-search:dataset:reconcile-memberships')
            ->expectsOutputToContain('Empfohlene Datensatzgrößen und Fortschritt')
            ->assertExitCode(0);
        $this->assertDatabaseCount('context_search_evaluation_dataset_members', 0);

        $this->artisan('context-search:dataset:reconcile-memberships --apply')->assertExitCode(0);
        $this->assertDatabaseCount('context_search_evaluation_dataset_members', 101);

        $this->artisan('context-search:dataset:reconcile-memberships --apply')
            ->expectsOutputToContain('Alle eingefrorenen Datensätze besitzen eine Mitgliedschaftsbilanz.')
            ->assertExitCode(0);
        $this->assertDatabaseCount('context_search_evaluation_dataset_members', 101);
    }

    public function test_a_conflict_in_a_later_member_batch_blocks_all_inserts(): void
    {
        $curation = app(EvaluationDatasetCurationService::class);
        $conflicting = $curation->create('acceptance');
        ContextSearchEvaluationDatasetMember::query()->create([
            'dataset_id' => $conflicting->id, 'member_type' => 'material', 'member_id' => 501,
        ]);
        $legacy = $curation->create('calibration');
        $legacy->update([
            'status' => ContextSearchEvaluationDataset::STATUS_EXPORTED,
            'manifest' => ['materials' => array_map(fn (int $id): array => ['source_id' => $id], range(1, 501))],
            'material_count' => 501,
        ]);

        $this->artisan('context-search:dataset:reconcile-memberships --apply')
            ->expectsOutputToContain("{$legacy->id}: Konflikt; keine Mitgliedschaften angelegt.")
            ->assertExitCode(0);
        $this->assertSame(0, ContextSearchEvaluationDatasetMember::query()->where('dataset_id', $legacy->id)->count());
    }

    public function test_it_inserts_large_resource_manifests_in_batches_with_revision_hashes(): void
    {
        $legacy = app(EvaluationDatasetCurationService::class)->create('ocr');
        $legacy->update([
            'status' => ContextSearchEvaluationDataset::STATUS_FROZEN,
            'manifest' => [
                'materials' => [['source_id' => 1]],
                'resources' => array_map(fn (int $id): array => [
                    'source_id' => $id, 'revision_hash' => hash('sha256', (string) $id),
                ], range(1, 501)),
            ],
            'material_count' => 1,
            'resource_count' => 501,
        ]);

        $this->artisan('context-search:dataset:reconcile-memberships --apply')->assertExitCode(0);

        $this->assertSame(502, ContextSearchEvaluationDatasetMember::query()->where('dataset_id', $legacy->id)->count());
        $this->assertDatabaseHas('context_search_evaluation_dataset_members', [
            'dataset_id' => $legacy->id, 'member_type' => 'resource', 'member_id' => 501,
            'source_revision_hash' => hash('sha256', '501'),
        ]);
    }
}
