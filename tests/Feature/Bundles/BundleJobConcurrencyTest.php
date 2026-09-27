<?php

namespace Tests\Feature\Bundles;

use App\Jobs\Bundle\DeleteMaterialIfNeeded;
use App\Jobs\Bundle\DeleteResourceIfNeeded;
use App\Jobs\Bundle\InsertOrUpdateMaterial;
use App\Jobs\Bundle\InsertOrUpdateResource;
use App\Jobs\Bundle\ValidateBundleSource;
use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Tests\TestCase;

class BundleJobConcurrencyTest extends TestCase {
	public function test_mutating_bundle_jobs_use_timeout_and_overlap_protection(): void {
		$bundle = Bundle::factory()->make(['id' => 7]);
		$foreignMaterialId = new ForeignMaterialId(['id' => 11]);
		$foreignResourceId = new ForeignResourceId(['id' => 13]);
		$file = (object)['uuid' => 'resource-uuid'];
		$material = (object)['uuid' => 'material-uuid'];

		$jobs = [
			new DeleteMaterialIfNeeded($bundle, $foreignMaterialId, '1.0.0'),
			new DeleteResourceIfNeeded($bundle, $foreignResourceId, '1.0.0'),
			new InsertOrUpdateResource($bundle, $file, '1.0.0'),
			new InsertOrUpdateMaterial($bundle, $material, '1.0.0'),
		];

		foreach ($jobs as $job) {
			$this->assertSame(120, $job->timeout);
			$this->assertCount(1, $job->middleware());
			$this->assertInstanceOf(WithoutOverlapping::class, $job->middleware()[0]);
		}
	}

	public function test_source_validation_retries_only_once_after_its_first_attempt(): void {
		$job = new ValidateBundleSource('run-uuid');

		$this->assertSame(2, $job->tries);
		$this->assertSame(5, $job->backoff);
	}
}
