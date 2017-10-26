<?php

namespace Tests\Feature;

use App\Models\ForeignMaterialId;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiForeignMaterialControllerTest extends TestCase {

	use DatabaseMigrations, ResourceTrait;

	public function setUp() {
		parent::setUp();

		$this->setUpTestData();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testShow() {

		/** @var ForeignMaterialId $fm */
		$fm = ForeignMaterialId::inRandomOrder()->take(1)->get()->first();
		$this->assertInstanceOf(ForeignMaterialId::class, $fm);

		/** @var User $testUser */
		$testUser = $fm->user;
		$this->assertInstanceOf(User::class, $testUser);

		Passport::actingAs(
			$testUser,
			[]
		);

		$fm->load([
					  'material.resources',
					  'material.keywords',
					  'material.bibleverses'
				  ]);
		$this->assertInstanceOf(Material::class, $fm->material);

		// Resources-Uri
		$uri = route('foreignMaterialShow', ['foreignMaterialId' => $fm->foreign_id]);

		$response = $this->json('get', $uri);
		$response->assertStatus(200);

		$data = $response->json();

		$response->assertJsonStructure(['id',
										'title',
										'rating',
										'from_bot',
										'description',
										'created_at',
										'updated_at',
										'keywords'    => [
											'*' => [
												'title',
												'type',
												'pivot' =>
													['relevance']
											]
										],
										'bibleverses' => [
											'*' => [
												'pivot' =>
													['relevance'],
												'from',
												'to',
												'bible_id'
											]
										],
										'resources'   => [
											'*' => [
												'id',
												'pivot' =>
													['limitation'],
											]
										]
									   ]);
		//$response->assertJson(['total' => $countsMaterials, 'current_page' => 1, 'from' => 1]);

		$data = $response->json();
		unset($data['keywords']);
		unset($data['bibleverses']);
		unset($data['resources']);

		$this->assertEquals($fm->foreign_id, $data['id']);
		unset($data['id']);

		$this->assertArraySubset($data, $fm->material->toArray());
	}

	public function testShowFailUnauthorizied() {
		/** @var ForeignMaterialId $fm */
		$fm = ForeignMaterialId::inRandomOrder()->take(1)->get()->first();
		$this->assertInstanceOf(ForeignMaterialId::class, $fm);

		// Dont't authorize via oAuth

		// Resources-Uri
		$uri = route('foreignMaterialShow', ['foreignMaterialId' => $fm->foreign_id]);

		$response = $this->json('get', $uri);
		$response->assertStatus(401); // Unauthorized
	}

	public function testShowFailForbidden() {

		/** @var User $testUser */
		$testUser = User::inRandomOrder()->take(1)->get()->first();
		$this->assertInstanceOf(User::class, $testUser);

		// Try any ForeignMaterialId from a different user as $testUser
		/** @var ForeignMaterialId $fm */
		$fm = ForeignMaterialId::where(
			'user_id', '!=', $testUser->id
		)->take(1)->get()->first();
		$this->assertInstanceOf(ForeignMaterialId::class, $fm);

		Passport::actingAs(
			$testUser,
			[]
		);

		// Resources-Uri
		$uri = route('foreignMaterialShow', ['foreignMaterialId' => $fm->foreign_id]);

		$response = $this->json('get', $uri);
		$response->assertStatus(403); // Forbidden
	}


}
