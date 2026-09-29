<?php

namespace Tests\Feature\Admin;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Text;
use App\Models\User;
use App\Services\ContextSearch\EvaluationDatasetCurationService;
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

    public function test_ocr_readiness_uses_known_pages_and_three_document_types(): void
    {
        $this->asAdmin();
        $owner = User::factory()->create();
        $material = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id, 'is_public' => true]);
        $pdfs = collect([300, 1, null])->map(function (?int $pages) use ($owner, $material): PdfFile {
            $pdf = PdfFile::factory()->create(['created_by' => $owner->id, 'is_public' => true]);
            // The factory's PDF fixture is inspected when the resource is created,
            // so explicitly clear that detected value for the unknown-page case.
            $pdf->page_count = $pages;
            $pdf->save();
            $material->resources()->attach($pdf->id);
            return $pdf;
        });

        $dataset = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'ocr'])
            ->assertCreated()->assertJsonPath('dataset.target_material_count', 0)
            ->assertJsonPath('dataset.target_resource_count', 0)
            ->assertJsonPath('dataset.quotas.pdf_pages.target', 300)->json('dataset');
        $assigned = $this->postJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/assign', [
            'material_ids' => [$material->id], 'resource_ids' => [], 'expected_version' => 1, 'include_private' => false,
        ])->assertOk()->assertJsonPath('dataset.status', 'draft')
            ->assertJsonPath('dataset.quotas.pdf_pages.actual', 301)
            ->assertJsonPath('dataset.unknown_page_count', 1)->json('dataset');

        $this->putJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/resources/'.$pdfs[0]->id.'/document-type', [
            'expected_version' => $assigned['version'], 'document_type' => 'invalid',
        ])->assertUnprocessable();

        $this->putJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/resources/'.$pdfs[0]->id.'/document-type', [
            'expected_version' => 1, 'document_type' => 'book',
        ])->assertConflict();

        foreach (['book', 'worksheet', 'presentation'] as $index => $type) {
            $assigned = $this->putJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/resources/'.$pdfs[$index]->id.'/document-type', [
                'expected_version' => $assigned['version'], 'document_type' => $type,
            ])->assertOk()->json('dataset');
        }
        $this->assertSame('ready', $assigned['status']);
        $this->assertSame(1, $assigned['quotas']['presentation']['actual']);

        Passport::actingAs(User::factory()->create());
        $this->putJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/resources/'.$pdfs[0]->id.'/document-type', [
            'expected_version' => $assigned['version'], 'document_type' => 'book',
        ])->assertNotFound();
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

        $this->postJson('/api/v2/admin/context-search/datasets/preview', ['dataset' => $dataset['id'], 'material_ids' => [$firstMaterial->id], 'resource_ids' => []])
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

    public function test_ocr_load_and_capacity_memberships_overlap_while_calibration_and_acceptance_remain_disjoint(): void
    {
        $this->asAdmin();
        [$calibrationMaterial] = $this->connectedSources();
        [$acceptanceMaterial] = $this->connectedSources();
        $calibration = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'calibration'])->assertCreated()->json('dataset');
        $acceptance = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'acceptance'])->assertCreated()->json('dataset');
        $ocr = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'ocr'])->assertCreated()->json('dataset');
        $load = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'load'])->assertCreated()->json('dataset');
        $capacity = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'capacity'])->assertCreated()->json('dataset');
        $calibrationPayload = ['material_ids' => [$calibrationMaterial->id], 'resource_ids' => [], 'expected_version' => 1, 'include_private' => false];
        $acceptancePayload = ['material_ids' => [$acceptanceMaterial->id], 'resource_ids' => [], 'expected_version' => 1, 'include_private' => false];

        $this->postJson('/api/v2/admin/context-search/datasets/'.$calibration['id'].'/assign', $calibrationPayload)->assertOk();
        $this->postJson('/api/v2/admin/context-search/datasets/preview', [
            'dataset' => $ocr['id'], 'material_ids' => [$calibrationMaterial->id], 'resource_ids' => [],
        ])->assertOk()->assertJsonCount(0, 'preview.conflicts');
        $this->postJson('/api/v2/admin/context-search/datasets/'.$ocr['id'].'/assign', [
            ...$calibrationPayload, 'expected_version' => 1,
        ])->assertOk();

        $this->getJson('/api/v2/admin/context-search/datasets/candidates?dataset='.$load['id'].'&per_page=100')
            ->assertOk()->assertJsonPath('data.0.assignment.overlap_allowed', true)
            ->assertJsonCount(2, 'data.0.assignment.memberships');

        $this->postJson('/api/v2/admin/context-search/datasets/'.$acceptance['id'].'/assign', $acceptancePayload)->assertOk();
        $this->postJson('/api/v2/admin/context-search/datasets/'.$ocr['id'].'/assign', [
            ...$acceptancePayload, 'expected_version' => 2,
        ])->assertOk();

        $this->postJson('/api/v2/admin/context-search/datasets/'.$load['id'].'/assign', [
            ...$calibrationPayload, 'expected_version' => 1,
        ])->assertOk();
        $this->postJson('/api/v2/admin/context-search/datasets/'.$capacity['id'].'/assign', [
            ...$calibrationPayload, 'expected_version' => 1,
        ])->assertOk();
        $this->postJson('/api/v2/admin/context-search/datasets/'.$capacity['id'].'/assign', [
            ...$acceptancePayload, 'expected_version' => 2,
        ])->assertOk();
        $this->postJson('/api/v2/admin/context-search/datasets/'.$acceptance['id'].'/assign', [
            ...$calibrationPayload, 'expected_version' => 2,
        ])->assertUnprocessable();
        $this->postJson('/api/v2/admin/context-search/datasets/'.$load['id'].'/assign', [
            ...$acceptancePayload, 'expected_version' => 2,
        ])->assertOk();
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

    public function test_candidates_can_be_limited_to_a_bundle_or_user_materials(): void
    {
        $this->asAdmin();
        $owner = User::factory()->create();
        $bundle = Bundle::factory()->create(['name' => 'Prüf-Bundle']);
        $otherBundle = Bundle::factory()->create(['name' => 'Anderes Bundle']);

        $bundleMaterial = $this->materialWithText($owner, 'Aus dem Prüf-Bundle');
        $userMaterial = $this->materialWithText($owner, 'Eigenes Material');
        $otherMaterial = $this->materialWithText($owner, 'Aus einem anderen Bundle');
        ForeignMaterialId::query()->create(['material_id' => $bundleMaterial->id, 'foreign_id' => 'bundle-material', 'user_id' => $owner->id, 'bundle_id' => $bundle->id]);
        ForeignMaterialId::query()->create(['material_id' => $otherMaterial->id, 'foreign_id' => 'other-material', 'user_id' => $owner->id, 'bundle_id' => $otherBundle->id]);

        $this->getJson('/api/v2/admin/context-search/datasets/candidates?bundle='.$bundle->id)
            ->assertOk()
            ->assertJsonPath('data.0.id', $bundleMaterial->id)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $bundle->id, 'name' => 'Prüf-Bundle']);

        $this->getJson('/api/v2/admin/context-search/datasets/candidates?bundle=user')
            ->assertOk()
            ->assertJsonPath('data.0.id', $userMaterial->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_bundle_candidates_are_distinct_and_paginated_by_material(): void
    {
        $this->asAdmin();
        $owner = User::factory()->create();
        $bundle = Bundle::factory()->create();
        $first = $this->materialWithText($owner, 'Erstes Material');
        $second = $this->materialWithText($owner, 'Zweites Material');
        $extraText = Text::factory()->create(['created_by' => $owner->id, 'is_public' => true, 'content' => 'Weiterer Text']);
        $first->resources()->attach($extraText->id);
        ForeignMaterialId::query()->create(['material_id' => $first->id, 'foreign_id' => 'first-a', 'user_id' => $owner->id, 'bundle_id' => $bundle->id]);
        ForeignMaterialId::query()->create(['material_id' => $first->id, 'foreign_id' => 'first-b', 'user_id' => $owner->id, 'bundle_id' => $bundle->id]);
        ForeignMaterialId::query()->create(['material_id' => $second->id, 'foreign_id' => 'second', 'user_id' => $owner->id, 'bundle_id' => $bundle->id]);

        $this->getJson('/api/v2/admin/context-search/datasets/candidates?type=text&bundle='.$bundle->id.'&per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonCount(1, 'data.0.resources');
    }

    public function test_dataset_overview_counts_existing_members_and_quotas_across_datasets(): void
    {
        $this->asAdmin();
        $owner = User::factory()->create();
        $publicMaterial = $this->materialWithText($owner, 'Öffentlich');
        $publicText = $publicMaterial->resources()->firstOrFail();
        $privateMaterial = Material::factory()->privatelyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
        $privateText = Text::factory()->create(['created_by' => $owner->id, 'is_public' => false, 'content' => 'Privater Testtext']);
        $privateMaterial->resources()->attach($privateText->id);
        $curation = app(EvaluationDatasetCurationService::class);
        $calibration = $curation->create('calibration');
        $calibration->update(['target_material_count' => 2, 'target_resource_count' => 2, 'target_quotas' => ['text' => 2, 'public' => 1, 'private' => 1]]);
        $acceptance = $curation->create('acceptance');
        $acceptance->update(['target_material_count' => 1, 'target_resource_count' => 1, 'target_quotas' => ['text' => 1]]);
        foreach ([['material', $publicMaterial->id], ['resource', $publicText->id], ['material', $privateMaterial->id], ['resource', $privateText->id]] as [$type, $id]) {
            ContextSearchEvaluationDatasetMember::query()->create(['dataset_id' => $calibration->id, 'member_type' => $type, 'member_id' => $id]);
        }

        $datasets = collect($this->getJson('/api/v2/admin/context-search/datasets')->assertOk()->json('datasets'))->keyBy('id');
        $this->assertSame(2, $datasets[$calibration->id]['material_count']);
        $this->assertSame(2, $datasets[$calibration->id]['resource_count']);
        $this->assertSame(2, $datasets[$calibration->id]['quotas']['text']['actual']);
        $this->assertSame(1, $datasets[$calibration->id]['quotas']['public']['actual']);
        $this->assertSame(1, $datasets[$calibration->id]['quotas']['private']['actual']);
        $this->assertSame(0, $datasets[$calibration->id]['materials_remaining']);
        $this->assertSame(0, $datasets[$acceptance->id]['material_count']);
        $this->assertSame(1, $datasets[$acceptance->id]['materials_remaining']);
    }

    public function test_an_assigned_connected_block_can_be_removed_from_a_mutable_dataset(): void
    {
        $this->asAdmin();
        [$material] = $this->connectedSources();
        $dataset = $this->postJson('/api/v2/admin/context-search/datasets', ['purpose' => 'calibration'])->assertCreated()->json('dataset');
        $assigned = $this->postJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/assign', [
            'material_ids' => [$material->id], 'resource_ids' => [], 'expected_version' => 1, 'include_private' => false,
        ])->assertOk()->json('dataset');

        $this->deleteJson('/api/v2/admin/context-search/datasets/'.$dataset['id'].'/members/material/'.$material->id, ['expected_version' => $assigned['version']])
            ->assertOk()
            ->assertJsonPath('dataset.material_count', 0)
            ->assertJsonPath('dataset.resource_count', 0);

        $this->assertDatabaseCount('context_search_evaluation_dataset_members', 0);
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

    private function materialWithText(User $owner, string $title): Material
    {
        $material = Material::factory()->publiclyVisible()->create(['title' => $title, 'created_by' => $owner->id, 'modified_by' => $owner->id]);
        $text = Text::factory()->create(['created_by' => $owner->id, 'is_public' => true, 'content' => $title]);
        $material->resources()->attach($text->id);

        return $material;
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
