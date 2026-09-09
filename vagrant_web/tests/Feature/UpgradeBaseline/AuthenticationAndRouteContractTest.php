<?php

namespace Tests\Feature\UpgradeBaseline;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthenticationAndRouteContractTest extends TestCase
{
    use RefreshDatabase;

    private const HISTORICAL_PASSPORT_MIGRATIONS = [
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
        $this->assertTrue(is_a(User::class, OAuthenticatable::class, true));
        $this->assertTrue(Passport::$passwordGrantEnabled);
    }

    public function test_framework_defaults_that_would_change_runtime_behavior_remain_explicit(): void
    {
        $this->assertFalse(config('hashing.rehash_on_login'));
        $this->assertSame('laravel:', config('cache.prefix'));
        $this->assertFalse(config('cache.serializable_classes'));
        $this->assertSame('php', config('session.serialization'));
        $this->assertSame('materialpool_session', config('session.cookie'));
        $this->assertFalse(config('queue.connections.sync.after_commit'));
        $this->assertFalse(config('queue.connections.database.after_commit'));
    }

    public function test_historical_passport_migrations_remain_unchanged_and_the_new_device_migration_matches_passport(): void
    {
        $vendorPath = base_path('vendor/laravel/passport/database/migrations');
        $applicationPath = database_path('migrations');

        foreach (self::HISTORICAL_PASSPORT_MIGRATIONS as $migration) {
            $this->assertFileExists($applicationPath . '/' . $migration);
        }

        $this->assertFileEquals(
            $vendorPath . '/2024_06_01_000001_create_oauth_device_codes_table.php',
            $applicationPath . '/2024_06_01_000001_create_oauth_device_codes_table.php'
        );
    }

    public function test_passport_uses_its_version_13_schema_and_defaults(): void
    {
        $this->assertTrue(Passport::$clientUuids);
        $this->assertFalse(Passport::$registersJsonApiRoutes);
        $this->assertFalse(Passport::$unserializesCookies);
        $this->assertTrue(Schema::hasColumns('oauth_clients', [
            'id', 'owner_type', 'owner_id', 'name', 'secret', 'provider',
            'redirect_uris', 'grant_types', 'revoked', 'created_at', 'updated_at',
        ]));
        $this->assertFalse(Schema::hasColumn('oauth_clients', 'user_id'));
        $this->assertFalse(Schema::hasColumn('oauth_clients', 'redirect'));
        $this->assertFalse(Schema::hasColumn('oauth_clients', 'personal_access_client'));
        $this->assertFalse(Schema::hasColumn('oauth_clients', 'password_client'));
        $this->assertTrue(Schema::hasTable('oauth_device_codes'));
        $this->assertSame('char(36)', Schema::getColumnType('oauth_clients', 'id', true));
        $this->assertSame('char(36)', Schema::getColumnType('oauth_access_tokens', 'client_id', true));
        $this->assertSame('char(36)', Schema::getColumnType('oauth_auth_codes', 'client_id', true));
    }

    public function test_new_confidential_clients_use_uuid_ids_and_hashed_secrets(): void
    {
        $client = (new ClientRepository())->createPasswordGrantClient(
            'Test client',
            config('auth.guards.api.provider'),
            true
        );

        $this->assertTrue((bool) preg_match('/^[0-9a-f-]{36}$/', (string) $client->id));
        $this->assertNotNull($client->plainSecret);
        $this->assertTrue(Hash::check($client->plainSecret, $client->getRawOriginal('secret')));
        $this->assertSame(['password', 'refresh_token'], $client->grant_types);
        $this->assertSame([], $client->redirect_uris);
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
            'api.v1.materials.show' => ['GET', 'api/v1/materials/{material}'],
            'api.v1.materials.copy' => ['GET', 'api/v1/materials/{material}/copy'],
            'api.v1.resources.store' => ['POST', 'api/v1/resources'],
            'api.v1.foreignMaterialStore' => ['POST', 'api/v1/foreign-materials/{foreignMaterialId}'],
            'api.v1.foreignResources.show' => ['GET', 'api/v1/foreign-resources/{foreignResourceId}'],
            'api.v1.bundles.update.init' => ['POST', 'api/v1/bundles/{bundle}/init-update'],
            'api.v1.bundles.index' => ['GET', 'api/v1/bundles'],
            'api.v1.bundles.show' => ['GET', 'api/v1/bundles/{bundle}'],
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

    public function test_route_names_are_unique_and_resolve_to_their_explicit_targets(): void
    {
        $this->assertSame(
            '/api/v1/materials/123',
            route('api.v1.materials.show', ['material' => 123], false)
        );
        $this->assertSame(
            '/api/v1/materials/123/copy',
            route('api.v1.materials.copy', ['material' => 123], false)
        );
        $this->assertSame('/api/v1/bundles', route('api.v1.bundles.index', [], false));
        $this->assertSame('/api/v1/bundles/456', route('api.v1.bundles.show', ['bundle' => 456], false));

        $names = collect(Route::getRoutes()->getRoutes())->pluck('action.as')->filter();
        $this->assertSame($names->count(), $names->unique()->count());
    }

    public function test_passport_route_signatures_remain_available(): void
    {
        $contracts = [
            'passport.token' => ['POST', 'oauth/token'],
            'passport.authorizations.authorize' => ['GET', 'oauth/authorize'],
            'passport.authorizations.approve' => ['POST', 'oauth/authorize'],
            'passport.authorizations.deny' => ['DELETE', 'oauth/authorize'],
            'passport.device' => ['GET', 'oauth/device'],
            'passport.device.code' => ['POST', 'oauth/device/code'],
            'passport.device.authorizations.authorize' => ['GET', 'oauth/device/authorize'],
        ];

        foreach ($contracts as $name => [$method, $uri]) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Missing Passport route {$name}.");
            $this->assertContains($method, $route->methods());
            $this->assertSame($uri, $route->uri());
        }

        foreach (['passport.clients.index', 'passport.personal.tokens.index', 'passport.tokens.index'] as $name) {
            $this->assertNull(Route::getRoutes()->getByName($name));
        }
    }
}
