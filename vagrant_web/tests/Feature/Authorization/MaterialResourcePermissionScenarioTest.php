<?php

namespace Tests\Feature\Authorization;

use App\Models\Bibleverse;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Resource;
use App\Models\User;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaterialResourcePermissionScenarioTest extends TestCase {
	use RefreshDatabase;

	public function test_material_create_permission_allows_initial_metadata_without_update_metadata_permission(): void {
		$user = $this->userWithOnlyPermissions([SystemPermissions::MATERIALS_CREATE]);

		Passport::actingAs($user);
		$response = $this->postJson(route('api.v1.materials.store'), [
			'title' => 'Material mit initialen Metadaten',
			'description' => 'Die Metadaten gehören zum Anlegen.',
			'rating' => 12,
			'is_public' => false,
			'author' => 'Initialer Autor',
			'keywords' => [[
				'title' => 'Initiales Schlagwort',
				'type' => 'key',
				'relevance' => 100,
			]],
		]);

		$response->assertOk()->assertJsonPath('is_public', false);
		$materialId = $response->json('id');
		$this->assertDatabaseHas('materials', [
			'id' => $materialId,
			'created_by' => $user->id,
			'title' => 'Material mit initialen Metadaten',
			'is_public' => false,
		]);
		$this->assertDatabaseHas('keywords', ['title' => 'Initiales Schlagwort', 'type' => 'key']);
	}

	public function test_resource_creation_requires_resource_create_and_material_create_for_automatic_material_creation(): void {
		$user = $this->userWithOnlyPermissions([]);
		$payload = [
			'content' => 'Eine neue Textressource.',
			'notes' => 'Testnotiz',
			'create_material_from_resource' => true,
			'foreign_material_id' => 'permission-scenario-resource',
		];

		Passport::actingAs($user);
		// Ohne resources.create darf weder die Resource noch ein Material entstehen.
		$this->postJson(route('api.v1.resources.store'), $payload)->assertForbidden();
		$this->assertDatabaseCount('resources', 0);
		$this->assertDatabaseCount('materials', 0);

		$user->assignRole($this->roleWithPermissions('Nur Resource anlegen', [SystemPermissions::RESOURCES_CREATE]));
		// Die automatische Materialanlage ist ein zweiter, eigenständiger Berechtigungsschritt.
		$this->postJson(route('api.v1.resources.store'), $payload)->assertForbidden();
		$this->assertDatabaseCount('resources', 0);
		$this->assertDatabaseCount('materials', 0);

		$user->assignRole($this->roleWithPermissions('Material anlegen', [SystemPermissions::MATERIALS_CREATE]));
		$this->postJson(route('api.v1.resources.store'), $payload)->assertOk();
		$this->assertDatabaseCount('resources', 1);
		$this->assertDatabaseCount('materials', 1);
	}

	public function test_direct_material_link_returns_404_for_private_foreign_material_and_allows_reading_permissions(): void {
		$owner = User::factory()->create();
		$privateMaterial = Material::factory()->privatelyVisible()->create([
			'created_by' => $owner->id,
			'modified_by' => $owner->id,
		]);
		$viewer = $this->userWithOnlyPermissions([]);

		Passport::actingAs($viewer);
		// Nicht sichtbare IDs dürfen ihre Existenz nicht über einen direkten Link verraten.
		$this->getJson(route('api.v1.materials.show', $privateMaterial))->assertNotFound();

		$viewer->assignRole($this->roleWithPermissions('Alle Materialien lesen', [SystemPermissions::MATERIALS_VIEW_ALL]));
		$this->getJson(route('api.v1.materials.show', $privateMaterial))
			->assertOk()
			->assertJsonPath('id', $privateMaterial->id);

		$admin = User::factory()->create(['is_admin' => true]);
		Passport::actingAs($admin);
		$this->getJson(route('api.v1.materials.show', $privateMaterial))
			->assertOk()
			->assertJsonPath('id', $privateMaterial->id);
	}

	public function test_direct_resource_link_returns_404_for_private_foreign_resource_and_allows_view_all(): void {
		$owner = User::factory()->create();
		$privateResource = Resource::factory()->create(['created_by' => $owner->id, 'is_public' => false]);
		$viewer = $this->userWithOnlyPermissions([]);

		Passport::actingAs($viewer);
		$this->getJson(route('api.v1.resources.show', $privateResource))->assertNotFound();

		$viewer->assignRole($this->roleWithPermissions('Alle Ressourcen lesen', [SystemPermissions::RESOURCES_VIEW_ALL]));
		$this->getJson(route('api.v1.resources.show', $privateResource))
			->assertOk()
			->assertJsonPath('id', $privateResource->id);
	}

	public function test_all_update_permissions_apply_to_foreign_materials_and_resources_without_changing_own_permissions(): void {
		$owner = User::factory()->create();
		$material = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$resource = Resource::factory()->create(['created_by' => $owner->id, 'notes' => 'Unverändert']);
		$editor = $this->userWithOnlyPermissions([]);

		Passport::actingAs($editor);
		// Own-Rechte gelten nicht für fremde Datensätze, selbst wenn diese öffentlich sind.
		$editor->assignRole($this->roleWithPermissions('Eigene Inhalte ändern', [
			SystemPermissions::MATERIALS_UPDATE_METADATA_OWN,
			SystemPermissions::RESOURCES_UPDATE_OWN,
		]));
		$this->putJson(route('api.v1.materials.update', $material), ['title' => 'Nicht erlaubt'])->assertForbidden();
		$this->putJson(route('api.v1.resources.update', $resource), ['notes' => 'Nicht erlaubt'])->assertForbidden();
		$this->assertDatabaseMissing('materials', ['id' => $material->id, 'title' => 'Nicht erlaubt']);
		$this->assertDatabaseHas('resources', ['id' => $resource->id, 'notes' => 'Unverändert']);

		$editor->assignRole($this->roleWithPermissions('Alle Inhalte ändern', [
			SystemPermissions::MATERIALS_UPDATE_METADATA_ALL,
			SystemPermissions::RESOURCES_UPDATE_ALL,
		]));
		$this->putJson(route('api.v1.materials.update', $material), ['title' => 'Global erlaubt'])
			->assertOk()
			->assertJsonPath('title', 'Global erlaubt');
		$this->putJson(route('api.v1.resources.update', $resource), ['notes' => 'Global erlaubt'])
			->assertOk()
			->assertJsonPath('notes', 'Global erlaubt');
		$this->assertDatabaseHas('materials', ['id' => $material->id, 'title' => 'Global erlaubt']);
		$this->assertDatabaseHas('resources', ['id' => $resource->id, 'notes' => 'Global erlaubt']);
	}

	public function test_material_structure_permissions_keep_own_and_all_assignments_separate(): void {
		$owner = User::factory()->create();
		$material = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$resource = Resource::factory()->create(['created_by' => $owner->id, 'is_public' => true]);
		$editor = $this->userWithOnlyPermissions([SystemPermissions::MATERIALS_UPDATE_OWN]);

		Passport::actingAs($editor);
		// Ein Own-Recht darf keine fremde Material-Resource-Beziehung verändern.
		$this->postJson(route('api.v2.api.v2.materialresource.attach', [$material, $resource]))->assertForbidden();
		$this->assertDatabaseMissing('material_resource', [
			'material_id' => $material->id,
			'resource_id' => $resource->id,
		]);

		$editor->assignRole($this->roleWithPermissions('Alle Materialstrukturen ändern', [SystemPermissions::MATERIALS_UPDATE_ALL]));
		$this->postJson(route('api.v2.api.v2.materialresource.attach', [$material, $resource]))->assertOk();
		$this->assertDatabaseHas('material_resource', [
			'material_id' => $material->id,
			'resource_id' => $resource->id,
		]);
	}

	public function test_metadata_permission_controls_keyword_bibleverse_and_author_changes(): void {
		$owner = $this->userWithOnlyPermissions([SystemPermissions::MATERIALS_UPDATE_METADATA_OWN]);
		$foreignOwner = User::factory()->create();
		$ownMaterial = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$foreignMaterial = Material::factory()->create(['created_by' => $foreignOwner->id, 'modified_by' => $foreignOwner->id]);
		$keyword = Keyword::factory()->create(['type' => 'key']);
		$bibleverse = Bibleverse::factory()->create();
		$author = Keyword::factory()->create(['type' => 'person']);

		Passport::actingAs($owner);
		// Tags, Bibelstellen und Autoren sind Metadaten und folgen derselben Own-/All-Grenze.
		$this->putJson(route('api.v1.keywords.updateAssignment', [$ownMaterial, $keyword]), ['relevance' => 100])->assertOk();
		$this->putJson(route('api.v1.bibleverses.createOrUpdateAssignment', [$ownMaterial, $bibleverse]), ['relevance' => 100])->assertOk();
		$this->putJson(route('api.v1.materials.update', $ownMaterial), ['author' => ['id' => $author->id]])->assertOk();
		$this->assertDatabaseHas('keyword_material', ['material_id' => $ownMaterial->id, 'keyword_id' => $keyword->id]);
		$this->assertDatabaseHas('bibleverse_material', ['material_id' => $ownMaterial->id, 'bibleverse_id' => $bibleverse->id]);
		$this->assertDatabaseHas('materials', ['id' => $ownMaterial->id, 'author_id' => $author->id]);

		$this->putJson(route('api.v1.keywords.updateAssignment', [$foreignMaterial, $keyword]), ['relevance' => 100])->assertForbidden();
		$this->assertDatabaseMissing('keyword_material', ['material_id' => $foreignMaterial->id, 'keyword_id' => $keyword->id]);
	}

	public function test_classic_private_material_and_resource_links_return_404_for_foreign_users(): void {
		$owner = User::factory()->create();
		$viewer = $this->userWithOnlyPermissions([]);
		$material = Material::factory()->privatelyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$resource = Resource::factory()->create(['created_by' => $owner->id, 'is_public' => false]);

		$this->actingAs($viewer);
		// Die klassischen Links dürfen genauso wenig wie die API die Existenz privater Fremddaten verraten.
		$this->get(route('pool.material.show', $material))->assertNotFound();
		$this->get(route('pool.resource.show', $resource))->assertNotFound();
	}

	public function test_delete_permissions_keep_own_and_all_records_separate(): void {
		$owner = User::factory()->create();
		$material = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$resource = Resource::factory()->create(['created_by' => $owner->id]);
		$editor = $this->userWithOnlyPermissions([
			SystemPermissions::MATERIALS_DELETE_OWN,
			SystemPermissions::RESOURCES_DELETE_OWN,
		]);

		Passport::actingAs($editor);
		// Own-Löschrechte dürfen keinen fremden Datensatz entfernen.
		$this->deleteJson(route('api.v2.api.v2.material.delete', $material))->assertForbidden();
		$this->deleteJson(route('api.v1.resources.delete', $resource))->assertForbidden();
		$this->assertDatabaseHas('materials', ['id' => $material->id]);
		$this->assertDatabaseHas('resources', ['id' => $resource->id]);

		$editor->assignRole($this->roleWithPermissions('Alle Inhalte löschen', [
			SystemPermissions::MATERIALS_DELETE_ALL,
			SystemPermissions::RESOURCES_DELETE_ALL,
		]));
		$this->deleteJson(route('api.v2.api.v2.material.delete', $material))->assertOk();
		$this->deleteJson(route('api.v1.resources.delete', $resource))->assertOk();
		$this->assertDatabaseMissing('materials', ['id' => $material->id]);
		$this->assertDatabaseMissing('resources', ['id' => $resource->id]);
	}

	public function test_search_visibility_keeps_material_and_resource_permissions_independent(): void {
		$owner = User::factory()->create();
		$privateMaterial = Material::factory()->privatelyVisible()->create([
			'created_by' => $owner->id,
			'modified_by' => $owner->id,
			'title' => 'Privater Suchtreffer',
		]);
		$privateResource = Resource::factory()->create(['created_by' => $owner->id, 'is_public' => false]);
		$privateMaterial->resources()->attach($privateResource);
		$viewer = $this->userWithOnlyPermissions([SystemPermissions::MATERIALS_VIEW_ALL]);

		$this->actingAs($viewer);
		// Material-Leserecht gibt weder den Resource-Inhalt preis noch lässt es private Resources als Typ-Treffer wirken.
		$this->postJson(route('pool.searchbar.get'), ['q' => [[['type' => '*', 'text' => 'Privater Suchtreffer']]]])
			->assertOk()
			->assertJsonPath('data.0.id', $privateMaterial->id)
			->assertJsonPath('data.0.resources', []);
		$this->postJson(route('pool.searchbar.get'), ['q' => [[['type' => 't', 'text' => $privateResource->type]]]])
			->assertOk()
			->assertJsonPath('total', 0);

		$viewer->assignRole($this->roleWithPermissions('Alle Ressourcen lesen', [SystemPermissions::RESOURCES_VIEW_ALL]));
		$this->postJson(route('pool.searchbar.get'), ['q' => [[['type' => 't', 'text' => $privateResource->type]]]])
			->assertOk()
			->assertJsonPath('data.0.id', $privateMaterial->id)
			->assertJsonPath('data.0.resources.0.id', $privateResource->id);
	}

	private function userWithOnlyPermissions(array $permissions): User {
		$user = User::factory()->create();
		$user->syncRoles([]);

		if ($permissions !== []) {
			$user->assignRole($this->roleWithPermissions('Berechtigungsszenario '.implode('-', $permissions), $permissions));
		}

		return $user;
	}

	private function roleWithPermissions(string $name, array $permissions): Role {
		$role = Role::create(['name' => $name, 'guard_name' => 'web']);
		$role->givePermissionTo($permissions);

		return $role;
	}
}
