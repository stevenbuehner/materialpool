<?php

namespace Tests\Feature;

use App\Models\ForeignInstance;
use App\Models\ForeignResourceKey;
use App\Models\Material;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ForeignInstanceMaterialApiControllerTest extends TestCase {

	use DatabaseMigrations, ResourceTrait;

	public function setUp() {
		parent::setUp();

		$this->setUpTestData();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testIndex() {
		/** @var ForeignInstance $fi */
		$fi = ForeignInstance::find(1);

		$fi->load(['foreignResourceKeys.resource.materials' => function ($q) use (&$materials) {
			$materials = $q->get()->unique();
		}]);


		$countsResources = $fi->foreignResourceKeys->count();
		$countsMaterials = $materials->count();
		$this->assertGreaterThan(0, $countsResources);
		$this->assertGreaterThan(0, $countsMaterials);

		// Resources-Uri
		$uri = route('foreignInstanceMaterialIndex', ['foreignInstance' => $fi->id]);

		$response = $this->json('get', $uri);
		$response->assertStatus(200);
		$response->assertJsonStructure(['total', 'per_page', 'current_page', 'last_page', 'next_page_url', 'prev_page_url', 'from', 'to',
										'data' => [
											'*' => [
												'id',
												'title',
												'description',
												'limitation',
												'rating',
												'from_bot',
												'created_by',
												'modified_by',
												'created_at',
												'updated_at',
												'keywords'  => [
													'*' => [
														'id',
														'title',
														'type',
														'mat_keyword_rating'
													]
												],
												'resources' => [
													'*' => [
														'id',
														'remote_path',
														'notes',
														'is_public',
														'type',
														'created_at',
														'updated_at',
														'remote_id'
													]
												]
											]
										]
									   ]);
		$response->assertJson(['total' => $countsMaterials, 'current_page' => 1, 'from' => 1]);


		foreach ($response->decodeResponseJson()['data'] as $material) {
			/** @var Material $mDb */
			$mDb = Material::find($material['id']);
			unset($material['keywords']);
			unset($material['resources']);

			$this->assertArraySubset($material, $mDb->toArray());
		}

	}

	public function testStoreMaterial() {
		/** @var ForeignInstance $fi */
		$fi = ForeignInstance::find(1);

		/** @var Resource $resource */
		$frk                  = $fi->foreignResourceKeys->first();
		$resource             = $frk->resource;
		$counMatBeforeRequest = $resource->materials->count();

		$this->assertTrue($fi->exists);
		$this->assertTrue($frk->exists);
		$this->assertTrue($resource->exists);

		$matToCreate              = factory(Material::class)->make(['from_bot' => TRUE]);
		$matToCreate->created_by  = $resource->created_by;
		$matToCreate->modified_by = $resource->created_by;


		// Resources-Uri
		$uri = route('foreignInstanceMaterialStore', ['foreignInstance'  => $fi->id,
													  'remoteResourceId' => $frk->remote_id]);

		$response = $this->json('post', $uri, $matToCreate->toArray());
		$response->assertStatus(200);

		$resource->load('materials');
		/** @var Material $createdMat */
		$createdMat = $resource->materials()->orderBy('id')->get()->last();

		$this->assertNotNull($createdMat);
		$this->assertEquals($counMatBeforeRequest + 1, $resource->materials->count());
		$this->assertArraySubset($matToCreate->toArray(), $createdMat->toArray());
	}


	public function testStoreMaterialNotExistantForThisForeignInstance() {
		/** @var ForeignInstance $fi1 */
		$fi1 = ForeignInstance::find(1);
		$fi2 = ForeignInstance::find(2);

		/** @var Resource $resource1 */
		$frk       = $fi1->foreignResourceKeys->first();
		$resource1 = $frk->resource;

		$this->assertTrue($fi1->exists);
		$this->assertTrue($frk->exists);
		$this->assertTrue($resource1->exists);

		$nothingToFindFRK = ForeignResourceKey::findOneWhere($fi2->id, $resource1->id);
		$this->assertNull($nothingToFindFRK);

		$matToCreate              = factory(Material::class)->make();

		// Resources-Uri
		$uri = route('foreignInstanceMaterialStore', ['foreignInstance'  => $fi2->id,
													  'remoteResourceId' => $frk->remote_id]);

		$response = $this->json('post', $uri, $matToCreate->toArray());
		$response->assertStatus(403);
		$response->assertJsonStructure(['error']);
	}

}
