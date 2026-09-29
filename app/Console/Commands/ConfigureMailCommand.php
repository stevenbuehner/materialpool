<?php

namespace App\Console\Commands;

use App\Support\EnvironmentFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ConfigureMailCommand extends Command {
	protected $signature = 'mail:configure';
	protected $description = 'SMTP-Konfiguration interaktiv einrichten und den Versand testen';

	public function handle(): int {
		if (!$this->confirm('Soll E-Mail konfiguriert werden?', FALSE)) {
			$this->components->info('E-Mail-Konfiguration wurde nicht geändert.');
			return self::SUCCESS;
		}
		try {
			app(EnvironmentFile::class)->assertWritable();
		} catch (Throwable $exception) {
			$this->components->error($exception->getMessage());
			return self::FAILURE;
		}

		$host = trim((string)$this->ask('MAIL_HOST'));
		$port = trim((string)$this->ask('MAIL_PORT', '587'));
		$encryption = trim((string)$this->choice('MAIL_ENCRYPTION', ['tls', 'ssl', 'none'], 'tls'));
		$username = trim((string)$this->ask('MAIL_USERNAME'));
		$password = (string)$this->secret('MAIL_PASSWORD');
		$from = trim((string)$this->ask('MAIL_FROM_ADDRESS'));
		$fromName = trim((string)$this->ask('MAIL_FROM_NAME', config('app.name', 'Materialpool')));
		$testRecipient = trim((string)$this->ask('Test-Empfängeradresse', $from));

		if ($host === '' || $username === '' || $password === '' || !ctype_digit($port) || (int)$port < 1 || (int)$port > 65535
			|| filter_var($from, FILTER_VALIDATE_EMAIL) === FALSE
			|| filter_var($testRecipient, FILTER_VALIDATE_EMAIL) === FALSE) {
			$this->components->error('SMTP-Host, gültiger Port und E-Mail-Adressen sind erforderlich.');
			return self::INVALID;
		}

		config([
			'mail.default' => 'smtp', 'mail.configured' => TRUE,
			'mail.mailers.smtp.host' => $host, 'mail.mailers.smtp.port' => (int)$port,
			'mail.mailers.smtp.encryption' => $encryption === 'none' ? NULL : $encryption,
			'mail.mailers.smtp.username' => $username, 'mail.mailers.smtp.password' => $password,
			'mail.from.address' => $from, 'mail.from.name' => $fromName,
		]);

		try {
			Mail::raw('Materialpool SMTP-Konfiguration erfolgreich getestet.', function ($message) use ($testRecipient): void {
				$message->to($testRecipient)->subject('Materialpool E-Mail-Test');
			});
		} catch (Throwable) {
			$this->components->error('Der SMTP-Test ist fehlgeschlagen. Die bestehende .env-Konfiguration blieb unverändert.');
			return self::FAILURE;
		}

		app(EnvironmentFile::class)->write([
			'MAIL_CONFIGURED' => 'true', 'MAIL_MAILER' => 'smtp', 'MAIL_HOST' => $host, 'MAIL_PORT' => $port,
			'MAIL_ENCRYPTION' => $encryption === 'none' ? 'null' : $encryption, 'MAIL_USERNAME' => $username,
			'MAIL_PASSWORD' => $password, 'MAIL_FROM_ADDRESS' => $from, 'MAIL_FROM_NAME' => $fromName,
		]);
		$this->call('config:clear');
		$this->components->info('SMTP-Test erfolgreich; Konfiguration gespeichert.');
		return self::SUCCESS;
	}
}
