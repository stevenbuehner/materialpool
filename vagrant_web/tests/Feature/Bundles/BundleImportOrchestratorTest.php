<?php

namespace Tests\Feature\Bundles;

use App\Enums\BundleImportOperation;
use App\Enums\BundleImportPhase;
use App\Enums\BundleImportStatus;
use App\Jobs\Bundle\AdvanceBundleImportPhase;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use App\Services\Bundles\BundleImportOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BundleImportOrchestratorTest extends TestCase {
	use RefreshDatabase;

	public function test_start_dispatches_a_validation_batch_and_persists_its_identity(): void {
		Bus::fake();
		$bundle = Bundle::factory()->create();
		$run = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'target_version' => '2.0.0',
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
		]);

		resolve(BundleImportOrchestrator::class)->start($run);

		$run->refresh();
		$this->assertSame(BundleImportStatus::Running, $run->status);
		$this->assertSame(BundleImportPhase::Validating, $run->phase);
		$this->assertNotNull($run->validation_batch_id);
		$this->assertSame($run->validation_batch_id, $run->current_batch_id);
		Bus::assertBatched(fn($batch) => $batch->name === 'bundle:' . $bundle->id . ':run:' . $run->id . ':validating');
		Bus::assertDispatched(AdvanceBundleImportPhase::class, function (AdvanceBundleImportPhase $job) use ($run): bool {
			return $job->queue === $run->queue_name && $job->connection === 'database';
		});
	}

	public function test_successful_terminal_run_persists_processed_and_skipped_import_counts(): void {
		$bundle = Bundle::factory()->create(['is_installed' => FALSE, 'installed_version' => NULL, 'update_available' => TRUE]);
		$resourceBatch = $this->finishedBatch(4);
		$materialBatch = $this->finishedBatch(3);
		$run = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Install,
			'status' => BundleImportStatus::Running,
			'phase' => BundleImportPhase::UpsertingMaterials,
			'target_version' => '2.0.0',
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
			'resources_batch_id' => $resourceBatch,
			'materials_batch_id' => $materialBatch,
			'source_warnings' => ['material_ids' => [10], 'file_uuids' => ['file-1', 'file-2']],
		]);

		resolve(BundleImportOrchestrator::class)->phaseSucceeded($run->id);

		$run->refresh();
		$this->assertSame(BundleImportStatus::Succeeded, $run->status);
		$this->assertSame(3, $run->result_summary['materials']['successful']);
		$this->assertSame(1, $run->result_summary['materials']['skipped']);
		$this->assertSame(0, $run->result_summary['materials']['failed']);
		$this->assertSame(4, $run->result_summary['resources']['successful']);
		$this->assertSame(2, $run->result_summary['resources']['skipped']);
		$this->assertSame(0, $run->result_summary['resources']['failed']);
		$this->assertSame(0, $run->result_summary['errors']['count']);
		$this->assertNull($run->result_summary['errors']['code']);
		$this->assertTrue($bundle->fresh()->is_installed);
		$this->assertSame('2.0.0', $bundle->fresh()->installed_version);
		$this->assertFalse($bundle->fresh()->update_available);
	}

	public function test_successful_uninstall_marks_the_bundle_as_not_installed_and_persists_removal_counts(): void {
		$bundle = Bundle::factory()->create(['is_installed' => TRUE, 'installed_version' => '2.0.0', 'update_available' => FALSE]);
		$resourceBatch = $this->finishedBatch(2);
		$materialBatch = $this->finishedBatch(3);
		$run = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Uninstall,
			'status' => BundleImportStatus::Running,
			'phase' => BundleImportPhase::UpsertingMaterials,
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
			'delete_materials_batch_id' => $materialBatch,
			'delete_resources_batch_id' => $resourceBatch,
		]);

		resolve(BundleImportOrchestrator::class)->phaseSucceeded($run->id);

		$run->refresh();
		$this->assertSame(BundleImportStatus::Succeeded, $run->status);
		$this->assertSame(3, $run->result_summary['removed']['materials']['successful']);
		$this->assertSame(2, $run->result_summary['removed']['resources']['successful']);
		$this->assertFalse($bundle->fresh()->is_installed);
		$this->assertNull($bundle->fresh()->installed_version);
		$this->assertTrue($bundle->fresh()->update_available);
	}

	public function test_failed_terminal_run_persists_failed_job_count_and_error_code(): void {
		$bundle = Bundle::factory()->create();
		$resourceBatch = $this->finishedBatch(3, 1);
		$run = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'status' => BundleImportStatus::Running,
			'phase' => BundleImportPhase::UpsertingResources,
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
			'resources_batch_id' => $resourceBatch,
		]);

		resolve(BundleImportOrchestrator::class)->fail($run->id, 'bundle_resource_import_failed');

		$run->refresh();
		$this->assertSame(BundleImportStatus::Failed, $run->status);
		$this->assertSame(2, $run->result_summary['resources']['successful']);
		$this->assertSame(0, $run->result_summary['resources']['skipped']);
		$this->assertSame(1, $run->result_summary['resources']['failed']);
		$this->assertSame(1, $run->result_summary['errors']['count']);
		$this->assertSame('bundle_resource_import_failed', $run->result_summary['errors']['code']);
	}

	private function finishedBatch(int $totalJobs, int $failedJobs = 0): string {
		$batch = Bus::batch([])->dispatch();
		DB::table('job_batches')->where('id', $batch->id)->update([
			'total_jobs' => $totalJobs,
			'pending_jobs' => 0,
			'failed_jobs' => $failedJobs,
			'finished_at' => now()->getTimestamp(),
		]);

		return $batch->id;
	}
}
