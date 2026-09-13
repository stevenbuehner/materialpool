<?php

namespace App\Console\Commands;

use App\Jobs\UpdateResourceFilesizesBatch;
use App\Models\Resource;
use App\Services\Processors\ResourceFilesizeProcessor;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class BackfillResourceFilesizes extends Command {
	protected $signature = 'resources:backfill-filesizes
		{--chunk=100 : Number of resources per queued job}
		{--force : Recalculate already populated values}';

	protected $description = 'Queue batched calculation of persisted resource filesizes';

	public function handle(): int {
		$chunkSize = filter_var($this->option('chunk'), FILTER_VALIDATE_INT, [
			'options' => ['min_range' => 1, 'max_range' => 1000],
		]);

		if ($chunkSize === FALSE) {
			$this->components->error('Die Batchgröße muss zwischen 1 und 1000 liegen.');

			return self::INVALID;
		}

		$force = (bool)$this->option('force');
		$query = $this->eligibleResources($force);
		$resourceCount = (clone $query)->count();
		$jobCount = 0;

		$query->chunkById($chunkSize, function ($resources) use ($force, &$jobCount): void {
			UpdateResourceFilesizesBatch::dispatch($resources->modelKeys(), $force)
				->onConnection('database')
				->onQueue('default');
			$jobCount++;
		}, 'id');

		$this->components->info("{$resourceCount} Ressourcen in {$jobCount} Batch-Jobs eingeplant.");

		return self::SUCCESS;
	}

	protected function eligibleResources(bool $force): Builder {
		$query = (new Resource())->newQueryWithoutScopes()
			->select('id')
			->where(function (Builder $query): void {
				$query->where('type', 'text')
					->orWhere(function (Builder $query): void {
						$query->whereIn('type', ResourceFilesizeProcessor::fileTypeKeys())
							->whereNotNull('local_path');
					});
			})
			->orderBy('id');

		if (!$force) {
			$query->whereNull('filesize');
		}

		return $query;
	}
}
