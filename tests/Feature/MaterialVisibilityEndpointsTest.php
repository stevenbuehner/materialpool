<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class MaterialVisibilityEndpointsTest extends TestCase {
	use RefreshDatabase;

	public function test_resource_find_returns_an_empty_page_when_no_resources_exist(): void {
		Passport::actingAs(User::factory()->create());

		$this->getJson(route('api.v1.resources.find', [
			'order_by' => 'id',
			'order_dir' => 'desc',
			'page' => 1,
		]))->assertOk()->assertJsonPath('total', 0)->assertJsonPath('data', []);
	}

	public function test_api_material_index_returns_only_materials_visible_to_the_current_user(): void {
		$owner = User::factory()->create();
		$viewer = User::factory()->create();
		$viewer->syncRoles([]);
		$ownMaterial = Material::factory()->create(['created_by' => $viewer->id, 'modified_by' => $viewer->id]);
		$privateMaterial = Material::factory()->privatelyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$publicMaterial = Material::factory()->publiclyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);

		Passport::actingAs($viewer);
		$response = $this->getJson(route('api.v1.materials.index'));

		$response->assertOk();
		$this->assertSame([$ownMaterial->id], collect($response->json('data'))->pluck('id')->all());
		$response->assertJsonMissing(['id' => $privateMaterial->id]);
		$response->assertJsonMissing(['id' => $publicMaterial->id]);
	}

	public function test_material_detail_does_not_serialize_a_private_foreign_resource(): void {
		$owner = User::factory()->create();
		$viewer = User::factory()->create();
		$material = Material::factory()->publiclyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$resource = Resource::factory()->create(['created_by' => $owner->id, 'is_public' => false]);
		$material->resources()->attach($resource);

		Passport::actingAs($viewer);
		$response = $this->getJson(route('api.v1.materials.show', $material));

		$response->assertOk();
		$response->assertJsonPath('resources', []);
	}

	public function test_resource_detail_does_not_serialize_a_private_foreign_material(): void {
		$owner = User::factory()->create();
		$viewer = User::factory()->create();
		$resource = Resource::factory()->create(['created_by' => $owner->id, 'is_public' => true]);
		$material = Material::factory()->privatelyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$resource->materials()->attach($material);

		Passport::actingAs($viewer);
		$response = $this->getJson(route('api.v1.resources.show', $resource));

		$response->assertOk();
		$response->assertJsonPath('materials', []);
	}

}
