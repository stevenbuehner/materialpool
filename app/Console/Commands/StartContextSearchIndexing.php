<?php

namespace App\Console\Commands;

use App\Models\ContextSearchIndexRun;
use App\Models\ContextSearchIndexRunPage;
use App\Models\ContextSearchIndexRunResource;
use App\Models\Resource;
use App\Services\ContextSearch\ContextSearchCapacityGate;
use App\Services\ContextSearch\ContextSearchIndexPipeline;
use App\Services\ContextSearch\ContextSearchQueueSafety;
use App\Services\ContextSearch\EmbeddingProfile;
use App\Services\ContextSearch\Qdrant\QdrantClient;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class StartContextSearchIndexing extends Command {
	protected $signature = 'context-search:index
        {resource? : Optionale ID einer einzelnen PDF- oder Textressource}
        {--after= : Nur Ressourcen mit einer höheren ID einplanen}
        {--limit=100 : Maximale Anzahl von Ressourcen für einen neuen Lauf}
        {--resume= : Einen fehlgeschlagenen Lauf fortsetzen}';

	protected $description = 'Startet oder setzt einen manuellen, Qdrant-basierten Kontextsuche-Indexlauf fort.';

	public function handle(ContextSearchQueueSafety $queueSafety): int {
		if (!config('context_search.enabled')) {
			$this->components->error('Die Kontextsuche ist deaktiviert. Setze CONTEXT_SEARCH_ENABLED=true erst nach betrieblicher Freigabe.');

			return self::FAILURE;
		}

		try {
			$queueSafety->assertDispatchAllowed();
			app(ContextSearchCapacityGate::class)->assertCanStart();
			$run = filled($this->option('resume'))
				? $this->resume((string)$this->option('resume'))
				: $this->start(app(QdrantClient::class), app(EmbeddingProfile::class));
		} catch (Throwable $exception) {
			report($exception);
			$this->components->error($exception->getMessage());

			return self::FAILURE;
		}

		$this->components->info("Indexlauf {$run->getKey()} plant {$run->total_resources} Ressourcen auf Queue {$this->queueName()} ein.");
		$this->components->info('Der Lauf ist nur manuell gestartet; es wurden keine Resource- oder Material-Listener aktiviert.');

		return self::SUCCESS;
	}

	private function resume(string $runId): ContextSearchIndexRun {
		$run = ContextSearchIndexRun::query()->findOrFail($runId);

		if ($run->status === ContextSearchIndexRun::STATUS_COMPLETED) {
			throw new RuntimeException('Ein abgeschlossener Indexlauf kann nicht fortgesetzt werden. Starte stattdessen einen neuen Lauf.');
		}

		$pending = ContextSearchIndexRunResource::query()
			->where('run_id', $run->getKey())
			->whereIn('status', [ContextSearchIndexRun::STATUS_PENDING, ContextSearchIndexRun::STATUS_FAILED, ContextSearchIndexRunResource::STATUS_RUNNING])
			->orderBy('resource_id')
			->get(['id', 'resource_id', 'status']);

		if ($pending->isEmpty()) {
			throw new RuntimeException('Dieser Lauf enthält keine fortsetzbaren Ressourcen.');
		}

		$run->update([
			'status'          => ContextSearchIndexRun::STATUS_RUNNING,
			'failure_message' => NULL,
			'finished_at'     => NULL,
		]);

		$pipeline = app(ContextSearchIndexPipeline::class);
		foreach ($pending as $runResource) {
			if ($runResource->status === ContextSearchIndexRun::STATUS_FAILED) {
				ContextSearchIndexRunResource::query()->whereKey($runResource->getKey())
					->update(['status' => ContextSearchIndexRun::STATUS_PENDING, 'failure_message' => NULL]);
				ContextSearchIndexRunPage::query()->where('run_resource_id', $runResource->getKey())
					->where('status', ContextSearchIndexRunPage::STATUS_FAILED)
					->update(['status' => ContextSearchIndexRunPage::STATUS_PENDING, 'failure_code' => NULL]);
			} elseif ($runResource->status === ContextSearchIndexRunResource::STATUS_RUNNING) {
				$pipeline->requeueResource((int)$runResource->getKey());
			}
		}
		$pipeline->advanceRun($run->getKey());

		return $run;
	}

	private function start(QdrantClient $qdrant, EmbeddingProfile $profile): ContextSearchIndexRun {
		$resource = $this->argument('resource');
		$limit    = (int)$this->option('limit');

		if ($limit < 1 || $limit > 1000) {
			throw new InvalidArgumentException('Die Option --limit muss zwischen 1 und 1000 liegen.');
		}

		$collection = $qdrant->aliases()[(string)config('context_search.qdrant.active_alias')] ?? NULL;

		if (!is_string($collection)) {
			throw new RuntimeException('Der aktive Qdrant-Alias fehlt. Provisioniere und aktiviere zuerst die Ziel-Collection.');
		}

		$query = (new Resource())->newQueryWithoutScopes()
			->whereIn('type', ['pdf', 'text'])
			->orderBy('id');

		if (filled($resource)) {
			$query->whereKey((int)$resource);
		} elseif (filled($this->option('after'))) {
			$query->whereKey('>', (int)$this->option('after'));
		}

		$resourceIds = $query->limit($resource === NULL ? $limit : 1)->pluck('id')->all();

		if ($resourceIds === []) {
			throw new RuntimeException('Keine passende PDF- oder Textressource für diesen Lauf gefunden.');
		}

		$run = ContextSearchIndexRun::query()->create([
			'status'             => ContextSearchIndexRun::STATUS_RUNNING,
			'collection_name'    => $collection,
			'embedding_profile'  => $profile->id(),
			'cursor_resource_id' => max($resourceIds),
			'total_resources'    => count($resourceIds),
			'started_at'         => now(),
		]);

		foreach ($resourceIds as $resourceId) {
			ContextSearchIndexRunResource::query()->create([
				'run_id'      => $run->getKey(),
				'resource_id' => $resourceId,
				'status'      => ContextSearchIndexRun::STATUS_PENDING,
			]);
		}

		app(ContextSearchIndexPipeline::class)->advanceRun($run->getKey());

		return $run;
	}

	private function queueName(): string {
		return (string)config('context_search.indexing.queue');
	}
}
