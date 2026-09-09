<?php

namespace Tests\Feature\UpgradeBaseline;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthenticationAndRouteContractTest extends TestCase
{
    use RefreshDatabase;

    private const PASSPORT_MIGRATIONS = [
        '2016_06_01_000001_create_oauth_auth_codes_table.php',
        '2016_06_01_000002_create_oauth_access_tokens_table.php',
        '2016_06_01_000003_create_oauth_refresh_tokens_table.php',
        '2016_06_01_000004_create_oauth_clients_table.php',
        '2016_06_01_000005_create_oauth_personal_access_clients_table.php',
    ];

    public function test_the_api_guard_remains_passport_with_the_eloquent_user_provider(): void
    {
        $this->assertSame('web', config('auth.defaults.guard'));
        $this->assertSame('passport', config('auth.guards.api.driver'));
        $this->assertSame('users', config('auth.guards.api.provider'));
        $this->assertSame('eloquent', config('auth.providers.users.driver'));
        $this->assertSame(User::class, config('auth.providers.users.model'));
        $this->assertContains(HasApiTokens::class, class_uses_recursive(User::class));
        $this->assertTrue(Passport::$passwordGrantEnabled);
    }

    public function test_framework_defaults_that_would_change_runtime_behavior_remain_explicit(): void
    {
        $this->assertFalse(config('hashing.rehash_on_login'));
        $this->assertSame('laravel:', config('cache.prefix'));
        $this->assertFalse(config('queue.connections.sync.after_commit'));
        $this->assertFalse(config('queue.connections.database.after_commit'));
    }

    public function test_passport_keeps_loading_exactly_its_five_historical_table_migrations(): void
    {
        $vendorPath = base_path('vendor/laravel/passport/database/migrations');
        $applicationPath = database_path('migrations');
        $migrations = array_map('basename', glob($vendorPath . '/*.php'));
        sort($migrations);

        $this->assertSame(self::PASSPORT_MIGRATIONS, $migrations);

        foreach (self::PASSPORT_MIGRATIONS as $migration) {
            $this->assertFileEquals(
                $vendorPath . '/' . $migration,
                $applicationPath . '/' . $migration,
                "Published Passport migration {$migration} differs from its locked source."
            );
        }
    }

    public function test_a_protected_api_route_rejects_anonymous_requests_and_accepts_passport_users(): void
    {
        $this->getJson(route('api.v1.general.options'))->assertUnauthorized();

        $user = User::factory()->create(['is_admin' => true]);
        Passport::actingAs($user, []);

        $this->getJson(route('api.v1.general.options'))
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.is_admin', true)
            ->assertJsonStructure(['user', 'server' => ['max_upload'], 'systemname']);
    }

    public function test_critical_application_route_signatures_are_stable(): void
    {
        $contracts = [
            'api.v1.general.options' => ['GET', 'api/v1/general/options'],
            'api.v1.keywords.index' => ['GET', 'api/v1/keywords'],
            'api.v1.keywords.update' => ['PUT', 'api/v1/keywords/{keyword}'],
            'api.v1.materials.store' => ['POST', 'api/v1/materials'],
            'api.v1.resources.store' => ['POST', 'api/v1/resources'],
            'api.v1.foreignMaterialStore' => ['POST', 'api/v1/foreign-materials/{foreignMaterialId}'],
            'api.v1.foreignResources.show' => ['GET', 'api/v1/foreign-resources/{foreignResourceId}'],
            'api.v1.bundles.update.init' => ['POST', 'api/v1/bundles/{bundle}/init-update'],
            'api.v2.api.v2.materialresource.attach' => ['POST', 'api/v2/material/{material}/resource/{resource}/attach'],
            'api.v2.api.v2.material.delete' => ['DELETE', 'api/v2/materials/{material}'],
            'pool.searchbar.get' => ['POST', 'pool/search/get'],
            'resource.image.preview' => ['GET', 'resource/{resource}/image/{width?}/{height?}'],
            'vue.' => ['GET', 'vue/{vue_capture?}'],
        ];

        foreach ($contracts as $name => [$method, $uri]) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Missing route {$name}.");
            $this->assertContains($method, $route->methods(), "Changed method for {$name}.");
            $this->assertSame($uri, $route->uri(), "Changed URI for {$name}.");
        }
    }

    public function test_passport_route_signatures_remain_available(): void
    {
        $contracts = [
            'passport.token' => ['POST', 'oauth/token'],
            'passport.authorizations.authorize' => ['GET', 'oauth/authorize'],
            'passport.authorizations.approve' => ['POST', 'oauth/authorize'],
            'passport.authorizations.deny' => ['DELETE', 'oauth/authorize'],
            'passport.clients.index' => ['GET', 'oauth/clients'],
            'passport.personal.tokens.index' => ['GET', 'oauth/personal-access-tokens'],
            'passport.tokens.index' => ['GET', 'oauth/tokens'],
        ];

        foreach ($contracts as $name => [$method, $uri]) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Missing Passport route {$name}.");
            $this->assertContains($method, $route->methods());
            $this->assertSame($uri, $route->uri());
        }
    }
}
