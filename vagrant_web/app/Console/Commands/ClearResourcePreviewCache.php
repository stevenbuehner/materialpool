<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ClearResourcePreviewCache extends Command {
	protected $signature = 'resources:clear-preview-cache {--force : Confirms removal of all derived preview files}';

	protected $description = 'Clears all derived resource preview cache entries and temporary document previews.';

	public function handle(): int {
		if (!$this->option('force')) {
			$this->components->error('Dieser Befehl löscht abgeleitete Preview-Dateien. Mit --force bestätigen.');

			return self::INVALID;
		}

		Cache::store('previewimages')->flush();
		Storage::disk('local_tmp')->deleteDirectory('preview-documents');

		$this->components->info('Abgeleitete Resource-Preview-Caches wurden geleert.');

		return self::SUCCESS;
	}
}
