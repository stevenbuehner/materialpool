<?php

namespace Tests\Feature\Bundles;

use App\Enums\BundleImportOperation;
use App\Enums\BundleImportStatus;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BundleImportRunSchemaTest extends TestCase {
	use RefreshDatabase;

	public function test_laravel_job_batches_and_bundle_import_runs_are_migrated(): void {
		$this->assertTrue(Schema::hasTable('job_batches'));
		$this->assertTrue(Schema::hasTable('bundle_import_runs'));
		$this->assertTrue(Schema::hasColumns('bundle_import_runs', [
			'id', 'bundle_id', 'operation', 'status', 'phase', 'active_slot', 'queue_name', 'current_batch_id', 'source_warnings', 'result_summary', 'failure_code', 'finished_at',
		]));
	}

	public function test_only_one_active_import_run_can_exist_for_a_bundle(): void {
		$bundle = $this->bundle();

		BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
		]);

		$this->expectException(QueryException::class);

		BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Uninstall,
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
		]);
	}

	public function test_a_terminal_import_run_releases_the_active_slot_for_a_new_run(): void {
		$bundle = $this->bundle();
		$firstRun = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
		]);

		$firstRun->update([
			'status' => BundleImportStatus::Succeeded,
			'active_slot' => NULL,
			'finished_at' => now(),
		]);

		$secondRun = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Uninstall,
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
		]);

		$this->assertNotSame($firstRun->id, $secondRun->id);
		$this->assertSame(1, $secondRun->active_slot);
	}

	private function bundle(): Bundle {
		return Bundle::query()->create([
			'name' => 'Import run schema bundle',
			'description' => 'Schema contract',
			'uuid' => 'bundle-import-run-schema-' . uniqid(),
			'container_root' => 'bundle-import-run-schema',
		]);
	}
}
