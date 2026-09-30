<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\DB;

final class PrioritizedBackgroundQueue {
	/** @return array{connection: string, queue: string, timeout: int, tries: int}|null */
	public function next(): ?array {
		if ($this->hasReadyJob('default', 'database')) {
			return NULL;
		}

		$bundleQueues = DB::table('bundle_import_runs')
			->whereNotNull('active_slot')
			->pluck('queue_name')
			->filter(fn ($queue): bool => is_string($queue) && preg_match('/^bundle_[0-9]+_queue$/', $queue) === 1)
			->reject(fn (string $queue): bool => app('queue')->isPaused('database', $queue))
			->all();
		if ($bundleQueues !== []) {
			$bundle = $this->readyJobs('database')->whereIn('queue', $bundleQueues)->orderBy('id')->value('queue');
			if (is_string($bundle)) {
				return ['connection' => 'database', 'queue' => $bundle, 'timeout' => 120, 'tries' => 0];
			}
		}

		if (app()->environment('local', 'testing') && config('context_search.enabled') && config('context_search.indexing.dispatch_enabled') === TRUE) {
			foreach (['upsert_queue', 'embedding_queue', 'queue'] as $key) {
				$queue = (string)config("context_search.indexing.{$key}");
				if ($this->hasReadyJob($queue, 'context_search')) {
					return ['connection' => 'context_search', 'queue' => $queue, 'timeout' => 480, 'tries' => 3];
				}
			}
		}

		if ($this->hasReadyJob('resource-previews-low', 'database')) {
			return ['connection' => 'database', 'queue' => 'resource-previews-low', 'timeout' => 120, 'tries' => 50];
		}

		return NULL;
	}

	private function hasReadyJob(string $queue, string $connection): bool {
		if (app('queue')->isPaused($connection, $queue)) {
			return FALSE;
		}

		return $this->readyJobs($connection)->where('queue', $queue)->exists();
	}

	private function readyJobs(string $connection): \Illuminate\Database\Query\Builder {
		$now = time();
		$retryAfter = (int)config("queue.connections.{$connection}.retry_after");

		return DB::table('jobs')->where(function ($query) use ($now, $retryAfter): void {
			$query->where(function ($available) use ($now): void {
				$available->whereNull('reserved_at')->where('available_at', '<=', $now);
			})->orWhere('reserved_at', '<=', $now - $retryAfter);
		});
	}
}
