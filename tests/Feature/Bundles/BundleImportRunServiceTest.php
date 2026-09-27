<?php

namespace Tests\Feature\Bundles;

use App\Enums\BundleImportOperation;
use App\Exceptions\Bundles\BundleImportConflictException;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use App\Services\Bundles\BundleImportRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleImportRunServiceTest extends TestCase {
	use RefreshDatabase;

	public function test_same_operation_and_target_version_reuse_the_active_run(): void {
		$bundle = Bundle::factory()->create();
		$service = resolve(BundleImportRunService::class);

		$firstRun = $service->start($bundle, BundleImportOperation::Update, '2.0.0');
		$secondRun = $service->start($bundle, BundleImportOperation::Update, '2.0.0');

		$this->assertSame($firstRun->id, $secondRun->id);
		$this->assertDatabaseCount('bundle_import_runs', 1);
	}

	public function test_conflicting_active_operation_is_rejected_without_creating_another_run(): void {
		$bundle = Bundle::factory()->create();
		$service = resolve(BundleImportRunService::class);
		$activeRun = $service->start($bundle, BundleImportOperation::Update, '2.0.0');

		try {
			$service->start($bundle, BundleImportOperation::Uninstall, NULL);
			$this->fail('Expected a conflicting operation to be rejected.');
		} catch (BundleImportConflictException $exception) {
			$this->assertSame($activeRun->id, $exception->activeRun->id);
		}

		$this->assertDatabaseCount('bundle_import_runs', 1);
	}
}
