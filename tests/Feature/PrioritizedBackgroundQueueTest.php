<?php

namespace Tests\Feature;

use App\Enums\BundleImportOperation;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use App\Services\Queue\PrioritizedBackgroundQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrioritizedBackgroundQueueTest extends TestCase {
	use RefreshDatabase;

	public function test_ready_jobs_follow_default_bundle_context_and_preview_priority(): void {
		$selector = resolve(PrioritizedBackgroundQueue::class);
		$this->queue('resource-previews-low');
		$this->assertSame('resource-previews-low', $selector->next()['queue']);

		$bundle = Bundle::factory()->create();
		$bundleQueue = 'bundle_' . $bundle->id . '_queue';
		BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'target_version' => '2.0.0',
			'queue_name' => $bundleQueue,
		]);
		$this->queue($bundleQueue);
		$this->queue('material-downloads');
		$this->assertSame($bundleQueue, $selector->next()['queue']);

		$this->queue('default');
		$this->assertNull($selector->next());
		DB::table('jobs')->where('queue', 'default')->delete();
		DB::table('jobs')->where('queue', $bundleQueue)->delete();
		$this->assertSame('material-downloads', $selector->next()['queue']);
		$this->assertSame('material_downloads', $selector->next()['connection']);
		DB::table('jobs')->where('queue', 'material-downloads')->delete();

		config(['context_search.enabled' => true, 'context_search.indexing.dispatch_enabled' => true]);
		$this->queue('context-search-extraction');
		$this->assertSame('context-search-extraction', $selector->next()['queue']);
		$this->assertSame('context_search', $selector->next()['connection']);
		config(['context_search.indexing.dispatch_enabled' => false]);
		$this->assertSame('resource-previews-low', $selector->next()['queue']);
	}

	public function test_delayed_default_job_does_not_block_ready_preview(): void {
		$this->queue('default', time() + 600);
		$this->queue('resource-previews-low');

		$this->assertSame('resource-previews-low', resolve(PrioritizedBackgroundQueue::class)->next()['queue']);
	}

	public function test_inactive_bundle_queue_is_not_consumed(): void {
		$bundle = Bundle::factory()->create();
		$queue = 'bundle_' . $bundle->id . '_queue';
		BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'status' => 'succeeded',
			'active_slot' => NULL,
			'target_version' => '2.0.0',
			'queue_name' => $queue,
		]);
		$this->queue($queue);
		$this->queue('resource-previews-low');

		$this->assertSame('resource-previews-low', resolve(PrioritizedBackgroundQueue::class)->next()['queue']);
	}

	public function test_background_command_requires_the_proxmox_switch(): void {
		config(['queue.prioritized_background_enabled' => FALSE]);

		$this->artisan('queues:work-background', ['--once' => TRUE])->assertExitCode(1);
	}

	public function test_configuration_check_never_selects_a_job(): void {
		config(['queue.prioritized_background_enabled' => TRUE]);
		$this->queue('resource-previews-low');

		$this->artisan('queues:work-background', ['--configuration-only' => TRUE])->assertExitCode(0);
		$this->assertDatabaseHas('jobs', ['queue' => 'resource-previews-low', 'reserved_at' => NULL]);
	}

	private function queue(string $queue, ?int $availableAt = NULL): void {
		DB::table('jobs')->insert([
			'queue' => $queue,
			'payload' => '{}',
			'attempts' => 0,
			'reserved_at' => NULL,
			'available_at' => $availableAt ?? time(),
			'created_at' => time(),
		]);
	}
}
