<?php

namespace Tests\Feature\Authorization;

use App\Models\Material;
use App\Models\User;
use App\Models\Bundle;
use App\Services\Bundles\BundlePermissionService;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionCatalogTest extends TestCase {
	use RefreshDatabase;

	public function test_permission_schema_and_default_group_are_installed(): void {
		foreach (['roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'] as $table) {
			$this->assertTrue(Schema::hasTable($table));
		}
		$this->assertTrue(Schema::hasColumn('users', 'status'));
		$this->assertTrue(Schema::hasColumn('materials', 'is_public'));
		$this->assertEqualsCanonicalizing(SystemPermissions::all(), Permission::pluck('name')->all());

		$role = Role::findByName(SystemPermissions::DEFAULT_GROUP);
		$this->assertEqualsCanonicalizing(SystemPermissions::defaultGroup(), $role->permissions->pluck('name')->all());
	}

	public function test_regular_factory_user_receives_default_group_and_effective_permissions(): void {
		$user = User::factory()->create();
		$material = Material::factory()->create();

		$this->assertTrue($user->hasRole(SystemPermissions::DEFAULT_GROUP));
		$this->assertTrue($user->can(SystemPermissions::MATERIALS_CREATE));
		$this->assertTrue($user->can(SystemPermissions::MATERIALS_VIEW_PUBLIC));
		$this->assertFalse($user->can(SystemPermissions::MATERIALS_UPDATE_ALL));
		$this->assertTrue($material->is_public);
	}

	public function test_bundle_permission_discovery_does_not_grant_existing_roles_or_users_access(): void {
		$bundle = Bundle::factory()->create();
		$permission = app(BundlePermissionService::class)->ensureFor($bundle);
		$user = User::factory()->create();
		$defaultRole = Role::findByName(SystemPermissions::DEFAULT_GROUP);

		$this->assertFalse($defaultRole->hasPermissionTo($permission));
		$this->assertFalse($user->can($permission->name));
	}
}
