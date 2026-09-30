<?php

namespace Tests\Feature\UpgradeBaseline;

use App\Console\Kernel;
use App\Http\Middleware\TrustProxies;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use LogicException;
use ReflectionMethod;
use Tests\TestCase;

class ProductionDeploymentContractTest extends TestCase
{
    public function test_health_route_is_public_and_discloses_no_runtime_details(): void
    {
        Event::fake([DiagnosingHealth::class]);

        $response = $this->get('/up');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame('OK', $response->getContent());
        Event::assertDispatchedTimes(DiagnosingHealth::class, 1);
    }

    public function test_only_explicitly_configured_proxies_can_assert_https(): void
    {
        config(['trustedproxy.proxies' => '10.20.0.0/16']);
        $middleware = resolve(TrustProxies::class);

        $trustedRequest = Request::create('http://materialpool.test/up', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.20.1.5',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'pool.example.test',
        ]);
        $middleware->handle($trustedRequest, fn (Request $request) => $request);

        $this->assertTrue($trustedRequest->isSecure());
        $this->assertSame('pool.example.test', $trustedRequest->getHost());

        $untrustedRequest = Request::create('http://materialpool.test/up', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'spoofed.example.test',
        ]);
        $middleware->handle($untrustedRequest, fn (Request $request) => $request);

        $this->assertFalse($untrustedRequest->isSecure());
        $this->assertSame('materialpool.test', $untrustedRequest->getHost());
    }

    public function test_wildcard_proxy_trust_is_rejected(): void
    {
        config(['trustedproxy.proxies' => '*']);
        $request = Request::create('http://materialpool.test/up', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('TRUSTED_PROXIES');

        resolve(TrustProxies::class)->handle($request, fn (Request $handledRequest) => $handledRequest);
    }

    public function test_default_queue_is_no_longer_started_by_the_scheduler(): void
    {
        $schedule = new Schedule();
        $method = new ReflectionMethod(Kernel::class, 'schedule');
        $method->setAccessible(true);
        $method->invoke(resolve(Kernel::class), $schedule);
        $commands = collect($schedule->events())->pluck('command');

        $this->assertFalse($commands->contains(
            fn (string $command) => str_contains($command, 'queue:work')
        ));
        $this->assertTrue($commands->contains(
            fn (string $command) => str_contains($command, "'artisan' backup:run")
        ));
        $this->assertTrue($commands->contains(
            fn (string $command) => str_contains($command, "'artisan' backup:clean")
        ));
        $this->assertTrue($commands->contains(
            fn (string $command) => str_contains($command, "'artisan' backup:monitor")
        ));
    }

    public function test_scheduled_backup_tasks_are_skipped_when_backup_is_disabled(): void
    {
        $schedule = new Schedule();
        $method = new ReflectionMethod(Kernel::class, 'schedule');
        $method->setAccessible(true);
        $method->invoke(resolve(Kernel::class), $schedule);
        $backupEvent = collect($schedule->events())->first(
            fn ($event): bool => str_contains((string)$event->command, 'backup:run')
        );

        $this->assertNotNull($backupEvent);
        config(['backup.enabled' => false]);
        $this->assertFalse($backupEvent->filtersPass($this->app));

        config(['backup.enabled' => true]);
        $this->assertTrue($backupEvent->filtersPass($this->app));
    }

    public function test_production_backup_targets_are_local_and_unencrypted_offsite(): void
    {
        $this->assertSame(['backup', 'backup_s3'], config('backup.backup.destination.disks'));
        $this->assertSame('none', config('backup.backup.encryption'));
        $this->assertNull(config('backup.backup.password'));
        $this->assertTrue(config('backup.backup.verify_backup'));
        $this->assertSame(['backup', 'backup_s3'], config('backup.monitor_backups.0.disks'));
        $this->assertContains(storage_path('app'), config('backup.backup.source.files.include'));
        $this->assertContains(public_path('uploads'), config('backup.backup.source.files.include'));
        $this->assertSame('s3', config('filesystems.disks.backup_s3.driver'));
        $this->assertArrayHasKey('endpoint', config('filesystems.disks.backup_s3'));
        $this->assertArrayHasKey('use_path_style_endpoint', config('filesystems.disks.backup_s3'));
    }

    public function test_database_queue_visibility_exceeds_worker_timeout(): void
    {
        $this->assertSame('database', config('queue.connections.database.driver'));
        $this->assertGreaterThan(120, config('queue.connections.database.retry_after'));
    }

    public function test_proxmox_queue_worker_restarts_after_its_planned_lifetime(): void
    {
        $unit = file_get_contents(base_path('deployment/systemd/materialpool-queue.service'));
        $background = file_get_contents(base_path('deployment/systemd/materialpool-background.service'));

        $this->assertStringContainsString('--queue=default ', $unit);
        $this->assertStringContainsString('--max-time=3600', $unit);
        $this->assertStringContainsString('Restart=always', $unit);
        $this->assertStringContainsString('queues:work-background', $background);
        $this->assertStringContainsString('Restart=always', $background);
        $this->assertStringContainsString('KillMode=mixed', $background);
    }

    public function test_proxmox_install_and_updates_manage_both_queue_services(): void
    {
        $installer = file_get_contents(base_path('install/materialpool-install.sh'));
        $updater = file_get_contents(base_path('deployment/release/update.sh'));

        $this->assertStringContainsString('materialpool-background.service materialpool-schedule.service', $installer);
        $this->assertStringContainsString('apply_units "$release"', $updater);
        $this->assertStringContainsString('apply_units "$base/current"', $updater);
        $this->assertStringContainsString('save_units "$tmp/units"', $updater);
        $this->assertStringContainsString('restore_units "$tmp/units"', $updater);
        $this->assertStringContainsString('queues:work-background --configuration-only', $updater);
    }

    public function test_production_preflight_accepts_complete_explicit_configuration(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        config([
            'app.debug' => false,
            'app.key' => 'base64:dGVzdC1vbmx5LWFwcGxpY2F0aW9uLWtleQ==',
            'app.url' => 'https://pool.example.test',
            'session.secure' => true,
            'queue.default' => 'database',
            'queue.connections.database.retry_after' => 150,
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.database' => 'materialpool',
            'trustedproxy.proxies' => '10.20.0.0/16,2001:db8::/48',
            'backup.enabled' => true,
            'backup.backup.destination.disks' => ['backup', 'backup_s3'],
            'backup.notifications.mail.to' => 'backup@pool.example.test',
            'backup.notifications.mail.recipient_is_explicit' => true,
            'mail.default' => 'smtp',
            'mail.configured' => true,
            'mail.mailers.smtp.host' => 'smtp.example.test',
            'mail.mailers.smtp.username' => 'test-user',
            'mail.mailers.smtp.password' => 'test-password',
            'filesystems.disks.backup_s3.key' => 'test-key',
            'filesystems.disks.backup_s3.secret' => 'test-secret',
            'filesystems.disks.backup_s3.region' => 'test-region',
            'filesystems.disks.backup_s3.bucket' => 'test-bucket',
            'filesystems.disks.backup_s3.endpoint' => 'https://s3.example.test',
        ]);

        $exitCode = Artisan::call('production:preflight', ['--configuration-only' => true]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertStringNotContainsString('test-secret', Artisan::output());
        $this->assertStringNotContainsString('test-password', Artisan::output());
    }

    public function test_production_preflight_allows_optional_services_and_direct_http_setup(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        config([
            'app.debug' => false,
            'app.key' => 'base64:dGVzdC1vbmx5LWFwcGxpY2F0aW9uLWtleQ==',
            'app.url' => 'http://192.0.2.20',
            'session.secure' => false,
            'queue.default' => 'database',
            'queue.connections.database.retry_after' => 150,
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.database' => 'materialpool',
            'trustedproxy.proxies' => null,
            'backup.enabled' => false,
            'mail.configured' => false,
            'mail.default' => 'log',
        ]);

        $exitCode = Artisan::call('production:preflight', ['--configuration-only' => true]);

        $this->assertSame(0, $exitCode, Artisan::output());
    }

    public function test_production_operations_assets_are_versioned(): void
    {
        foreach ([
            'ops/production/build-release.sh',
            'ops/production/deploy-release.sh',
            'ops/production/activate-release.sh',
            'ops/production/provision-ubuntu.sh',
            'ops/production/nginx.conf',
            'ops/production/materialpool-worker.conf',
            'ops/production/materialpool.cron',
            'ops/production/materialpool.sudoers',
            'docs/ai/production-deployment-contract.md',
        ] as $path) {
            $this->assertFileExists(base_path($path), "Missing production asset: {$path}");
        }
    }

    public function test_production_deploy_assets_enforce_reproducible_and_safe_commands(): void
    {
        $buildScript = file_get_contents(base_path('ops/production/build-release.sh'));
        $activateScript = file_get_contents(base_path('ops/production/activate-release.sh'));
        $appServiceProvider = file_get_contents(app_path('Providers/AppServiceProvider.php'));
        $worker = file_get_contents(base_path('ops/production/materialpool-worker.conf'));

        $this->assertStringContainsString('npm ci --ignore-scripts', $buildScript);
        $this->assertStringContainsString('npm run build', $buildScript);
        $this->assertStringContainsString('public/build/manifest.json', $buildScript);
        $this->assertStringContainsString('test ! -e public/hot', $buildScript);
        $this->assertStringContainsString("find . -type f ! -path './.release-manifest' -print0", $buildScript);
        $this->assertStringContainsString('LC_ALL=C sort -z', $buildScript);
        $this->assertStringContainsString('composer install', $activateScript);
        $this->assertStringContainsString('--no-dev', $activateScript);
        $this->assertStringContainsString("find . -type f ! -path './.release-manifest' -print0", $activateScript);
        $this->assertStringContainsString('LC_ALL=C sort -z', $activateScript);
        $this->assertStringNotContainsString('LaravelIdeHelper', $appServiceProvider);
        $this->assertStringContainsString('storage/framework/down', $activateScript);
        $this->assertStringContainsString("supervisorctl status 'materialpool-default:*'", $activateScript);
        $this->assertStringNotContainsString('composer update', $activateScript);
        $this->assertStringNotContainsString('migrate:fresh', $activateScript);
        $this->assertStringContainsString('--queue=default,resource-previews-low', $worker);
        $this->assertStringContainsString('--tries=50', $worker);
        $this->assertStringContainsString('--timeout=120', $worker);
        $this->assertStringContainsString('--max-time=3600', $worker);
    }
}
