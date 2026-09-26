<?php

namespace Tests\Feature\Admin;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\Material;
use App\Models\Text;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class ContextSearchEvaluationDatasetCurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_global_admin_can_curate_evaluation_datasets(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/v2/admin/context-search/datasets')->assertNotFound();
    }

    public function test_reconciliation_command_prints_inhaltsfreie_target_progress_overview(): void
    {
        $this->artisan('context-search:dataset:reconcile-memberships')
            ->expectsOutputToContain('Empfohlene Datensatzgrößen und Fortschritt')
            ->expectsOutputToContain('Kalibrierung')
            ->expectsOutputToContain('Gesamt')
            ->assertExitCode(0);
    }

    public function test_assigning_a_material_reserves_its_entire_connected_component(): void
    {
        $this->asAdmin();
        [$firstMaterial, $secondMaterial, $firstText, $secondText] = $this->connectedSources();
        $dataset = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'calibration'])->assertCreated()->json('dataset');

        $this->postJson('/api/v2/admin/context-search/datasets/preview', ['material_ids' => [$firstMaterial->id], 'resource_ids' => []])
            ->assertOk()->assertJsonPath('preview.counts.materials', 2)->assertJsonPath('preview.counts.resources', 2);

        $this->postJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/assign', [
            'material_ids' => [$firstMaterial->id], 'resource_ids' => [], 'expected_version' => 1,
            'include_private' => false,
        ])->assertOk()->assertJsonPath('dataset.material_count', 2)->assertJsonPath('dataset.resource_count', 2);

        $this->assertDatabaseCount('context_search_evaluation_dataset_members', 4);
        $this->assertDatabaseHas('context_search_evaluation_dataset_members', ['dataset_id' => $dataset['id'], 'member_type' => 'material', 'member_id' => $secondMaterial->id]);
        $this->assertDatabaseHas('context_search_evaluation_dataset_members', ['dataset_id' => $dataset['id'], 'member_type' => 'resource', 'member_id' => $secondText->id]);
    }

    public function test_conflicting_assignment_and_outdated_version_are_rejected(): void
    {
        $this->asAdmin();
        [$firstMaterial] = $this->connectedSources();
        $first = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'calibration'])->assertCreated()->json('dataset');
        $second = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'acceptance'])->assertCreated()->json('dataset');
        $payload = ['material_ids' => [$firstMaterial->id], 'resource_ids' => [], 'expected_version' => 1, 'include_private' => false];

        $this->postJson('/api/v2/admin/context-search/datasets/'.$first['id'].'/assign', $payload)->assertOk();
        $this->postJson('/api/v2/admin/context-search/datasets/'.$second['id'].'/assign', $payload)->assertUnprocessable();
        $this->postJson('/api/v2/admin/context-search/datasets/'.$first['id'].'/assign', $payload)->assertConflict();
    }

    public function test_private_content_requires_explicit_reason_and_candidate_endpoint_marks_assignment(): void
    {
        $this->asAdmin();
        $owner = User::factory()->create();
        $material = Material::factory()->privatelyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
        $text = Text::factory()->create(['created_by' => $owner->id, 'is_public' => false, 'content' => 'Privater Testinhalt.']);
        $material->resources()->attach($text->id);
        $dataset = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'ocr'])->assertCreated()->json('dataset');
        $payload = ['material_ids' => [$material->id], 'resource_ids' => [], 'expected_version' => 1, 'include_private' => false];
        $this->postJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/assign', $payload)->assertUnprocessable();

        $payload['include_private'] = true; $payload['private_reason'] = 'Abnahme privater Quellen';
        $this->postJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/assign', $payload)->assertOk();
        $this->getJson('/api/v2/admin/context-search/datasets/candidates?visibility=private')->assertOk()
            ->assertJsonPath('data.0.assignment.purpose', 'ocr')->assertJsonPath('data.0.resources.0.assignment.purpose', 'ocr');
    }

    public function test_complete_curated_draft_can_be_frozen_without_losing_membership_ledger(): void
    {
        $this->asAdmin();
        [$material] = $this->connectedSources();
        $dataset = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'calibration'])->assertCreated()->json('dataset');
        $assigned = $this->postJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/assign', [
            'material_ids' => [$material->id], 'resource_ids' => [], 'expected_version' => 1, 'include_private' => false,
        ])->assertOk()->json('dataset');
        ContextSearchEvaluationDataset::query()->findOrFail($dataset['id'])->update([
            'status' => ContextSearchEvaluationDataset::STATUS_READY,
            'target_material_count' => $assigned['material_count'], 'target_resource_count' => $assigned['resource_count'], 'target_quotas' => [],
        ]);

        $this->postJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/freeze')->assertOk()
            ->assertJsonPath('dataset.status', ContextSearchEvaluationDataset::STATUS_FROZEN);
        $this->assertSame(4, ContextSearchEvaluationDatasetMember::query()->where('dataset_id', $dataset['id'])->count());
    }

    private function asAdmin(): void
    {
        Passport::actingAs(User::factory()->create(['is_admin' => true]));
    }

    /** @return array{Material, Material, Text, Text} */
    private function connectedSources(): array
    {
        $owner = User::factory()->create();
        $first = Material::factory()->publiclyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
        $second = Material::factory()->publiclyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
        $one = Text::factory()->create(['created_by' => $owner->id, 'is_public' => true, 'content' => 'Erste Textquelle.']);
        $two = Text::factory()->create(['created_by' => $owner->id, 'is_public' => true, 'content' => 'Zweite Textquelle.']);
        $first->resources()->attach($one->id);
        $second->resources()->attach([$one->id, $two->id]);
        return [$first, $second, $one, $two];
    }
}
