<?php

namespace Tests\Feature\Bundles;

use App\Jobs\Bundle\DeleteMaterialIfNeeded;
use App\Jobs\Bundle\DeleteResourceIfNeeded;
use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Models\Material;
use App\Models\Resource;
use App\Models\User;
use App\Services\Bundles\BundlesService;
use App\Services\MaterialHandling\MaterialHandlingService;
use App\Services\ResourceHandling\FileHandlingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BundleUninstallWithoutSourceTest extends TestCase {
	use RefreshDatabase;

	public function test_uninstall_deletes_a_bundle_material_without_reading_the_missing_source(): void {
		$owner = User::factory()->create();
		$bundle = Bundle::factory()->create();
		$material = Material::factory()->create(['from_bot' => true, 'created_by' => $owner->id, 'modified_by' => $owner->id]);
		$foreignId = ForeignMaterialId::create([
			'material_id' => $material->id,
			'user_id' => $owner->id,
			'foreign_id' => 'missing-source-material',
			'bundle_id' => $bundle->id,
		])->fresh(['material.foreignIds']);
		$bundles = Mockery::mock(BundlesService::class);
		$bundles->shouldNotReceive('hasMaterial');
		$materials = Mockery::mock(MaterialHandlingService::class);
		$materials->shouldReceive('deleteMaterialAndDetachAssociations')->once()->with(Mockery::type(Material::class));

		(new DeleteMaterialIfNeeded($bundle, $foreignId, '2.0.0', true))->handle($bundles, $materials);

		$this->assertDatabaseMissing('material_foreign_ids', ['id' => $foreignId->id]);
	}

	public function test_uninstall_deletes_an_unshared_bundle_resource_without_reading_the_missing_source(): void {
		$owner = User::factory()->create();
		$bundle = Bundle::factory()->create();
		$resource = Resource::factory()->create(['created_by' => $owner->id]);
		$foreignId = ForeignResourceId::create([
			'resource_id' => $resource->id,
			'user_id' => $owner->id,
			'foreign_id' => 'missing-source-resource',
			'bundle_id' => $bundle->id,
		])->fresh(['resource.materials', 'resource.foreignIds']);
		$bundles = Mockery::mock(BundlesService::class);
		$bundles->shouldNotReceive('hasFile');
		$files = Mockery::mock(FileHandlingService::class);
		$files->shouldReceive('deleteResourceCompletely')->once()->with(Mockery::type(Resource::class), true);

		(new DeleteResourceIfNeeded($bundle, $foreignId, '2.0.0', true))->handle($bundles, $files);
	}
}
