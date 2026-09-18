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
}
