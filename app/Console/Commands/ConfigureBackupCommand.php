<?php

namespace App\Console\Commands;

use App\Support\EnvironmentFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ConfigureBackupCommand extends Command {
	protected $signature = 'backup:configure';
	protected $description = 'S3-Backup interaktiv einrichten und ein Test-Backup erstellen';

	public function handle(): int {
		if (!$this->confirm('Soll ein lokales und externes S3-Backup konfiguriert werden?', FALSE)) {
			$this->components->info('Backup-Konfiguration wurde nicht geändert.');
			return self::SUCCESS;
		}
		try {
			app(EnvironmentFile::class)->assertWritable();
		} catch (Throwable $exception) {
			$this->components->error($exception->getMessage());
			return self::FAILURE;
		}

		$recipient = trim((string)$this->ask('BACKUP_NOTIFICATION_EMAIL'));
		$endpoint = trim((string)$this->ask('BACKUP_S3_ENDPOINT (https://...)'));
		$region = trim((string)$this->ask('BACKUP_S3_REGION'));
		$bucket = trim((string)$this->ask('BACKUP_S3_BUCKET'));
		$pathStyle = (bool)$this->confirm('BACKUP_S3_USE_PATH_STYLE_ENDPOINT?', FALSE);
		$key = trim((string)$this->ask('BACKUP_S3_KEY'));
		$secret = (string)$this->secret('BACKUP_S3_SECRET');

		if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === FALSE || !str_starts_with($endpoint, 'https://')
			|| $region === '' || $bucket === '' || $key === '' || $secret === '') {
			$this->components->error('Alle Backup-Werte müssen gültig und vollständig sein; der Endpoint muss HTTPS verwenden.');
			return self::INVALID;
		}

		config([
			'backup.enabled' => TRUE, 'backup.notifications.mail.to' => $recipient,
			'backup.notifications.mail.recipient_is_explicit' => TRUE,
			'backup.backup.destination.disks' => ['backup', 'backup_s3'],
			'filesystems.disks.backup_s3.key' => $key, 'filesystems.disks.backup_s3.secret' => $secret,
			'filesystems.disks.backup_s3.region' => $region, 'filesystems.disks.backup_s3.bucket' => $bucket,
			'filesystems.disks.backup_s3.endpoint' => $endpoint,
			'filesystems.disks.backup_s3.use_path_style_endpoint' => $pathStyle,
		]);

		$localDisk = Storage::disk('backup');
		$existingFiles = $localDisk->allFiles();
		$exitCode = Artisan::call('backup:run', ['--disable-notifications' => TRUE]);
		if ($exitCode !== self::SUCCESS) {
			$this->components->error('Das Test-Backup ist fehlgeschlagen. Die bestehende .env-Konfiguration blieb unverändert.');
			return self::FAILURE;
		}
		$newArchives = collect(array_diff($localDisk->allFiles(), $existingFiles))
			->filter(fn (string $path): bool => str_ends_with($path, '.zip'));
		if ($newArchives->isEmpty()) {
			$this->components->error('Das Test-Backup hat kein lokales Archiv erzeugt; die .env blieb unverändert.');
			return self::FAILURE;
		}
		foreach ($newArchives as $archive) {
			$path = $localDisk->path($archive);
			if (!chmod($path, 0640) || !chown($path, 'www-data') || !chgrp($path, 'www-data')) {
				$this->components->error('Die Zugriffsrechte des Test-Backups konnten nicht sicher gesetzt werden; die .env blieb unverändert.');
				return self::FAILURE;
			}
		}

		app(EnvironmentFile::class)->write([
			'BACKUP_ENABLED' => 'true', 'BACKUP_NOTIFICATION_EMAIL' => $recipient,
			'BACKUP_S3_ENDPOINT' => $endpoint, 'BACKUP_S3_REGION' => $region, 'BACKUP_S3_BUCKET' => $bucket,
			'BACKUP_S3_USE_PATH_STYLE_ENDPOINT' => $pathStyle ? 'true' : 'false',
			'BACKUP_S3_KEY' => $key, 'BACKUP_S3_SECRET' => $secret,
		]);
		$this->call('config:clear');
		$this->components->info('Test-Backup lokal und auf S3 erfolgreich; Konfiguration gespeichert.');
		if (config('mail.configured') !== TRUE) {
			$this->components->warn('SMTP ist nicht eingerichtet; Backup-Benachrichtigungen werden nicht per E-Mail zugestellt.');
		}
		return self::SUCCESS;
	}
}
