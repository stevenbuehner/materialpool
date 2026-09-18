<?php

namespace App\Console\Commands;

use App\Jobs\PlanResourcePreviews;
use App\Models\Resource;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class QueueResourcePreviews extends Command {
	protected $signature = 'resources:queue-previews
		{--chunk=100 : Number of resources per queued planning job}';

	protected $description = 'Queues missing resource previews on the low-priority queue.';

	public function handle(): int {
		$chunkSize = filter_var($this->option('chunk'), FILTER_VALIDATE_INT, [
			'options' => ['min_range' => 1, 'max_range' => 1000],
		]);

		if ($chunkSize === FALSE) {
			$this->components->error('Die Batchgröße muss zwischen 1 und 1000 liegen.');

			return self::INVALID;
		}

		$query = (new Resource())->newQueryWithoutScopes()->select('id')->orderBy('id');
		$resourceCount = (clone $query)->count();
		$jobCount = 0;

		$query->chunkById($chunkSize, function ($resources) use (&$jobCount): void {
			foreach ($resources as $resource) {
				PlanResourcePreviews::dispatch($resource->id);
				$jobCount++;
			}
		}, 'id');

		$this->components->info("{$resourceCount} Ressourcen in {$jobCount} Preview-Planungsjobs eingeplant.");

		return self::SUCCESS;
	}
}
