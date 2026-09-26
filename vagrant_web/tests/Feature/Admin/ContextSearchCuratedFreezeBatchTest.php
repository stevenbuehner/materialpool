<?php

namespace Tests\Feature\Admin;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\Material;
use App\Models\Text;
use App\Models\User;
use App\Services\ContextSearch\EvaluationDatasetCurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class ContextSearchCuratedFreezeBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_freeze_preserves_all_sources_across_multiple_manifest_batches(): void
    {
        Passport::actingAs(User::factory()->create(['is_admin' => true]));
        $owner = User::factory()->create();
        $dataset = app(EvaluationDatasetCurationService::class)->create('calibration');
        $materialIds = [];
        $resourceIds = [];

        for ($index = 0; $index < 105; $index++) {
            $material = Material::factory()->publiclyVisible()->create([
                'created_by' => $owner->id, 'modified_by' => $owner->id,
            ]);
            $resource = Text::factory()->create([
                'created_by' => $owner->id, 'is_public' => true, 'content' => "Testinhalt {$index}",
            ]);
            $material->resources()->attach($resource->id);
            $materialIds[] = $material->id;
            $resourceIds[] = $resource->id;
            ContextSearchEvaluationDatasetMember::query()->create([
                'dataset_id' => $dataset->id, 'member_type' => ContextSearchEvaluationDatasetMember::TYPE_MATERIAL,
                'member_id' => $material->id,
            ]);
            ContextSearchEvaluationDatasetMember::query()->create([
                'dataset_id' => $dataset->id, 'member_type' => ContextSearchEvaluationDatasetMember::TYPE_RESOURCE,
                'member_id' => $resource->id,
            ]);
        }

        $dataset->update([
            'status' => ContextSearchEvaluationDataset::STATUS_READY,
            'material_count' => 105, 'resource_count' => 105,
        ]);

        $this->postJson('/api/v2/admin/context-search/datasets/'.$dataset->id.'/freeze')
            ->assertOk()
            ->assertJsonPath('dataset.status', ContextSearchEvaluationDataset::STATUS_FROZEN)
            ->assertJsonPath('dataset.material_count', 105)
            ->assertJsonPath('dataset.resource_count', 105);

        $dataset->refresh();
        sort($materialIds);
        sort($resourceIds);
        $this->assertSame($materialIds, array_column($dataset->manifest['materials'], 'source_id'));
        $this->assertSame($resourceIds, array_column($dataset->manifest['resources'], 'source_id'));
        $this->assertSame($dataset->manifest_hash, $dataset->manifest['manifest_hash']);
        $this->assertSame(210, $dataset->members()->count());
    }
}
