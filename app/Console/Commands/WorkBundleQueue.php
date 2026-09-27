<?php

namespace App\Console\Commands;

use App\Enums\BundleImportStatus;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use Illuminate\Console\Command;

class WorkBundleQueue extends Command {
	protected $signature = 'bundles:work {bundle : Die numerische Bundle-ID} {--dry-run : Zeigt nur den aktiven Lauf und den Queue-Namen an}';

	protected $description = 'Verarbeitet einen bereits gestarteten Bundle-Import über den Standard-Laravel-Worker.';

	public function handle(): int {
		$bundle = Bundle::query()->find($this->argument('bundle'));
		if ($bundle === NULL) {
			$this->error('Das angegebene Bundle existiert nicht.');

			return self::INVALID;
		}

		$run = BundleImportRun::query()
			->where('bundle_id', $bundle->id)
			->whereIn('status', [BundleImportStatus::Pending, BundleImportStatus::Running])
			->latest('created_at')
			->first();
		if ($run === NULL) {
			$this->warn('Für dieses Bundle gibt es keinen aktiven Importlauf. Es wurde kein neuer Lauf gestartet.');

			return self::FAILURE;
		}

		$this->line(sprintf('Aktiver Lauf %s (%s, Phase %s) auf Queue %s.', $run->id, $run->operation->value, $run->phase->value, $run->queue_name));
		if ($this->option('dry-run')) {
			return self::SUCCESS;
		}

		return $this->call('queue:work', [
			'connection'        => 'database',
			'--queue'           => $run->queue_name,
			'--stop-when-empty' => TRUE,
			'--timeout'         => 120,
			'--tries'           => 0,
			'--backoff'         => 5,
		]);
	}
}
