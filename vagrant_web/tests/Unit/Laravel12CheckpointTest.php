<?php

namespace Tests\Unit;

use Composer\InstalledVersions;
use Tests\TestCase;

class Laravel12CheckpointTest extends TestCase
{
    public function test_non_deployable_checkpoint_uses_the_reviewed_dependency_set(): void
    {
        $composer = json_decode(
            file_get_contents(base_path('composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame('12.0.0', $composer['require']['laravel/framework']);
        $this->assertSame('^8.4', $composer['require']['php']);
        $this->assertSame('^6.0', $composer['require']['kalnoy/nestedset']);
        $this->assertSame('^9.3', $composer['require']['spatie/laravel-backup']);
        $this->assertSame('1.0.21', $composer['require-dev']['laravel/boost']);
        $this->assertSame('8.6.1', $composer['require-dev']['nunomaduro/collision']);
        $this->assertSame('^11.5.50', $composer['require-dev']['phpunit/phpunit']);
        $this->assertSame('2.9.1', $composer['require-dev']['spatie/laravel-ignition']);
        $this->assertSame('7.2.*', $composer['require-dev']['symfony/console']);

        $this->assertSame('v12.0.0', InstalledVersions::getPrettyVersion('laravel/framework'));
        $this->assertSame('v6.0.7', InstalledVersions::getPrettyVersion('kalnoy/nestedset'));
        $this->assertSame('9.3.6', InstalledVersions::getPrettyVersion('spatie/laravel-backup'));
        $this->assertSame('v1.0.21', InstalledVersions::getPrettyVersion('laravel/boost'));
        $this->assertSame('11.5.56', InstalledVersions::getPrettyVersion('phpunit/phpunit'));
    }

    public function test_local_disk_root_keeps_the_pre_laravel_12_contract(): void
    {
        $this->assertSame(storage_path('app'), config('filesystems.disks.local.root'));
    }
}
