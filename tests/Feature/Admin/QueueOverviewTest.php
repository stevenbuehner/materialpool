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
		$this->getJson(route('api.v2.admin.queue-overview.jobs.show', 1))->assertUnauthorized();
		Passport::actingAs(User::factory()->create());
		$this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'jobs']))->assertNotFound();
		$this->getJson(route('api.v2.admin.queue-overview.jobs.show', 1))->assertNotFound();
	}

	public function test_job_details_are_loaded_individually_with_readable_redacted_data(): void {
		Passport::actingAs(User::factory()->create(['is_admin' => true]));
		$id = DB::table('jobs')->insertGetId([
			'queue' => 'default',
			'payload' => json_encode([
				'displayName' => 'App\\Jobs\\Example',
				'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
				'data' => ['commandName' => 'App\\Jobs\\Example', 'command' => serialize((object) ['title' => 'Grüße', 'apiToken' => 'private-token', 'nested' => ['count' => 3]])],
				'secret' => 'private-payload',
			]),
			'attempts' => 2, 'reserved_at' => null, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp,
		]);

		$this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'jobs']))
			->assertOk()->assertDontSee('private-payload')->assertDontSee('private-token');
		$this->getJson(route('api.v2.admin.queue-overview.jobs.show', $id))
			->assertOk()->assertJsonPath('id', $id)->assertJsonPath('payload.data.jobData.title', 'Grüße')
			->assertJsonPath('payload.data.jobData.nested.count', 3)
			->assertJsonPath('payload.data.jobData.apiToken', __('pool.queue-redacted'))
			->assertJsonPath('payload.secret', __('pool.queue-redacted'))
			->assertJsonMissingPath('payload.data.command')
			->assertDontSee('private-payload')->assertDontSee('private-token')
			->assertHeader('Cache-Control', 'no-store, private');
		$this->getJson(route('api.v2.admin.queue-overview.jobs.show', $id + 1))->assertNotFound();
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

	public function test_only_global_admin_can_read_or_change_failed_jobs(): void {
		$uuid = '00000000-0000-0000-0000-000000000010';
		$this->getJson(route('api.v2.admin.queue-overview.failed.show', $uuid))->assertUnauthorized();
		$this->postJson(route('api.v2.admin.queue-overview.failed.retry', $uuid))->assertUnauthorized();
		$this->deleteJson(route('api.v2.admin.queue-overview.failed.delete', $uuid))->assertUnauthorized();

		Passport::actingAs(User::factory()->create());
		$this->getJson(route('api.v2.admin.queue-overview.failed.show', $uuid))->assertNotFound();
		$this->postJson(route('api.v2.admin.queue-overview.failed.retry', $uuid))->assertNotFound();
		$this->deleteJson(route('api.v2.admin.queue-overview.failed.delete', $uuid))->assertNotFound();
	}

	public function test_failed_job_details_are_loaded_individually_and_can_be_deleted(): void {
		Passport::actingAs(User::factory()->create(['is_admin' => true]));
		$uuid = '00000000-0000-0000-0000-000000000011';
		DB::table('failed_jobs')->insert([
			'uuid' => $uuid, 'connection' => 'database', 'queue' => 'default',
			'payload' => json_encode(['displayName' => 'App\\Jobs\\Example', 'secret' => 'private-payload']),
			'exception' => 'private-exception', 'failed_at' => now(),
		]);
		DB::table('failed_jobs')->insert([
			'uuid' => '00000000-0000-0000-0000-000000000015', 'connection' => 'database', 'queue' => 'other',
			'payload' => '{}', 'exception' => 'other failure', 'failed_at' => now(),
		]);

		$this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'failed']))
			->assertOk()->assertDontSee('private-payload')->assertDontSee('private-exception');
		$this->getJson(route('api.v2.admin.queue-overview.failed.show', $uuid))
			->assertOk()->assertJsonPath('uuid', $uuid)->assertJsonPath('exception', 'private-exception')
			->assertJsonPath('payload.secret', __('pool.queue-redacted'))->assertDontSee('private-payload')
			->assertHeader('Cache-Control', 'no-store, private');
		$this->deleteJson(route('api.v2.admin.queue-overview.failed.delete', $uuid))->assertOk();
		$this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
		$this->assertDatabaseHas('failed_jobs', ['uuid' => '00000000-0000-0000-0000-000000000015']);
		$this->getJson(route('api.v2.admin.queue-overview.failed.show', $uuid))->assertNotFound();
		$this->deleteJson(route('api.v2.admin.queue-overview.failed.delete', $uuid))->assertNotFound();
	}

	public function test_a_single_failed_job_can_be_retried_on_its_original_queue(): void {
		Passport::actingAs(User::factory()->create(['is_admin' => true]));
		$uuid = '00000000-0000-0000-0000-000000000012';
		DB::table('failed_jobs')->insert([
			'uuid' => $uuid, 'connection' => 'database', 'queue' => 'resource-previews-low',
			'payload' => json_encode(['uuid' => $uuid, 'job' => 'App\\Jobs\\Example', 'displayName' => 'App\\Jobs\\Example']),
			'exception' => 'previous failure', 'failed_at' => now(),
		]);

		$this->postJson(route('api.v2.admin.queue-overview.failed.retry', $uuid))->assertOk();
		$this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
		$this->assertDatabaseHas('jobs', ['queue' => 'resource-previews-low']);
		$this->postJson(route('api.v2.admin.queue-overview.failed.retry', $uuid))->assertNotFound();
	}

	public function test_context_search_failed_jobs_cannot_be_retried(): void {
		Passport::actingAs(User::factory()->create(['is_admin' => true]));
		$uuid = '00000000-0000-0000-0000-000000000013';
		DB::table('failed_jobs')->insert([
			'uuid' => $uuid, 'connection' => 'context_search', 'queue' => 'context-search-extraction',
			'payload' => json_encode(['uuid' => $uuid, 'displayName' => 'App\\Jobs\\Example']),
			'exception' => 'previous failure', 'failed_at' => now(),
		]);

		$this->getJson(route('api.v2.admin.queue-overview.index', ['tab' => 'failed']))->assertJsonPath('data.data.0.can_retry', false);
		$this->postJson(route('api.v2.admin.queue-overview.failed.retry', $uuid))->assertStatus(409);
		$this->assertDatabaseHas('failed_jobs', ['uuid' => $uuid]);
		$this->assertDatabaseCount('jobs', 0);
	}

	public function test_invalid_failed_payload_is_preserved_instead_of_queued(): void {
		Passport::actingAs(User::factory()->create(['is_admin' => true]));
		$uuid = '00000000-0000-0000-0000-000000000014';
		DB::table('failed_jobs')->insert([
			'uuid' => $uuid, 'connection' => 'database', 'queue' => 'default',
			'payload' => '{invalid-json', 'exception' => 'previous failure', 'failed_at' => now(),
		]);

		$this->postJson(route('api.v2.admin.queue-overview.failed.retry', $uuid))->assertStatus(409);
		$this->assertDatabaseHas('failed_jobs', ['uuid' => $uuid]);
		$this->assertDatabaseCount('jobs', 0);
	}
}
