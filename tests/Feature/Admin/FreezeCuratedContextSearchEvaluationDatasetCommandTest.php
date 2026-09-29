<?php

namespace Tests\Feature\Admin;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\Material;
use App\Models\Text;
use App\Models\User;
use App\Services\ContextSearch\EvaluationDatasetCurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FreezeCuratedContextSearchEvaluationDatasetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_freezes_the_existing_browser_selection_without_creating_a_second_dataset(): void
    {
        $owner = User::factory()->create();
        $material = Material::factory()->publiclyVisible()->create([
            'created_by' => $owner->id, 'modified_by' => $owner->id,
        ]);
        $resource = Text::factory()->create([
            'created_by' => $owner->id, 'is_public' => true, 'content' => 'Auswahl aus dem Browser.',
        ]);
        $material->resources()->attach($resource->id);
        $dataset = app(EvaluationDatasetCurationService::class)->create('calibration');
        foreach ([
            [ContextSearchEvaluationDatasetMember::TYPE_MATERIAL, $material->id],
            [ContextSearchEvaluationDatasetMember::TYPE_RESOURCE, $resource->id],
        ] as [$type, $id]) {
            ContextSearchEvaluationDatasetMember::query()->create([
                'dataset_id' => $dataset->id, 'member_type' => $type, 'member_id' => $id,
            ]);
        }
        $dataset->update([
            'status' => ContextSearchEvaluationDataset::STATUS_READY,
            'material_count' => 1, 'resource_count' => 1,
        ]);

        $this->artisan('context-search:dataset:freeze-curated', ['dataset' => $dataset->id])
            ->expectsOutputToContain('eingefroren')
            ->assertExitCode(0);

        $dataset->refresh();
        $this->assertSame(ContextSearchEvaluationDataset::STATUS_FROZEN, $dataset->status);
        $this->assertSame($material->id, $dataset->manifest['materials'][0]['source_id']);
        $this->assertSame($resource->id, $dataset->manifest['resources'][0]['source_id']);
        $this->assertSame($dataset->manifest_hash, $dataset->manifest['manifest_hash']);
        $this->assertDatabaseCount('context_search_evaluation_datasets', 1);
        $this->assertDatabaseCount('context_search_evaluation_dataset_members', 2);

        $this->artisan('context-search:dataset:freeze-curated', ['dataset' => $dataset->id])
            ->expectsOutputToContain('Nur ein vollständiger Entwurf darf eingefroren werden.')
            ->assertExitCode(1);
        $this->assertDatabaseCount('context_search_evaluation_datasets', 1);
    }

    public function test_it_rejects_an_incomplete_or_missing_dataset(): void
    {
        $dataset = app(EvaluationDatasetCurationService::class)->create('acceptance');

        $this->artisan('context-search:dataset:freeze-curated', ['dataset' => $dataset->id])
            ->expectsOutputToContain('Nur ein vollständiger Entwurf darf eingefroren werden.')
            ->assertExitCode(1);
        $this->assertSame(ContextSearchEvaluationDataset::STATUS_DRAFT, $dataset->fresh()->status);

        $this->artisan('context-search:dataset:freeze-curated', ['dataset' => '00000000-0000-0000-0000-000000000000'])
            ->expectsOutputToContain('Der Datensatz wurde nicht gefunden.')
            ->assertExitCode(1);
    }

    public function test_it_lists_all_datasets_and_only_offers_eligible_ones_for_selection(): void
    {
        $owner = User::factory()->create();
        $material = Material::factory()->publiclyVisible()->create([
            'created_by' => $owner->id, 'modified_by' => $owner->id,
        ]);
        $resource = Text::factory()->create([
            'created_by' => $owner->id, 'is_public' => true, 'content' => 'Auswahl aus dem Browser.',
        ]);
        $material->resources()->attach($resource->id);

        $incomplete = app(EvaluationDatasetCurationService::class)->create('acceptance');
        $ready = app(EvaluationDatasetCurationService::class)->create('calibration');
        $closed = app(EvaluationDatasetCurationService::class)->create('ocr');
        $closed->update(['status' => ContextSearchEvaluationDataset::STATUS_FROZEN]);
        foreach ([
            [ContextSearchEvaluationDatasetMember::TYPE_MATERIAL, $material->id],
            [ContextSearchEvaluationDatasetMember::TYPE_RESOURCE, $resource->id],
        ] as [$type, $id]) {
            ContextSearchEvaluationDatasetMember::query()->create([
                'dataset_id' => $ready->id, 'member_type' => $type, 'member_id' => $id,
            ]);
        }
        $ready->update([
            'status' => ContextSearchEvaluationDataset::STATUS_READY,
            'target_material_count' => 1,
            'target_resource_count' => 1,
            'target_quotas' => ['text' => 1],
        ]);

        $choice = $ready->id.' (calibration)';
        $this->artisan('context-search:dataset:freeze-curated')
            ->expectsOutputToContain('PDF-Seiten')
            ->expectsOutputToContain($incomplete->id)
            ->expectsOutputToContain($ready->id)
            ->expectsOutputToContain($closed->id)
            ->expectsOutputToContain('0/300')
            ->expectsOutputToContain('bereits eingefroren')
            ->expectsChoice('Welchen Datensatz einfrieren?', $choice, [$choice, 'Abbrechen'])
            ->expectsOutputToContain('eingefroren')
            ->assertExitCode(0);

        $this->assertSame(ContextSearchEvaluationDataset::STATUS_FROZEN, $ready->fresh()->status);
        $this->assertSame(ContextSearchEvaluationDataset::STATUS_DRAFT, $incomplete->fresh()->status);
    }

    public function test_it_lists_datasets_without_freezing_in_non_interactive_mode(): void
    {
        $dataset = app(EvaluationDatasetCurationService::class)->create('acceptance');

        $this->artisan('context-search:dataset:freeze-curated', ['--no-interaction' => true])
            ->expectsOutputToContain($dataset->id)
            ->assertExitCode(1);

        $this->assertSame(ContextSearchEvaluationDataset::STATUS_DRAFT, $dataset->fresh()->status);
    }
}
