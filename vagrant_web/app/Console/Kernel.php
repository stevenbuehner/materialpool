<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
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
	 * @param \Illuminate\Console\Scheduling\Schedule $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule): void {

		// Bereinigung, Backup und Monitoring laufen im regulären Ablauf zeitversetzt.
		$schedule->command(CleanupCommand::class)
			->dailyAt('00:30');
		$schedule->command(BackupCommand::class, [])
			->dailyAt('01:30')
			->runInBackground();
		$schedule->command(MonitorCommand::class)
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
