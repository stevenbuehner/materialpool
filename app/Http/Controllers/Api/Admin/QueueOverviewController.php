<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\QueueJobDetailsPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QueueOverviewController extends Controller {
	public function index(Request $request): JsonResponse {
		$filters = $request->validate([
			'tab' => ['required', Rule::in(['jobs', 'failed', 'batches'])],
			'queue' => ['sometimes', 'string', 'max:191'],
			'type' => ['sometimes', 'string', 'max:100'],
			'status' => ['sometimes', Rule::in(['waiting', 'delayed', 'reserved'])],
			'page' => ['sometimes', 'integer', 'min:1'],
		]);

		$now = now()->timestamp;
		$summary = [
			'waiting' => DB::table('jobs')->whereNull('reserved_at')->where('available_at', '<=', $now)->count(),
			'delayed' => DB::table('jobs')->whereNull('reserved_at')->where('available_at', '>', $now)->count(),
			'reserved' => DB::table('jobs')->whereNotNull('reserved_at')->count(),
			'failed' => DB::table('failed_jobs')->count(),
		];

		$tab = $filters['tab'];
		$queue = $filters['queue'] ?? null;
		$type = $filters['type'] ?? null;
		$status = $filters['status'] ?? null;
		$page = $filters['page'] ?? 1;

		if ($tab === 'jobs') {
			$query = DB::table('jobs')->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at');
			if ($queue !== null && $queue !== '') $query->where('queue', $queue);
			if ($type !== null && $type !== '') $query->where('payload->displayName', 'like', '%' . $type . '%');
			if ($status === 'waiting') $query->whereNull('reserved_at')->where('available_at', '<=', $now);
			if ($status === 'delayed') $query->whereNull('reserved_at')->where('available_at', '>', $now);
			if ($status === 'reserved') $query->whereNotNull('reserved_at');
			$items = $query->orderBy('queue')->orderByRaw('CASE WHEN reserved_at IS NOT NULL THEN 0 ELSE 1 END')->orderBy('id')->paginate(50, ['*'], 'page', $page);
			$items->getCollection()->transform(fn($job): array => [
				'id' => $job->id,
				'queue' => $job->queue,
				'type' => $this->jobType($job->payload),
				'status' => $job->reserved_at !== null ? 'reserved' : ($job->available_at > $now ? 'delayed' : 'waiting'),
				'attempts' => $job->attempts,
				'created_at' => $job->created_at,
				'available_at' => $job->available_at,
				'reserved_at' => $job->reserved_at,
			]);
		} elseif ($tab === 'failed') {
			$query = DB::table('failed_jobs')->select('id', 'uuid', 'queue', 'connection', 'payload', 'failed_at');
			if ($queue !== null && $queue !== '') $query->where('queue', $queue);
			if ($type !== null && $type !== '') $query->where('payload->displayName', 'like', '%' . $type . '%');
			$items = $query->orderBy('queue')->orderByDesc('failed_at')->orderByDesc('id')->paginate(50, ['*'], 'page', $page);
			$items->getCollection()->transform(fn($job): array => [
				'id' => $job->id,
				'uuid' => $job->uuid,
				'queue' => $job->queue,
				'connection' => $job->connection,
				'can_retry' => $this->canRetry($job),
				'type' => $this->jobType($job->payload),
				'failed_at' => $job->failed_at,
			]);
		} else {
			$query = DB::table('job_batches')->select('id', 'name', 'total_jobs', 'pending_jobs', 'failed_jobs', 'cancelled_at', 'created_at', 'finished_at');
			if ($queue !== null && $queue !== '') {
				$query->whereExists(function ($subquery) use ($queue): void {
					$subquery->selectRaw('1')->from('bundle_import_runs')->where('queue_name', $queue)
						->where(function ($references): void {
							foreach (['current_batch_id', 'validation_batch_id', 'delete_materials_batch_id', 'delete_resources_batch_id', 'resources_batch_id', 'materials_batch_id'] as $column) {
								$references->orWhereColumn($column, 'job_batches.id');
							}
						});
				});
			}
			$items = $query->orderByDesc('created_at')->paginate(50, ['*'], 'page', $page);
			$batchIds = $items->getCollection()->pluck('id')->all();
			$runQueues = collect();
			if ($batchIds !== []) {
				$runQueues = DB::table('bundle_import_runs')->select('queue_name', 'current_batch_id', 'validation_batch_id', 'delete_materials_batch_id', 'delete_resources_batch_id', 'resources_batch_id', 'materials_batch_id')
					->where(function ($references) use ($batchIds): void {
						foreach (['current_batch_id', 'validation_batch_id', 'delete_materials_batch_id', 'delete_resources_batch_id', 'resources_batch_id', 'materials_batch_id'] as $column) {
							$references->orWhereIn($column, $batchIds);
						}
					})->get();
			}
			$items->getCollection()->transform(function ($batch) use ($runQueues): array {
				$queues = $runQueues->filter(fn($run): bool => in_array($batch->id, [
					$run->current_batch_id, $run->validation_batch_id, $run->delete_materials_batch_id,
					$run->delete_resources_batch_id, $run->resources_batch_id, $run->materials_batch_id,
				], true))->pluck('queue_name')->unique();
				return [
					'id' => $batch->id,
					'name' => $batch->name,
					'queue' => $queues->count() === 1 ? $queues->first() : null,
					'total_jobs' => $batch->total_jobs,
					'pending_jobs' => $batch->pending_jobs,
					'failed_jobs' => $batch->failed_jobs,
					'cancelled_at' => $batch->cancelled_at,
					'created_at' => $batch->created_at,
					'finished_at' => $batch->finished_at,
				];
			});
		}

		$queueTable = $tab === 'jobs' ? 'jobs' : ($tab === 'failed' ? 'failed_jobs' : 'bundle_import_runs');
		$queueColumn = $tab === 'batches' ? 'queue_name' : 'queue';
		$queueNames = DB::table($queueTable)->distinct()->orderBy($queueColumn)->pluck($queueColumn);

		return response()->json(['summary' => $summary, 'data' => $items, 'queues' => $queueNames, 'refreshed_at' => now()->toIso8601String()])
			->header('Cache-Control', 'private, no-store');
	}

	public function showJob(int $job, QueueJobDetailsPresenter $presenter): JsonResponse {
		$record = DB::table('jobs')->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')->where('id', $job)->first();
		abort_if($record === null, 404, __('pool.queue-job-gone'));

		$now = now()->timestamp;
		return response()->json([
			'id' => $record->id,
			'queue' => $record->queue,
			'type' => $this->jobType($record->payload),
			'status' => $record->reserved_at !== null ? 'reserved' : ($record->available_at > $now ? 'delayed' : 'waiting'),
			'attempts' => $record->attempts,
			'created_at' => $record->created_at,
			'available_at' => $record->available_at,
			'payload' => $presenter->payload($record->payload),
		])->header('Cache-Control', 'private, no-store');
	}

	public function showFailed(string $failedJob, QueueJobDetailsPresenter $presenter): JsonResponse {
		$job = $this->failedJob($failedJob);

		return response()->json([
			'uuid' => $job->uuid,
			'queue' => $job->queue,
			'connection' => $job->connection,
			'type' => $this->jobType($job->payload),
			'failed_at' => $job->failed_at,
			'payload' => $presenter->payload($job->payload),
			'exception' => $job->exception,
			'can_retry' => $this->canRetry($job),
		])->header('Cache-Control', 'private, no-store');
	}

	public function retryFailed(string $failedJob): JsonResponse {
		return DB::transaction(function () use ($failedJob): JsonResponse {
			$job = DB::table('failed_jobs')->where('uuid', $failedJob)->lockForUpdate()->first();
			abort_if($job === null, 404);
			abort_unless($this->canRetry($job), 409, __('pool.queue-retry-unavailable'));

			Artisan::call('queue:retry', ['id' => [$job->uuid]]);
			abort_if(DB::table('failed_jobs')->where('uuid', $failedJob)->exists(), 409, __('pool.queue-retry-failed'));

			return response()->json(['message' => __('pool.queue-retried')])->header('Cache-Control', 'private, no-store');
		});
	}

	public function deleteFailed(string $failedJob): JsonResponse {
		abort_unless(DB::table('failed_jobs')->where('uuid', $failedJob)->delete() > 0, 404);

		return response()->json(['message' => __('pool.queue-deleted')])->header('Cache-Control', 'private, no-store');
	}

	private function failedJob(string $uuid): object {
		$job = DB::table('failed_jobs')->select('uuid', 'queue', 'connection', 'payload', 'exception', 'failed_at')->where('uuid', $uuid)->first();
		abort_if($job === null, 404);

		return $job;
	}

	private function canRetry(object $job): bool {
		$payload = json_decode($job->payload, true);

		return $job->connection !== 'context_search'
			&& !str_starts_with($job->queue, 'context-search-')
			&& array_key_exists($job->connection, config('queue.connections'))
			&& is_array($payload)
			&& ($payload['uuid'] ?? null) === $job->uuid
			&& is_string($payload['job'] ?? null)
			&& $payload['job'] !== '';
	}

	private function jobType(string $payload): string {
		$name = json_decode($payload, true)['displayName'] ?? null;
		return is_string($name) && preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]*$/', $name) ? $name : 'Job';
	}
}
