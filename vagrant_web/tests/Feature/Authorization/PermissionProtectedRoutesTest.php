<?php

namespace Tests\Feature\Authorization;

use App\Models\Material;
use App\Models\Resource;
use App\Models\User;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionProtectedRoutesTest extends TestCase {
	use RefreshDatabase;

	public function test_metadata_and_resource_assignments_require_their_separate_permissions(): void {
		$user = User::factory()->create();
		$material = Material::factory()->create(['created_by' => $user->id, 'modified_by' => $user->id]);
		$resource = Resource::factory()->create(['created_by' => $user->id, 'is_public' => true]);
		$user->syncRoles([]);
		Passport::actingAs($user);

		$this->putJson(route('api.v1.materials.update', $material), ['title' => 'Neu'])->assertForbidden();
		$this->postJson(route('api.v2.api.v2.materialresource.attach', [$material, $resource]))->assertForbidden();

		$metadataRole = Role::create(['name' => 'Eigene Metadaten', 'guard_name' => 'web']);
		$metadataRole->givePermissionTo(SystemPermissions::MATERIALS_UPDATE_METADATA_OWN);
		$user->assignRole($metadataRole);
		$this->putJson(route('api.v1.materials.update', $material), ['title' => 'Neu'])
			->assertOk()
			->assertJsonPath('title', 'Neu');
		$this->postJson(route('api.v2.api.v2.materialresource.attach', [$material, $resource]))->assertForbidden();
	}

	public function test_create_and_copy_require_material_create_permission(): void {
		$owner = User::factory()->create();
		$material = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$user = User::factory()->create();
		$user->syncRoles([]);
		$reader = Role::create(['name' => 'Öffentliche Materialien lesen', 'guard_name' => 'web']);
		$reader->givePermissionTo(SystemPermissions::MATERIALS_VIEW_PUBLIC);
		$user->assignRole($reader);
		Passport::actingAs($user);

		$this->postJson(route('api.v1.materials.store'), ['title' => 'Neu'])->assertForbidden();
		$this->getJson(route('api.v1.materials.copy', $material))->assertForbidden();

		$creator = Role::create(['name' => 'Material anlegen', 'guard_name' => 'web']);
		$creator->givePermissionTo(SystemPermissions::MATERIALS_CREATE);
		$user->assignRole($creator);
		$this->postJson(route('api.v1.materials.store'), ['title' => 'Neu'])->assertOk();
		$this->getJson(route('api.v1.materials.copy', $material))->assertOk();
	}
}
