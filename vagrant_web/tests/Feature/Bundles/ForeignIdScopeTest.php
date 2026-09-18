<?php

namespace Tests\Feature\Bundles;

use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Models\Material;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForeignIdScopeTest extends TestCase {
	use RefreshDatabase;

	public function test_the_same_foreign_id_can_exist_in_two_bundle_scopes_for_one_user(): void {
		$owner = User::factory()->create();
		$firstBundle = Bundle::factory()->create();
		$secondBundle = Bundle::factory()->create();
		$firstMaterial = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$secondMaterial = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);

		$first = ForeignMaterialId::create(['material_id' => $firstMaterial->id, 'user_id' => $owner->id, 'foreign_id' => 'shared-id', 'bundle_id' => $firstBundle->id]);
		$second = ForeignMaterialId::create(['material_id' => $secondMaterial->id, 'user_id' => $owner->id, 'foreign_id' => 'shared-id', 'bundle_id' => $secondBundle->id]);

		$this->assertSame("bundle:{$firstBundle->id}", $first->scope_key);
		$this->assertSame("bundle:{$secondBundle->id}", $second->scope_key);
		$this->assertSame($first->id, ForeignMaterialId::query()->where(['bundle_id' => $firstBundle->id, 'foreign_id' => 'shared-id'])->value('id'));
		$this->assertSame($second->id, ForeignMaterialId::query()->where(['bundle_id' => $secondBundle->id, 'foreign_id' => 'shared-id'])->value('id'));
	}

	public function test_non_bundle_foreign_ids_keep_their_user_scope(): void {
		$owner = User::factory()->create();
		$resource = Resource::factory()->create(['created_by' => $owner->id]);

		$foreignId = ForeignResourceId::create(['resource_id' => $resource->id, 'user_id' => $owner->id, 'foreign_id' => 'api-id']);

		$this->assertSame("user:{$owner->id}", $foreignId->scope_key);
	}
}
