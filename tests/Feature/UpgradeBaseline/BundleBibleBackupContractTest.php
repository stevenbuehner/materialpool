<?php

namespace Tests\Feature\UpgradeBaseline;

use App\Console\Kernel;
use App\Jobs\Bundle\FinishImportAfterUpdate;
use App\Models\Bibleverse;
use App\Models\Bundle;
use App\Services\Bundles\BundleQueueService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;
use Tests\TestCase;

class BundleBibleBackupContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundle_queue_names_and_job_metadata_are_stable(): void
    {
        $bundle = $this->bundle();
        $service = resolve(BundleQueueService::class);
        $job = (new FinishImportAfterUpdate($bundle, '2.4.1'))
            ->onConnection('database')
            ->onQueue($service->getQueueName($bundle));

        $this->assertSame('bundle_' . $bundle->id . '_queue', $service->getQueueName($bundle));
        $this->assertSame('database', $job->connection);
        $this->assertSame('bundle_' . $bundle->id . '_queue', $job->queue);
        $this->assertSame('2.4.1', $job->getVersion());
    }

    public function test_bundle_queue_inspection_reads_the_version_from_the_serialized_database_payload(): void
    {
        $bundle = $this->bundle();
        $service = resolve(BundleQueueService::class);
        $queue = $service->getQueueName($bundle);
        $job = new FinishImportAfterUpdate($bundle, '3.7.9');

        DB::table('jobs')->insert([
            'queue' => $queue,
            'payload' => json_encode(['data' => ['command' => serialize($job)]]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        $this->assertSame('3.7.9', $service->getFirstJobVersion($queue));
        $this->assertTrue($service->hasFinishImportAfterUpdateJob($queue));
        $this->assertSame(1, $service->countJobsInBundleQueue($bundle));
    }

    public function test_the_bible_package_service_and_php_contract_are_available(): void
    {
        $service = resolve('BibleVerseService');

        $this->assertInstanceOf(BibleVerseService::class, $service);
        $this->assertSame($service, resolve('BibleVerseService'));
        $this->assertTrue(is_a(Bibleverse::class, BibleVerseInterface::class, true));

        $verse = new Bibleverse(['from' => 1001001, 'to' => 1001001]);
        $this->assertSame('1Mo 1,1', $verse->label);
        $this->assertSame(1, $verse->from_book_id);
        $this->assertSame(1, $verse->from_chapter);
        $this->assertSame(1, $verse->from_verse);
    }

    public function test_the_bible_package_javascript_exports_used_by_vue_remain_present(): void
    {
        $servicePath = base_path('vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de.js');
        $entityPath = base_path('vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse.js');

        $this->assertFileExists($servicePath);
        $this->assertFileExists($entityPath);
        $this->assertStringContainsString('BibleVerseService', file_get_contents($servicePath));
        $this->assertStringContainsString('class BibleVerse', file_get_contents($entityPath));
    }

    public function test_backup_configuration_keeps_sources_destination_retention_and_notifications(): void
    {
        $this->assertSame(config('app.name'), config('backup.backup.name'));
        $this->assertSame([storage_path('app'), public_path('uploads')], config('backup.backup.source.files.include'));
        $this->assertSame([
            storage_path('app/tmp'),
            storage_path('app/context-search-ocr-artifacts'),
        ], config('backup.backup.source.files.exclude'));
        $this->assertNull(config('backup.backup.source.files.relative_path'));
        $this->assertSame(['mysql'], config('backup.backup.source.databases'));
        $this->assertSame(['backup', 'backup_s3'], config('backup.backup.destination.disks'));
        $this->assertSame('none', config('backup.backup.encryption'));
        $this->assertNull(config('backup.backup.password'));
        $this->assertSame(storage_path('backups'), config('backup.backup.temporary_directory'));
        $this->assertSame(14, config('backup.cleanup.default_strategy.keep_all_backups_for_days'));
        $this->assertSame(30, config('backup.cleanup.default_strategy.keep_daily_backups_for_days'));
        $this->assertSame(8, config('backup.cleanup.default_strategy.keep_weekly_backups_for_weeks'));
        $this->assertSame(6, config('backup.cleanup.default_strategy.keep_monthly_backups_for_months'));
        $this->assertSame(2, config('backup.cleanup.default_strategy.keep_yearly_backups_for_years'));
        $this->assertSame(['backup', 'backup_s3'], config('backup.monitor_backups.0.disks'));
        $this->assertSame(2, config('backup.monitor_backups.0.health_checks.' . \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class));
    }

    public function test_backup_cleanup_and_monitor_schedules_are_time_separated_while_queue_workers_are_external(): void
    {
        $schedule = new Schedule();
        $method = new ReflectionMethod(Kernel::class, 'schedule');
        $method->setAccessible(true);
        $method->invoke(resolve(Kernel::class), $schedule);
        $events = collect($schedule->events());

        $backup = $events->first(function ($event) {
            return strpos($event->command, "'artisan' backup:run") !== false;
        });
        $cleanup = $events->first(function ($event) {
            return strpos($event->command, "'artisan' backup:clean") !== false;
        });
        $monitor = $events->first(function ($event) {
            return strpos($event->command, "'artisan' backup:monitor") !== false;
        });
        $this->assertNotNull($backup);
        $this->assertSame('30 1 * * *', $backup->expression);
        $this->assertTrue($backup->runInBackground);
        $this->assertNotNull($cleanup);
        $this->assertSame('30 0 * * *', $cleanup->expression);
        $this->assertNotNull($monitor);
        $this->assertSame('0 3 * * *', $monitor->expression);
        $this->assertFalse($events->contains(function ($event) {
            return strpos($event->command, 'queue:work') !== false;
        }));
    }

    private function bundle(): Bundle
    {
        return Bundle::query()->create([
            'name' => 'Contract bundle',
            'description' => 'Upgrade baseline',
            'uuid' => 'contract-bundle',
            'container_root' => 'contract',
        ]);
    }
}
