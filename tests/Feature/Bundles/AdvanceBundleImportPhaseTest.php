<?php

namespace Tests\Feature\Bundles;

use App\Jobs\Bundle\AdvanceBundleImportPhase;
use Tests\TestCase;

class AdvanceBundleImportPhaseTest extends TestCase {
	public function test_it_keeps_retrying_for_a_bounded_period_until_its_batch_is_finished(): void {
		$job = new AdvanceBundleImportPhase('run-id', 'batch-id');

		$this->assertSame(120, $job->timeout);
		$this->assertSame(0, $job->tries);
		$this->assertGreaterThan(now()->addMinutes(29), $job->retryUntil());
	}
}
