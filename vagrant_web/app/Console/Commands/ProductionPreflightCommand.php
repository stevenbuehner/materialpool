<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

class ProductionPreflightCommand extends Command
{
    protected $signature = 'production:preflight {--configuration-only : Skip filesystem, executable and database checks}';

    protected $description = 'Validate the mandatory Materialpool production contract without displaying secrets';

    public function handle(): int
    {
        $errors = $this->configurationErrors();

        if (! $this->option('configuration-only')) {
            $errors = [...$errors, ...$this->runtimeErrors()];
        }

        if ($errors !== []) {
            $this->components->error('Produktions-Preflight fehlgeschlagen.');

            foreach ($errors as $error) {
                $this->line(" - {$error}");
            }

            return self::FAILURE;
        }

        $this->components->info('Produktions-Preflight erfolgreich.');

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function configurationErrors(): array
    {
        $errors = [];

        $this->require($errors, app()->environment('production'), 'APP_ENV muss production sein.');
        $this->require($errors, config('app.debug') === false, 'APP_DEBUG muss false sein.');
        $this->require($errors, $this->isConfigured(config('app.key')), 'Der bestehende APP_KEY fehlt.');
        $this->require($errors, str_starts_with((string) config('app.url'), 'https://'), 'APP_URL muss HTTPS verwenden.');
        $this->require($errors, config('session.secure') === true, 'SESSION_SECURE_COOKIE muss true sein.');
        $this->require($errors, config('queue.default') === 'database', 'QUEUE_CONNECTION muss database sein.');
        $this->require($errors, (int) config('queue.connections.database.retry_after') > 120, 'QUEUE_RETRY_AFTER muss größer als 120 sein.');
        $this->require($errors, config('database.default') === 'mysql', 'DB_CONNECTION muss mysql sein.');
        $this->require(
            $errors,
            in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost', '::1'], true),
            'DB_HOST muss auf die lokale MySQL-Instanz zeigen.'
        );
        $this->require(
            $errors,
            $this->isConfigured(config('database.connections.mysql.database'))
                && config('database.connections.mysql.database') !== 'testing',
            'DB_DATABASE muss eine dedizierte Produktionsdatenbank sein.'
        );

        $proxies = $this->proxyList(config('trustedproxy.proxies'));
        $this->require($errors, $proxies !== [], 'TRUSTED_PROXIES muss explizite IP-Adressen oder CIDR-Netze enthalten.');

        foreach ($proxies as $proxy) {
            $this->require($errors, $this->isIpOrCidr($proxy), "Ungültiger TRUSTED_PROXIES-Eintrag: {$proxy}");
        }

        $this->require($errors, config('backup.backup.destination.disks') === ['backup', 'backup_s3'], 'Backups müssen lokal und auf backup_s3 geschrieben werden.');
        $this->require($errors, config('backup.backup.encryption') === 'aes256', 'Backup-Verschlüsselung muss aes256 sein.');
        $this->require($errors, $this->isConfigured(config('backup.backup.password')), 'BACKUP_ARCHIVE_PASSWORD fehlt.');

        $recipient = (string) config('backup.notifications.mail.to');
        $this->require($errors, config('backup.notifications.mail.recipient_is_explicit') === true, 'BACKUP_NOTIFICATION_EMAIL muss explizit gesetzt sein.');
        $this->require(
            $errors,
            filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false
                && ! str_ends_with($recipient, '@example.com')
                && ! str_ends_with($recipient, '.invalid'),
            'BACKUP_NOTIFICATION_EMAIL muss eine reale Empfängeradresse sein.'
        );

        $this->require($errors, config('mail.default') === 'smtp', 'MAIL_MAILER muss smtp sein.');

        foreach ([
            'mail.mailers.smtp.host' => 'MAIL_HOST',
            'mail.mailers.smtp.username' => 'MAIL_USERNAME',
            'mail.mailers.smtp.password' => 'MAIL_PASSWORD',
            'filesystems.disks.backup_s3.key' => 'BACKUP_S3_KEY',
            'filesystems.disks.backup_s3.secret' => 'BACKUP_S3_SECRET',
            'filesystems.disks.backup_s3.region' => 'BACKUP_S3_REGION',
            'filesystems.disks.backup_s3.bucket' => 'BACKUP_S3_BUCKET',
            'filesystems.disks.backup_s3.endpoint' => 'BACKUP_S3_ENDPOINT',
        ] as $configKey => $environmentKey) {
            $this->require($errors, $this->isConfigured(config($configKey)), "{$environmentKey} fehlt.");
        }

        $this->require(
            $errors,
            str_starts_with((string) config('filesystems.disks.backup_s3.endpoint'), 'https://'),
            'BACKUP_S3_ENDPOINT muss HTTPS verwenden.'
        );

        return $errors;
    }

    /** @return list<string> */
    private function runtimeErrors(): array
    {
        $errors = [];

        foreach ([storage_path(), base_path('bootstrap/cache'), public_path('uploads')] as $directory) {
            $this->require($errors, is_dir($directory) && is_writable($directory), "Verzeichnis ist nicht beschreibbar: {$directory}");
        }

        foreach ([
            resource_path('bin/ffmpeg'),
            resource_path('bin/ffprobe'),
            '/usr/bin/mysqldump',
            '/usr/bin/pdfinfo',
            '/usr/bin/pdftotext',
            '/usr/bin/libreoffice',
        ] as $executable) {
            $this->require($errors, is_executable($executable), "Ausführbare Laufzeitabhängigkeit fehlt: {$executable}");
        }

        $this->require($errors, extension_loaded('imagick'), 'PHP-Erweiterung imagick fehlt.');

        $privateKey = Passport::keyPath('oauth-private.key');
        $publicKey = Passport::keyPath('oauth-public.key');
        $this->require($errors, is_readable($privateKey), 'Bestehender privater Passport-Schlüssel fehlt oder ist nicht lesbar.');
        $this->require($errors, is_readable($publicKey), 'Bestehender öffentlicher Passport-Schlüssel fehlt oder ist nicht lesbar.');

        if (is_file($privateKey)) {
            $this->require($errors, (fileperms($privateKey) & 0777) === 0600, 'Privater Passport-Schlüssel muss Modus 0600 haben.');
        }

        try {
            DB::select('select 1');
        } catch (\Throwable) {
            $errors[] = 'MySQL-Verbindung ist nicht funktionsfähig.';
        }

        return $errors;
    }

    /** @param list<string> $errors */
    private function require(array &$errors, bool $condition, string $message): void
    {
        if (! $condition) {
            $errors[] = $message;
        }
    }

    /** @return list<string> */
    private function proxyList(mixed $proxies): array
    {
        if (is_string($proxies)) {
            $proxies = explode(',', $proxies);
        }

        if (! is_array($proxies)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $proxies), fn (string $proxy): bool => $proxy !== ''));
    }

    private function isIpOrCidr(string $value): bool
    {
        if (in_array($value, ['*', '**', 'REMOTE_ADDR'], true)) {
            return false;
        }

        [$address, $prefix] = array_pad(explode('/', $value, 2), 2, null);

        if (filter_var($address, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if ($prefix === null) {
            return true;
        }

        $maximum = str_contains($address, ':') ? 128 : 32;

        return ctype_digit($prefix) && (int) $prefix >= 0 && (int) $prefix <= $maximum;
    }

    private function isConfigured(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '' && strtolower(trim($value)) !== 'null';
    }
}
