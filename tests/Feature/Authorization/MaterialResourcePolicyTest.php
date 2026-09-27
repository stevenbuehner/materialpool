<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserStatus;
use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Models\Material;
use App\Models\Resource;
use App\Models\User;
use App\Services\Bundles\BundlePermissionService;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaterialResourcePolicyTest extends TestCase {
	use RefreshDatabase;

	public function test_material_metadata_and_structure_permissions_are_independent(): void {
		$owner = User::factory()->create();
		$material = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);

		$this->actingAs($owner);
		$this->assertTrue(Gate::allows('update', $material));
		$this->assertTrue(Gate::allows('updateMetadata', $material));

		$owner->syncRoles([]);
		$owner->refresh();
		$this->assertFalse(Gate::allows('update', $material));
		$this->assertFalse(Gate::allows('updateMetadata', $material));

		$role = Role::create(['name' => 'Nur Metadaten', 'guard_name' => 'web']);
		$role->givePermissionTo(SystemPermissions::MATERIALS_UPDATE_METADATA_OWN);
		$owner->assignRole($role);
		$this->assertFalse(Gate::allows('update', $material));
		$this->assertTrue(Gate::allows('updateMetadata', $material));
	}

	public function test_all_permission_applies_to_own_and_foreign_resources(): void {
		$owner = User::factory()->create();
		$user = User::factory()->create();
		$own = Resource::factory()->create(['created_by' => $user->id, 'is_public' => false]);
		$foreign = Resource::factory()->create(['created_by' => $owner->id, 'is_public' => false]);
		$user->syncRoles([]);
		$role = Role::create(['name' => 'Alle Ressourcen bearbeiten', 'guard_name' => 'web']);
		$role->givePermissionTo(SystemPermissions::RESOURCES_UPDATE_ALL);
		$user->assignRole($role);

		$this->actingAs($user);
		$this->assertTrue(Gate::allows('update', $own));
		$this->assertTrue(Gate::allows('update', $foreign));
		$this->assertFalse(Gate::allows('create', Resource::class));
	}

	public function test_global_admin_is_allowed_and_suspended_user_is_denied(): void {
		$admin = User::factory()->create(['is_admin' => true]);
		$this->actingAs($admin);
		$this->assertTrue(Gate::allows('create', Material::class));

		$admin->status = UserStatus::Suspended;
		$admin->save();
		$this->assertFalse(Gate::allows('create', Material::class));
	}

	public function test_material_visibility_scope_matches_the_view_policy(): void {
		$owner = User::factory()->create();
		$withoutPublicPermission = User::factory()->create();
		$withoutPublicPermission->syncRoles([]);
		$withPublicPermission = User::factory()->create();
		$withViewAllPermission = User::factory()->create();
		$withViewAllPermission->syncRoles([]);
		$viewAllRole = Role::create(['name' => 'Alle Materialien lesen', 'guard_name' => 'web']);
		$viewAllRole->givePermissionTo(SystemPermissions::MATERIALS_VIEW_ALL);
		$withViewAllPermission->assignRole($viewAllRole);
		$admin = User::factory()->create(['is_admin' => true]);
		$suspended = User::factory()->create(['status' => UserStatus::Suspended]);

		$ownerPrivate = Material::factory()->privatelyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$ownerPublic = Material::factory()->publiclyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$viewerPrivate = Material::factory()->privatelyVisible()->create(['created_by' => $withoutPublicPermission->id, 'modified_by' => $withoutPublicPermission->id]);

		$cases = [
			[$owner, [$ownerPrivate->id, $ownerPublic->id]],
			[$withoutPublicPermission, [$viewerPrivate->id]],
			[$withPublicPermission, [$ownerPublic->id]],
			[$withViewAllPermission, [$ownerPrivate->id, $ownerPublic->id, $viewerPrivate->id]],
			[$admin, [$ownerPrivate->id, $ownerPublic->id, $viewerPrivate->id]],
			[$suspended, []],
		];

		foreach ($cases as [$user, $expectedIds]) {
			$visibleIds = Material::visibleTo($user)->orderBy('id')->pluck('id')->all();
			sort($expectedIds);

			$this->assertSame($expectedIds, $visibleIds);

			foreach ([$ownerPrivate, $ownerPublic, $viewerPrivate] as $material) {
				$this->assertSame(
					in_array($material->id, $expectedIds, true),
					Gate::forUser($user)->allows('view', $material)
				);
			}
		}

		$this->assertSame(404, Gate::forUser($withoutPublicPermission)->inspect('view', $ownerPrivate)->status());
	}

	public function test_resource_visibility_scope_matches_the_view_policy(): void {
		$owner = User::factory()->create();
		$viewer = User::factory()->create();
		$withViewAllPermission = User::factory()->create();
		$withViewAllPermission->syncRoles([]);
		$viewAllRole = Role::create(['name' => 'Alle Ressourcen lesen', 'guard_name' => 'web']);
		$viewAllRole->givePermissionTo(SystemPermissions::RESOURCES_VIEW_ALL);
		$withViewAllPermission->assignRole($viewAllRole);
		$admin = User::factory()->create(['is_admin' => true]);
		$suspended = User::factory()->create(['status' => UserStatus::Suspended]);

		$ownerPrivate = Resource::factory()->create(['created_by' => $owner->id, 'is_public' => false]);
		$ownerPublic = Resource::factory()->create(['created_by' => $owner->id, 'is_public' => true]);
		$viewerPrivate = Resource::factory()->create(['created_by' => $viewer->id, 'is_public' => false]);

		$cases = [
			[$owner, [$ownerPrivate->id, $ownerPublic->id]],
			[$viewer, [$ownerPublic->id, $viewerPrivate->id]],
			[$withViewAllPermission, [$ownerPrivate->id, $ownerPublic->id, $viewerPrivate->id]],
			[$admin, [$ownerPrivate->id, $ownerPublic->id, $viewerPrivate->id]],
			[$suspended, []],
		];

		foreach ($cases as [$user, $expectedIds]) {
			$visibleIds = Resource::visibleTo($user)->orderBy('id')->pluck('id')->all();
			sort($expectedIds);

			$this->assertSame($expectedIds, $visibleIds);

			foreach ([$ownerPrivate, $ownerPublic, $viewerPrivate] as $resource) {
				$this->assertSame(
					in_array($resource->id, $expectedIds, true),
					Gate::forUser($user)->allows('view', $resource)
				);
			}
		}

		$this->assertSame(404, Gate::forUser($viewer)->inspect('view', $ownerPrivate)->status());
	}

	public function test_bundle_materials_require_their_bundle_read_permission_even_when_public_or_owned(): void {
		$owner = User::factory()->create();
		$reader = User::factory()->create();
		$admin = User::factory()->create(['is_admin' => true]);
		$bundle = Bundle::factory()->create(['is_installed' => false]);
		$material = Material::factory()->publiclyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		ForeignMaterialId::create([
			'material_id' => $material->id,
			'foreign_id' => 'bundle-material-' . $material->id,
			'user_id' => $owner->id,
			'bundle_id' => $bundle->id,
		]);

		$this->assertFalse(Gate::forUser($owner)->allows('view', $material));
		$this->assertFalse(Gate::forUser($reader)->allows('view', $material));
		$this->assertTrue(Gate::forUser($admin)->allows('view', $material));
		$this->assertSame([], Material::visibleTo($reader)->pluck('id')->all());

		$reader->givePermissionTo(app(BundlePermissionService::class)->ensureFor($bundle));
		$this->assertTrue(Gate::forUser($reader)->allows('view', $material));
		$this->assertSame([$material->id], Material::visibleTo($reader)->pluck('id')->all());
	}

	public function test_bundle_resources_require_their_bundle_read_permission_and_permissions_survive_uninstallation(): void {
		$owner = User::factory()->create();
		$reader = User::factory()->create();
		$bundle = Bundle::factory()->create(['is_installed' => false]);
		$resource = Resource::factory()->create(['created_by' => $owner->id, 'is_public' => true]);
		ForeignResourceId::create([
			'resource_id' => $resource->id,
			'foreign_id' => 'bundle-resource-' . $resource->id,
			'user_id' => $owner->id,
			'bundle_id' => $bundle->id,
		]);

		$this->assertFalse(Gate::forUser($reader)->allows('view', $resource));
		$this->assertSame([], Resource::visibleTo($reader)->pluck('id')->all());

		$permission = app(BundlePermissionService::class)->ensureFor($bundle);
		$reader->givePermissionTo($permission);
		$this->assertTrue(Gate::forUser($reader)->allows('view', $resource));
		$this->assertSame([$resource->id], Resource::visibleTo($reader)->pluck('id')->all());
		$this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => $permission->name]);
	}
}
