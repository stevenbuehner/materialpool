<?php

namespace Tests\Feature\Admin;

use App\Models\Bundle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

class QueueOverviewTest extends TestCase {
	use RefreshDatabase;

	public function test_only_global_admin_can_read_queue_overview(): void {
		$this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'jobs']))->assertUnauthorized();
		Passport::actingAs(User::factory()->create());
		$this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'jobs']))->assertNotFound();
	}

	public function test_jobs_are_classified_and_payload_is_not_exposed(): void {
		Passport::actingAs(User::factory()->create(['is_admin' => true]));
		$now = now()->timestamp;
		foreach ([
			['default', null, $now - 5, 'App\\Jobs\\Example'],
			['default', null, $now + 60, 'App\\Jobs\\Example'],
			['resource-previews-low', $now - 3, $now - 10, 'App\\Jobs\\Preview'],
		] as [$queue, $reservedAt, $availableAt, $type]) {
			DB::table('jobs')->insert([
				'queue' => $queue,
				'payload' => json_encode(['displayName' => $type, 'secret' => 'never-expose']),
				'attempts' => 1,
				'reserved_at' => $reservedAt,
				'available_at' => $availableAt,
				'created_at' => $now - 30,
			]);
		}

		$response = $this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'jobs', 'queue' => 'default', 'status' => 'waiting']));
		$response->assertOk()->assertJsonPath('summary.waiting', 1)->assertJsonPath('summary.delayed', 1)
			->assertJsonPath('summary.reserved', 1)->assertJsonPath('data.total', 1)
			->assertJsonPath('data.data.0.type', 'App\\Jobs\\Example');
		$this->assertStringNotContainsString('never-expose', $response->getContent());
		$this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'jobs', 'type' => 'Preview']))
			->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.queue', 'resource-previews-low');
	}

	public function test_failed_jobs_and_batches_use_existing_data_without_exposing_exception_or_options(): void {
		Passport::actingAs(User::factory()->create(['is_admin' => true]));
		DB::table('failed_jobs')->insert([
			'uuid' => '00000000-0000-0000-0000-000000000001',
			'connection' => 'database',
			'queue' => 'default',
			'payload' => json_encode(['displayName' => 'App\\Jobs\\Example', 'secret' => 'never-expose']),
			'exception' => 'private-exception',
			'failed_at' => now(),
		]);
		$this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'failed']))
			->assertOk()->assertJsonPath('data.data.0.queue', 'default')
			->assertDontSee('private-exception')->assertDontSee('never-expose');

		$bundle = Bundle::factory()->create();
		$batchId = '00000000-0000-0000-0000-000000000002';
		DB::table('job_batches')->insert([
			'id' => $batchId, 'name' => 'bundle:test', 'total_jobs' => 4, 'pending_jobs' => 2,
			'failed_jobs' => 0, 'failed_job_ids' => '[]', 'options' => 'private-options',
			'created_at' => now()->timestamp, 'cancelled_at' => null, 'finished_at' => null,
		]);
		DB::table('bundle_import_runs')->insert([
			'id' => '00000000-0000-0000-0000-000000000003', 'bundle_id' => $bundle->id,
			'operation' => 'install', 'status' => 'running', 'phase' => 'validating',
			'queue_name' => 'bundle_' . $bundle->id . '_queue', 'validation_batch_id' => $batchId,
			'created_at' => now(), 'updated_at' => now(),
		]);
		$this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'batches']))
			->assertOk()->assertJsonPath('data.data.0.queue', 'bundle_' . $bundle->id . '_queue')
			->assertJsonPath('data.data.0.pending_jobs', 2)->assertDontSee('private-options');
	}

	public function test_invalid_filters_are_rejected(): void {
		Passport::actingAs(User::factory()->create(['is_admin' => true]));
		$this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'invalid']))->assertUnprocessable();
		$this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'jobs', 'status' => 'finished']))->assertUnprocessable();
	}
}
