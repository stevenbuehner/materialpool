<?php

namespace Tests\Feature;

use App\Models\ForeignMaterialId;
use App\Models\Material;
use App\Models\User;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaterialVisibilityWriteTest extends TestCase {
	use RefreshDatabase;

	public function test_api_material_creation_persists_an_explicit_private_visibility(): void {
		$user = User::factory()->create();

		Passport::actingAs($user);
		$response = $this->postJson(route('api.v1.materials.store'), [
			'title' => 'Privates API-Material',
			'is_public' => false,
		]);

		$response->assertOk()->assertJsonPath('is_public', false);
		$this->assertDatabaseHas('materials', ['title' => 'Privates API-Material', 'is_public' => false]);
	}

	public function test_api_material_creation_uses_the_public_default_without_a_visibility_value(): void {
		$user = User::factory()->create();

		Passport::actingAs($user);
		$response = $this->postJson(route('api.v1.materials.store'), ['title' => 'API-Material mit Standardwert']);

		$response->assertOk()->assertJsonPath('is_public', true);
		$this->assertDatabaseHas('materials', ['title' => 'API-Material mit Standardwert', 'is_public' => true]);
	}

	public function test_api_material_update_persists_an_explicit_private_visibility(): void {
		$user = User::factory()->create();
		$material = Material::factory()->publiclyVisible()->create(['created_by' => $user->id, 'modified_by' => $user->id]);

		Passport::actingAs($user);
		$response = $this->putJson(route('api.v1.materials.update', $material), ['is_public' => false]);

		$response->assertOk()->assertJsonPath('is_public', false);
		$this->assertDatabaseHas('materials', ['id' => $material->id, 'is_public' => false]);
	}

	public function test_web_material_update_persists_an_explicit_private_visibility(): void {
		$user = User::factory()->create();
		$material = Material::factory()->publiclyVisible()->create(['created_by' => $user->id, 'modified_by' => $user->id]);

		$response = $this->actingAs($user)->put(route('pool.material.update', $material), ['is_public' => false]);

		$response->assertRedirect(route('pool.material.show', $material));
		$this->assertDatabaseHas('materials', ['id' => $material->id, 'is_public' => false]);
	}

	public function test_api_material_update_forbids_visibility_changes_without_metadata_permission(): void {
		$user = User::factory()->create();
		$user->syncRoles([]);
		$createOnlyRole = Role::create(['name' => 'Nur Material anlegen', 'guard_name' => 'web']);
		$createOnlyRole->givePermissionTo(SystemPermissions::MATERIALS_CREATE);
		$user->assignRole($createOnlyRole);
		$material = Material::factory()->publiclyVisible()->create(['created_by' => $user->id, 'modified_by' => $user->id]);

		Passport::actingAs($user);
		$response = $this->putJson(route('api.v1.materials.update', $material), ['is_public' => false]);

		$response->assertForbidden();
		$this->assertDatabaseHas('materials', ['id' => $material->id, 'is_public' => true]);
	}

	public function test_api_material_creation_rejects_an_invalid_visibility_value(): void {
		$user = User::factory()->create();

		Passport::actingAs($user);
		$response = $this->postJson(route('api.v1.materials.store'), [
			'title' => 'Ungültige Sichtbarkeit',
			'is_public' => 'nein',
		]);

		$response->assertUnprocessable()->assertJsonValidationErrors(['is_public']);
		$this->assertDatabaseMissing('materials', ['title' => 'Ungültige Sichtbarkeit']);
	}

	public function test_foreign_material_update_requires_metadata_permission_for_visibility_changes(): void {
		$user = User::factory()->create();
		$user->syncRoles([]);
		$material = Material::factory()->publiclyVisible()->create(['created_by' => $user->id, 'modified_by' => $user->id]);
		$foreignMaterialId = ForeignMaterialId::factory()->create(['user_id' => $user->id, 'material_id' => $material->id]);

		Passport::actingAs($user);
		$response = $this->putJson(route('api.v1.foreignMaterialUpdate', $foreignMaterialId), [
			'title' => 'Privat per Fremd-ID',
			'is_public' => false,
		]);

		$response->assertForbidden();
		$this->assertDatabaseHas('materials', ['id' => $material->id, 'is_public' => true]);
	}

	public function test_foreign_material_update_persists_visibility_with_metadata_permission(): void {
		$user = User::factory()->create();
		$material = Material::factory()->publiclyVisible()->create(['created_by' => $user->id, 'modified_by' => $user->id]);
		$foreignMaterialId = ForeignMaterialId::factory()->create(['user_id' => $user->id, 'material_id' => $material->id]);

		Passport::actingAs($user);
		$response = $this->putJson(route('api.v1.foreignMaterialUpdate', $foreignMaterialId), [
			'title' => 'Privat per Fremd-ID',
			'is_public' => false,
		]);

		$response->assertOk()->assertJsonPath('is_public', false);
		$this->assertDatabaseHas('materials', ['id' => $material->id, 'is_public' => false]);
	}
}
