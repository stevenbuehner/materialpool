<?php

namespace Tests\Feature;

use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\User;
use App\ResourceLimitations\PageLimitation;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\ResourceLimitations\ResourceLimitationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiResourceMaterialControllerTest extends TestCase {

	use RefreshDatabase, ResourceTrait;

	/** @var  User $testUser */
	protected $testUser;

	/** @var  ResourceLimitationService $limitationService */
	protected $limitationService;

	protected function setUp(): void {
		parent::setUp();

		Storage::fake(config('app.disks.resources'));

		$this->setUpTestData();

		/** @var User $testUser */
		$this->testUser = User::first();
		$this->assertInstanceOf(User::class, $this->testUser);

		$this->limitationService = resolve(ResourceLimitationService::class);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testAssignResourceToMaterialWithoutLimitationSuccess() {

		$this->authenticatePassport($this->testUser);

		$newForeignResource = $this->getNewCreatedForeignResource($this->testUser);

		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = $this->testUser->foreignMaterialIds->first();
		$resourcesBefore   = $foreignMaterialId->material->resources;
		$this->assertInstanceOf(Collection::class, $resourcesBefore);
		$uri = route('api.v1.materialresource.attach', [
			'foreignResourceId' => $newForeignResource->foreign_id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);

		$response = $this->json('post', $uri);

		$response->assertStatus(200);

		/** @var ForeignMaterialId $foreignMaterialId */
		/** @var  Collection $resourcesAfter */
		$foreignMaterialId = $foreignMaterialId->fresh(['material.resources']);
		$resourcesAfter    = $foreignMaterialId->material->resources;

		$this->assertInstanceOf(Collection::class, $resourcesAfter);
		$this->assertEquals($resourcesBefore->count() + 1, $resourcesAfter->count(),
							'Should be one more resource assigned');
		$this->assertTrue($resourcesAfter->filter(function (Resource $resource) use ($newForeignResource) {
							  return $resource->id == $newForeignResource->resource_id;
						  })->count() === 1);

	}

	protected function getNewCreatedForeignResource(User $user) {
		/** @var Resource $newResource */
		$newResource = Resource::factory()->create([
															'created_by' => $user->id
														]);

		$this->assertInstanceOf(Resource::class, $newResource);
		$this->assertTrue($newResource->exists);

		/** @var ForeignResourceId $foreignResource */
		$foreignResource = ForeignResourceId::factory()->make([
																	   'user_id' => $user->id
																   ]);

		$newResource->foreignIds()->save($foreignResource);

		$this->assertInstanceOf(ForeignResourceId::class, $foreignResource);
		$this->assertTrue($foreignResource->exists);

		return $foreignResource;

	}

	public function testAssignResourceToMaterialWithWrongLimitationFailure() {

		$this->authenticatePassport($this->testUser);

		/** @var Resource $newResource */
		// Type Resource is not applicable with PageLimitation (!) => Error
		$newForeignResource = $this->getNewCreatedForeignResource($this->testUser);

		$limitation = new PageLimitation();
		$limitation->setPages([1]);


		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = $this->testUser->foreignMaterialIds->first();
		$resourcesBefore   = $foreignMaterialId->material->resources;
		$this->assertInstanceOf(Collection::class, $resourcesBefore);
		$uri  = route('api.v1.materialresource.attach', [
			'foreignResourceId' => $newForeignResource->foreign_id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);
		$data = [
			'limitation' => $this->limitationService->getLimitationData($limitation)
		];

		$response = $this->json('post', $uri, $data);

		$response->assertStatus(200);
	}

	public function testAssignResourceToMaterialWithInvalidFormattedLimitation() {

		$this->authenticatePassport($this->testUser);

		/** @var PdfFile $newResource */
		$newResource        = PdfFile::factory()->create(['created_by' => $this->testUser->id]);
		$newForeignResource = $newResource->foreignIds()
										  ->save(ForeignResourceId::factory()->make(['user_id' => $this->testUser->id]));
		$this->assertInstanceOf(PdfFile::class, $newResource);

		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = $this->testUser->foreignMaterialIds->first();
		$resourcesBefore   = $foreignMaterialId->material->resources;
		$this->assertInstanceOf(Collection::class, $resourcesBefore);
		$uri = route('api.v1.materialresource.attach', [
			'foreignResourceId' => $newForeignResource->foreign_id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);

		$response = $this->json('post', $uri, $data = [
			'limitation' => [
				'value' => 'test',
				'type'  => 'page'
			]
		]);
		$response->assertStatus(200);


		$response = $this->json('post', $uri, $data = [
			'limitation' => [
				'value' => '1,2',
				'type'  => 'wrongtest'
			]
		]);
		$response->assertStatus(200);
	}

	public function testAssignResourceToMaterialWithLimitationSuccess() {

		$this->authenticatePassport($this->testUser);

		/** @var PdfFile $newResource */
		$newResource        = PdfFile::factory()->create(['created_by' => $this->testUser->id]);
		$newForeignResource = $newResource->foreignIds()
										  ->save(ForeignResourceId::factory()->make(['user_id' => $this->testUser->id]));
		$this->assertInstanceOf(ForeignResourceId::class, $newForeignResource);
		$this->assertInstanceOf(PdfFile::class, $newResource);

		$limitation = new PageLimitation();
		$limitation->setPages([1]);


		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = $this->testUser->foreignMaterialIds->first();
		$resourcesBefore   = $foreignMaterialId->material->resources;
		$this->assertInstanceOf(Collection::class, $resourcesBefore);
		$uri  = route('api.v1.materialresource.attach', [
			'foreignResourceId' => $newForeignResource->foreign_id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);
		$data = [
			'limitation' => $this->limitationService->getLimitationData($limitation)
		];

		$response = $this->json('post', $uri, $data);

		$response->assertStatus(200);

		/** @var ForeignMaterialId $foreignMaterialId */
		/** @var  Collection $resourcesAfter */
		$foreignMaterialId = $foreignMaterialId->fresh(['material.resources']);
		$resourcesAfter    = $foreignMaterialId->material->resources;

		// Check assignment
		$this->assertInstanceOf(Collection::class, $resourcesAfter);
		$this->assertEquals($resourcesBefore->count() + 1, $resourcesAfter->count(),
							'Should be one more resource assigned');

		$filteredResourceAssignment = $resourcesAfter->filter(function (Resource $resource) use ($newResource) {
			return $resource->id === $newResource->id;
		});
		$this->assertTrue($filteredResourceAssignment->count() === 1);

		// Check Limitation
		$this->assertInstanceOf(ResourceLimitationInterface::class,
								$filteredResourceAssignment->first()->pivot->limitation);
		$this->assertEquals($limitation, $filteredResourceAssignment->first()->pivot->limitation);
		$this->assertEquals($limitation->getPages(),
							$filteredResourceAssignment->first()->pivot->limitation->getPages());

	}

	public function testAssigNResourceToMaterialNotExistant() {

		$this->authenticatePassport($this->testUser);

		$newForeignResource = $this->getNewCreatedForeignResource($this->testUser);
		$foreignMaterialId  = uniqid('rm_test_');
		$uri                = route('api.v1.materialresource.attach', [
			'foreignResourceId' => $newForeignResource->foreign_id,
			'foreignMaterialId' => $foreignMaterialId
		]);

		$response = $this->json('post', $uri);
		$response->assertStatus(404); // Not Found
		$this->assertEquals(0, $newForeignResource->resource->materials->count());

	}

	public function testAssignResourceNotExistantToMaterial() {
		$this->authenticatePassport($this->testUser);

		$newForeignResource = $this->getNewCreatedForeignResource($this->testUser);
		$newResource        = $newForeignResource->resource;

		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = $this->testUser->foreignMaterialIds->first();
		$resourcesBefore   = $foreignMaterialId->material->resources;
		$this->assertInstanceOf(Collection::class, $resourcesBefore);
		$uri = route('api.v1.materialresource.attach', [
			'foreignResourceId' => $newForeignResource->foreign_id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);

		$newForeignResource->resource->delete();
		$newForeignResource->delete();

		$response = $this->json('post', $uri);
		$response->assertStatus(404); // Not Found
		$this->assertEquals(0, $newResource->materials->count());

		$freshForeignMaterialId = $foreignMaterialId->fresh('material.resources');
		$this->assertEquals($resourcesBefore->toArray(), $freshForeignMaterialId->material->resources->toArray());
	}

	public function testAssignResourceToMaterialForbidden() {
		$this->authenticatePassport($this->testUser);

		$newForeignResource = $this->getNewCreatedForeignResource($this->testUser);

		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = ForeignMaterialId::where('user_id', '!=', $this->testUser->id)->first();
		$resourcesBefore   = $foreignMaterialId->material->resources;
		$this->assertInstanceOf(Collection::class, $resourcesBefore);
		$uri = route('api.v1.materialresource.attach', [
			'foreignResourceId' => $newForeignResource->foreign_id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);

		$response = $this->json('post', $uri);
		$response->assertStatus(403); // Forbidden
		$this->assertEquals(0, $newForeignResource->resource->materials->count());

		$freshForeignMaterialId = $foreignMaterialId->fresh('material.resources');
		$this->assertEquals($resourcesBefore->toArray(), $freshForeignMaterialId->material->resources->toArray());
	}

	public function testAssignResourceForbiddenToMaterial() {
		$this->authenticatePassport($this->testUser);

		$otherUser              = User::factory()->create();
		$newForeignResource     = $this->getNewCreatedForeignResource($otherUser);
		$newResource            = $newForeignResource->resource;
		$newResource->is_public = FALSE;
		$newResource->save();

		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = $this->testUser->foreignMaterialIds->first();
		$this->assertInstanceOf(ForeignMaterialId::class, $foreignMaterialId);

		$resourcesBefore = $foreignMaterialId->material->resources;
		$this->assertInstanceOf(Collection::class, $resourcesBefore);

		$uri = route('api.v1.materialresource.attach', [
			'foreignResourceId' => $newForeignResource->foreign_id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);

		$response = $this->json('post', $uri);
		$response->assertStatus(403); // Forbidden
		$this->assertEquals(0, $newResource->materials->count());

		$freshForeignMaterialId = $foreignMaterialId->fresh('material.resources');
		$this->assertEquals($resourcesBefore->toArray(), $freshForeignMaterialId->material->resources->toArray());
	}

	public function testAssignResourceToMaterialUnauthorized() {

		// No Authentication (!)

		$newForeignResource = $this->getNewCreatedForeignResource($this->testUser);

		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = $this->testUser->foreignMaterialIds->first();
		$resourcesBefore   = $foreignMaterialId->material->resources;
		$this->assertInstanceOf(Collection::class, $resourcesBefore);
		$uri = route('api.v1.materialresource.attach', [
			'foreignResourceId' => $newForeignResource->foreign_id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);

		$response = $this->json('post', $uri);

		$response->assertStatus(401); // Unauthorized
	}

	public function testDetachSuccess() {

		$this->authenticatePassport($this->testUser);

		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = $this->testUser->foreignMaterialIds()->has('material.resources')->first();
		$this->assertInstanceOf(ForeignMaterialId::class, $foreignMaterialId);

		/** @var Material $materialBefore */
		$testUser = $this->testUser;
		$foreignMaterialId->load(['material.resources.foreignIds' => function ($query) use ($testUser) {
			$query->where('user_id', '=', $testUser->id);
		}]);
		$materialBefore = $foreignMaterialId->material;

		/** @var Resource $resourcesBefore */
		$resourcesBefore = $materialBefore->resources->reject(function ($resource) {
			return $resource->foreignIds->count() == 0;
		});
		$resBefore       = $resourcesBefore->first();

		$this->assertInstanceOf(Resource::class, $resBefore);
		$uri = route('api.v1.materialresource.detach', [
			'foreignResourceId' => $resBefore->foreignIds->first()->foreign_id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);

		$response = $this->json('delete', $uri);

		$response->assertStatus(200);

		$materialAfter = $materialBefore->fresh('resources');
		$this->assertEquals($materialBefore->resources->count() - 1, $materialAfter->resources->count());

		$this->assertEquals(0,
							$materialAfter->resources->filter(function (Resource $resource) use ($resBefore) {
								return $resource->id == $resBefore->id;
							})->count(), 'Expected no resource with this id to be assigned to the material');

	}

	public function testDetachMaterialForbidden() {

		$this->authenticatePassport($this->testUser);

		/** @var User $user */
		$user = User::factory()->create();
		$this->assertInstanceOf(User::class, $user);

		$foreignResourceId = $this->getNewCreatedForeignResource($user);

		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = ForeignMaterialId::where('user_id', '!=', $this->testUser->id)->first();
		$this->assertInstanceOf(ForeignMaterialId::class, $foreignMaterialId);

		/** @var Material $materialBefore */
		$materialBefore = $foreignMaterialId->material;

		/** @var Resource $resourcesBefore */
		$resourcesBefore = $materialBefore->resources;

		$uri = route('api.v1.materialresource.detach', [
			'foreignResourceId' => $foreignResourceId->foreign_id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);

		$response = $this->json('delete', $uri);

		$response->assertStatus(403); // Forbidden


		$materialAfter = $materialBefore->fresh('resources');
		$this->assertEquals($resourcesBefore->count(), $materialAfter->resources->count());

	}

	public function testDetachResourceForbidden() {
		// This should work anyway. Only access-rights for the material is required!

		$this->authenticatePassport($this->testUser);

		/** @var User $user */
		$user = User::factory()->create();
		$this->assertInstanceOf(User::class, $user);

		$foreinResourceId    = $this->getNewCreatedForeignResource($user);
		$resource            = $foreinResourceId->resource;
		$resource->is_public = FALSE;
		$resource->save();

		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = $this->testUser->foreignMaterialIds()->first();
		$this->assertInstanceOf(ForeignMaterialId::class, $foreignMaterialId);

		/** @var Material $materialBefore */
		$materialBefore = $foreignMaterialId->material;
		$materialBefore->resources()->attach($resource->id);

		/** @var Resource $resourcesBefore */
		$resourcesBefore = $materialBefore->resources;

		$uri = route('api.v1.materialresource.detach', [
			'foreignResourceId' => $foreinResourceId->foreign_id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);

		$response = $this->json('delete', $uri);

		$response->assertStatus(200);


		$materialAfter = $materialBefore->fresh('resources');
		$this->assertEquals($resourcesBefore->count() - 1, $materialAfter->resources->count());
	}

	public function testDetachUnauthorized() {

		// No Authorization
		// $this->authenticatePassport($this->testUser);


		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = $this->testUser->foreignMaterialIds()->has('material.resources')->first();
		$this->assertInstanceOf(ForeignMaterialId::class, $foreignMaterialId);


		/** @var Material $materialBefore */
		$materialBefore = $foreignMaterialId->material;

		/** @var Resource $resourcesBefore */
		$resourcesBefore = $materialBefore->resources;

		/** @var Resource $resource */
		$resource = $resourcesBefore->first();

		$uri = route('api.v1.materialresource.detach', [
			'foreignResourceId' => $resource->id,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);

		$response = $this->json('delete', $uri);

		$response->assertStatus(401); // Unauthorized

		$materialAfter = $materialBefore->fresh('resources');
		$this->assertEquals($resourcesBefore->count(), $materialAfter->resources->count());
	}

	public function testDetachMaterialNotExistant() {

		$this->authenticatePassport($this->testUser);

		/** @var Resource $resource */
		$resource = Resource::first();

		$uri = route('api.v1.materialresource.detach', [
			'foreignResourceId' => $resource->id,
			'foreignMaterialId' => uniqid('not_existant_test')
		]);

		$response = $this->json('delete', $uri);

		$response->assertStatus(404);

	}

	public function testDetachResourceNotExistant() {

		$this->authenticatePassport($this->testUser);

		/** @var ForeignMaterialId $foreignMaterialId */
		$foreignMaterialId = $this->testUser->foreignMaterialIds()->has('material.resources')->first();
		$this->assertInstanceOf(ForeignMaterialId::class, $foreignMaterialId);

		$foreignResourceId = uniqid('rm_test_');
		$this->assertEquals(0, ForeignResourceId::where('foreign_id', '=', $foreignResourceId)->get()->count());

		$uri = route('api.v1.materialresource.detach', [
			'foreignResourceId' => $foreignResourceId,
			'foreignMaterialId' => $foreignMaterialId->foreign_id
		]);

		$response = $this->json('delete', $uri);

		$response->assertStatus(404);

	}


}
