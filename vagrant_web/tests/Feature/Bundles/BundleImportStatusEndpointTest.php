<?php

namespace Tests\Feature\Bundles;

use App\Enums\BundleImportOperation;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class BundleImportStatusEndpointTest extends TestCase {
	use RefreshDatabase;

	public function test_bundle_manager_can_read_its_import_run_status_but_not_another_bundles_run(): void {
		$user = User::factory()->create(['is_admin' => TRUE]);
		$bundle = Bundle::factory()->create();
		$otherBundle = Bundle::factory()->create();
		$run = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'target_version' => '2.0.0',
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
		]);
		Passport::actingAs($user);

		$this->getJson(route('api.v1.bundles.runs.status', [$bundle, $run]))
			->assertOk()
			->assertJsonPath('id', $run->id)
			->assertJsonPath('status', 'pending')
			->assertJsonPath('progress.total', 0);
		$this->getJson(route('api.v1.bundles.runs.status', [$otherBundle, $run]))->assertNotFound();
	}

	public function test_bundle_manager_can_resume_by_reading_the_active_run_without_its_id(): void {
		$user = User::factory()->create(['is_admin' => TRUE]);
		$bundle = Bundle::factory()->create();
		$run = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'target_version' => '2.0.0',
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
		]);
		Passport::actingAs($user);

		$this->getJson(route('api.v1.bundles.runs.active', $bundle))
			->assertOk()
			->assertJsonPath('id', $run->id);
	}

	public function test_bundle_manager_receives_a_data_minimized_summary_of_skipped_import_records(): void {
		$user = User::factory()->create(['is_admin' => TRUE]);
		$bundle = Bundle::factory()->create();
		$run = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'target_version' => '2.0.0',
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
			'source_warnings' => [
				'material_ids' => [17],
				'file_uuids' => ['private-source-file'],
				'summary' => ['skipped_materials' => 1, 'skipped_resources' => 2, 'reasons' => ['missing_file' => 1, 'only_referenced_by_skipped_material' => 1]],
			],
		]);
		Passport::actingAs($user);

		$this->getJson(route('api.v1.bundles.runs.status', [$bundle, $run]))
			->assertOk()
			->assertJsonPath('warnings.skipped_materials', 1)
			->assertJsonPath('warnings.skipped_resources', 2)
			->assertJsonPath('warnings.reasons.missing_file', 1)
			->assertJsonMissingPath('warnings.material_ids')
			->assertJsonMissingPath('warnings.file_uuids');
	}
}
