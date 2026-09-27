<?php

namespace Tests\Feature\Bundles;

use App\Enums\BundleImportOperation;
use App\Enums\BundleImportPhase;
use App\Enums\BundleImportStatus;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkBundleQueueCommandTest extends TestCase {
	use RefreshDatabase;

	public function test_dry_run_reports_an_existing_active_run_without_initializing_one(): void {
		$bundle = Bundle::factory()->create();
		$run = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'status' => BundleImportStatus::Running,
			'phase' => BundleImportPhase::UpsertingResources,
			'target_version' => '2.0.0',
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
		]);

		$this->artisan('bundles:work', ['bundle' => $bundle->id, '--dry-run' => true])
			->expectsOutputToContain($run->id)
			->assertExitCode(0);
		$this->assertDatabaseCount('bundle_import_runs', 1);
	}

	public function test_refuses_to_start_a_worker_when_no_run_is_active(): void {
		$bundle = Bundle::factory()->create();

		$this->artisan('bundles:work', ['bundle' => $bundle->id, '--dry-run' => true])
			->expectsOutputToContain('keinen aktiven Importlauf')
			->assertExitCode(1);
	}
}
