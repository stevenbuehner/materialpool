<?php

namespace Tests\Feature\Bundles;

use App\Enums\BundleImportOperation;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use App\Models\User;
use App\Services\Bundles\BundleImportOrchestrator;
use App\Services\Bundles\BundlesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Mockery;
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

	public function test_update_initialization_records_install_for_an_uninstalled_bundle_and_update_for_an_installed_bundle(): void {
		$user = User::factory()->create(['is_admin' => TRUE]);
		$uninstalledBundle = Bundle::factory()->create(['is_installed' => FALSE]);
		$installedBundle = Bundle::factory()->create(['is_installed' => TRUE, 'installed_version' => '1.0.0']);
		$bundles = Mockery::mock(BundlesService::class);
		$bundles->shouldReceive('getLocalBundleData')->twice()->andReturn(['version' => '2.0.0']);
		$orchestrator = Mockery::mock(BundleImportOrchestrator::class);
		$orchestrator->shouldReceive('start')->twice();
		$this->app->instance(BundlesService::class, $bundles);
		$this->app->instance(BundleImportOrchestrator::class, $orchestrator);
		Passport::actingAs($user);

		$this->postJson(route('api.v1.bundles.update.init', $uninstalledBundle))
			->assertAccepted()
			->assertJsonPath('run.operation', BundleImportOperation::Install->value);
		$this->postJson(route('api.v1.bundles.update.init', $installedBundle))
			->assertAccepted()
			->assertJsonPath('run.operation', BundleImportOperation::Update->value);

		$this->assertDatabaseHas('bundle_import_runs', ['bundle_id' => $uninstalledBundle->id, 'operation' => BundleImportOperation::Install->value]);
		$this->assertDatabaseHas('bundle_import_runs', ['bundle_id' => $installedBundle->id, 'operation' => BundleImportOperation::Update->value]);
	}

	public function test_bundle_manager_can_read_the_latest_terminal_import_summary_after_a_reload(): void {
		$user = User::factory()->create(['is_admin' => TRUE]);
		$bundle = Bundle::factory()->create();
		$run = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'status' => 'succeeded',
			'active_slot' => NULL,
			'target_version' => '2.0.0',
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
			'result_summary' => [
				'materials' => ['successful' => 4, 'skipped' => 1, 'failed' => 0],
				'resources' => ['successful' => 8, 'skipped' => 2, 'failed' => 0],
				'removed' => ['materials' => ['successful' => 0, 'skipped' => 0, 'failed' => 0], 'resources' => ['successful' => 0, 'skipped' => 0, 'failed' => 0]],
				'errors' => ['count' => 0, 'code' => NULL],
			],
		]);
		Passport::actingAs($user);

		$this->getJson(route('api.v1.bundles.runs.latest', $bundle))
			->assertOk()
			->assertJsonPath('id', $run->id)
			->assertJsonPath('summary.materials.successful', 4)
			->assertJsonPath('summary.resources.skipped', 2)
			->assertJsonPath('summary.errors.count', 0);
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
