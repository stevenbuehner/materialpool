<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserStatus;
use App\Models\Material;
use App\Models\Resource;
use App\Models\User;
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
}
