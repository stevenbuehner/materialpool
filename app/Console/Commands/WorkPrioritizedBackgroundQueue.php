<?php

namespace App\Console\Commands;

use App\Services\Queue\PrioritizedBackgroundQueue;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class WorkPrioritizedBackgroundQueue extends Command {
	protected $signature = 'queues:work-background {--once : Höchstens einen bereiten Job bearbeiten} {--configuration-only : Nur Konfiguration und PHP-Laufzeit prüfen}';

	protected $description = 'Verarbeitet Bundle-, freigegebene Kontextsuche- und Vorschau-Jobs nach Priorität.';

	public function handle(PrioritizedBackgroundQueue $queues): int {
		if (!config('queue.prioritized_background_enabled')) {
			$this->error('Die priorisierte Hintergrund-Queue ist in dieser Installation nicht aktiviert.');

			return self::FAILURE;
		}

		if (!extension_loaded('pcntl')) {
			$this->error('Der Hintergrundworker benötigt die PHP-CLI-Erweiterung pcntl.');

			return self::FAILURE;
		}
		if ($this->option('configuration-only')) {
			return self::SUCCESS;
		}

		$stopping = FALSE;
		pcntl_async_signals(TRUE);
		pcntl_signal(SIGTERM, function () use (&$stopping): void { $stopping = TRUE; });
		pcntl_signal(SIGINT, function () use (&$stopping): void { $stopping = TRUE; });

		$startedAt = time();
		do {
			if (app()->isDownForMaintenance()) {
				if ($this->option('once')) {
					return self::SUCCESS;
				}
				sleep(3);
				continue;
			}

			$next = $queues->next();
			if ($next === NULL) {
				if ($this->option('once')) {
					return self::SUCCESS;
				}
				sleep(3);
				continue;
			}

			$process = new Process([
				PHP_BINARY, base_path('artisan'), 'queue:work', $next['connection'],
				'--queue=' . $next['queue'], '--stop-when-empty', '--max-jobs=1', '--sleep=0',
				'--timeout=' . $next['timeout'], '--tries=' . $next['tries'],
			]);
			$process->setTimeout($next['timeout'] + 60);
			$process->run(fn ($type, $output) => $this->output->write($output));
			if (!$process->isSuccessful()) {
				return self::FAILURE;
			}
		} while (!$stopping && !$this->option('once') && time() - $startedAt < 3600);

		return self::SUCCESS;
	}
}
