<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Queue\Console\WorkCommand;
use Spatie\Backup\Commands\BackupCommand;
use Spatie\Backup\Commands\CleanupCommand;

class Kernel extends ConsoleKernel {
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		//
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param \Illuminate\Console\Scheduling\Schedule $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule) {

		// Backups erstellen
		$schedule->command(BackupCommand::class, [])
			->daily()
			->runInBackground();
		$schedule->command(CleanupCommand::class)
			->daily();

		// Jobs ausführen
		$schedule->command(WorkCommand::class,
			['database', '--queue=default', '--stop-when-empty', '--tries=50', '--timeout=120', '--no-interaction'])
			->everyFiveMinutes();

	}

	/**
	 * Register the Closure based commands for the application.
	 *
	 * @return void
	 */
	protected function commands() {
		$this->load(__DIR__ . '/Commands');
	}
}
