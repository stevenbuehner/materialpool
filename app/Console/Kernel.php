<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use App\Services\MaterialHandling\MaterialDownloadStore;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Spatie\Backup\Commands\BackupCommand;
use Spatie\Backup\Commands\CleanupCommand;
use Spatie\Backup\Commands\MonitorCommand;

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
	 * @param Schedule $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule): void {
		$schedule->call(fn () => app(MaterialDownloadStore::class)->deleteExpired())->dailyAt('04:00');

		// Bereinigung, Backup und Monitoring laufen im regulären Ablauf zeitversetzt.
		$schedule->command(CleanupCommand::class)
			->when(fn (): bool => (bool)config('backup.enabled'))
			->dailyAt('00:30');
		$schedule->command(BackupCommand::class, [])
			->when(fn (): bool => (bool)config('backup.enabled'))
			->dailyAt('01:30')
			->runInBackground();
		$schedule->command(MonitorCommand::class)
			->when(fn (): bool => (bool)config('backup.enabled'))
			->dailyAt('03:00');

	}

	/**
	 * Register the Closure based commands for the application.
	 *
	 * @return void
	 */
	protected function commands(): void {
		$this->load(__DIR__ . '/Commands');
	}
}
