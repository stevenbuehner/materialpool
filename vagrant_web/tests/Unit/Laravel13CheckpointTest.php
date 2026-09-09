<?php

namespace Tests\Unit;

use App\Http\Kernel;
use App\Http\Middleware\PreventRequestForgery;
use Composer\InstalledVersions;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as FrameworkPreventRequestForgery;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class Laravel13CheckpointTest extends TestCase
{
    public function test_final_checkpoint_uses_the_reviewed_dependency_set(): void
    {
        $composer = json_decode(
            file_get_contents(base_path('composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame('^13.0', $composer['require']['laravel/framework']);
        $this->assertSame('^8.4', $composer['require']['php']);
        $this->assertSame('^13.8', $composer['require']['laravel/passport']);
        $this->assertSame('^7.0', $composer['require']['kalnoy/nestedset']);
        $this->assertSame('^10.3', $composer['require']['spatie/laravel-backup']);
        $this->assertSame('^1.6', $composer['require']['tightenco/parental']);
        $this->assertSame('^2.8', $composer['require-dev']['laravel/boost']);
        $this->assertSame('^8.9.5', $composer['require-dev']['nunomaduro/collision']);
        $this->assertSame('^12.5.12', $composer['require-dev']['phpunit/phpunit']);
        $this->assertSame('^4.4', $composer['require-dev']['fruitcake/laravel-debugbar']);
        $this->assertSame('stable', $composer['minimum-stability']);
        $this->assertArrayNotHasKey('nanigans/single-table-inheritance', $composer['require']);
        $this->assertArrayNotHasKey('barryvdh/laravel-debugbar', $composer['require-dev']);
        $this->assertArrayNotHasKey('spatie/laravel-ignition', $composer['require-dev']);

        $this->assertSame('v13.31.0', InstalledVersions::getPrettyVersion('laravel/framework'));
        $this->assertSame('v13.8.0', InstalledVersions::getPrettyVersion('laravel/passport'));
        $this->assertSame('v7.0.0', InstalledVersions::getPrettyVersion('kalnoy/nestedset'));
        $this->assertSame('10.3.2', InstalledVersions::getPrettyVersion('spatie/laravel-backup'));
        $this->assertSame('v2.8.0', InstalledVersions::getPrettyVersion('laravel/boost'));
        $this->assertSame('12.5.35', InstalledVersions::getPrettyVersion('phpunit/phpunit'));
    }

    public function test_local_disk_root_keeps_the_existing_storage_contract(): void
    {
        $this->assertSame(storage_path('app'), config('filesystems.disks.local.root'));
    }

    public function test_the_application_uses_laravel_13_request_forgery_protection(): void
    {
        $kernel = new ReflectionClass(Kernel::class);
        $groups = $kernel->getDefaultProperties()['middlewareGroups'];

        $this->assertContains(PreventRequestForgery::class, $groups['web']);
        $this->assertTrue(is_subclass_of(PreventRequestForgery::class, FrameworkPreventRequestForgery::class));

        $middleware = app(PreventRequestForgery::class);
        $method = new ReflectionMethod(FrameworkPreventRequestForgery::class, 'hasValidOrigin');

        $sameOrigin = Request::create('/', 'POST', server: ['HTTP_SEC_FETCH_SITE' => 'same-origin']);
        $crossSite = Request::create('/', 'POST', server: ['HTTP_SEC_FETCH_SITE' => 'cross-site']);

        $this->assertTrue($method->invoke($middleware, $sameOrigin));
        $this->assertFalse($method->invoke($middleware, $crossSite));
    }
}
