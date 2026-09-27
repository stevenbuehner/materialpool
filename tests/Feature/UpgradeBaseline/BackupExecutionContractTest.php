<?php

namespace Tests\Feature\UpgradeBaseline;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BackupExecutionContractTest extends TestCase
{
    public function test_backup_10_creates_an_archive_that_can_be_restored_in_isolation(): void
    {
        Storage::fake('backup');

        $fixture = base_path('tests/testFiles/Bild.jpg');
        $temporaryDirectory = storage_path('framework/testing/backup-temporary');
        $restoreDirectory = storage_path('framework/testing/backup-restore');

        File::deleteDirectory($temporaryDirectory);
        File::deleteDirectory($restoreDirectory);

        config([
            'backup.backup.name' => 'materialpool-contract-test',
            'backup.backup.source.files.include' => [$fixture],
            'backup.backup.source.files.exclude' => [],
            'backup.backup.source.files.follow_links' => false,
            'backup.backup.source.files.relative_path' => base_path('tests/testFiles'),
            'backup.backup.source.databases' => [],
            'backup.backup.destination.disks' => ['backup'],
            'backup.backup.temporary_directory' => $temporaryDirectory,
            'backup.backup.password' => null,
            'backup.backup.encryption' => 'none',
        ]);

        $exitCode = Artisan::call('backup:run', [
            '--only-files' => true,
            '--disable-notifications' => true,
        ]);

        $archives = collect(Storage::disk('backup')->allFiles())
            ->filter(fn (string $path): bool => str_ends_with($path, '.zip'));

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertCount(1, $archives);

        File::ensureDirectoryExists($restoreDirectory);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open(Storage::disk('backup')->path($archives->first())));
        $this->assertTrue($zip->extractTo($restoreDirectory));
        $zip->close();

        $restoredFiles = collect(File::allFiles($restoreDirectory));
        $restoredFixture = $restoredFiles->first(
            fn ($file): bool => $file->getFilename() === 'Bild.jpg'
        );

        $this->assertNotNull($restoredFixture);
        $this->assertSame(hash_file('sha256', $fixture), hash_file('sha256', $restoredFixture->getPathname()));

        File::deleteDirectory($temporaryDirectory);
        File::deleteDirectory($restoreDirectory);
    }
}
