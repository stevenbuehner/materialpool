<?php

namespace Tests\Unit;

use App\Http\Kernel;
use App\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as FrameworkPreventRequestForgery;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class Laravel13CheckpointTest extends TestCase
{
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
