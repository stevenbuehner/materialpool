<?php

namespace Tests\Feature;

use App\Models\ForeignMaterialId;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
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

		$this->validMaterialShouldLookLike($fm, $response);
	}

	protected function validMaterialShouldLookLike(ForeignMaterialId $shouldBeFM, TestResponse $response) {

		$response->assertStatus(200);

		$response->assertJsonStructure($this->getValidMaterialStructure());

		$data = $response->json();

		// Foreign-ID
		$this->assertEquals($shouldBeFM->foreign_id, $data['id']);

		// Material-Attributes
		$shouldBe = $shouldBeFM->material->attributesToArray();
		unset($shouldBe['id']);
		$this->assertArraySubset($shouldBe, $data);

		// Author
		if ($shouldBeFM->material->author_id === NULL) {
			$this->assertNull($data['author']);
		} else {
			$this->assertEquals($shouldBeFM->material->author->title, $data['author']);
		}

		// Keywords
		$this->assertEquals($shouldBeFM->material->keywords->toArray(), $data['keywords']);
		$this->assertCount(count($data['keywords']), $shouldBeFM->material->keywords);

		// Bibleverses
		$this->assertEquals($shouldBeFM->material->bibleverses->only(['from', 'to'])->toArray(),
							collect($data['bibleverses'])->only(['from', 'to'])->toArray());
		$this->assertCount(count($data['bibleverses']), $shouldBeFM->material->bibleverses);
	}

	protected function getValidMaterialStructure() {
		return
			['id',
			 'title',
			 'rating',
			 'from_bot',
			 'description',
			 'author',
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
			];
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

	public function testCreateComplete() {

		/** @var User $testUser */
		$testUser = User::take(1)->get()->first();
		$this->assertInstanceOf(User::class, $testUser);

		Passport::actingAs(
			$testUser,
			[]
		);

		// ForeignMaterialUID
		$uid          = 'test_' . factory(ForeignMaterialId::class)->make()->foreign_id;
		$testMaterial = factory(Material::class);


		$data = $this->getTestDataMaterial();

		/** @var Keyword $kw1 */
		/** @var Person $kw2 */
		$kw1 = factory(Keyword::class)->create();
		$kw2 = factory(Person::class)->make();

		$data['keywords'][] = [
			'title'     => $kw1->title,
			'type'      => $kw1::getSingleTableType(),
			'relevance' => 200
		];

		$data['keywords'][] = [
			'title' => $kw2->title,
			'type'  => $kw2::getSingleTableType(),
		];

		$data['bibleverses'][] = ['from' => 1001001, 'to' => 1001002];

		// Resources-Uri
		$uri = route('foreignMaterialStore', ['foreignMaterialId' => $uid]);


		$response = $this->json('post', $uri, $data);

		$responseData = $response->json();
		$this->assertArrayNotHasKey('errors', $responseData);

		$response->assertStatus(200);

		$fm = ForeignMaterialId::where([
										   'foreign_id' => $uid,
										   'user_id'    => $testUser->id
									   ])->firstOrFail();
		$this->validMaterialShouldLookLike($fm, $response);
	}

	protected function getTestDataMaterial() {
		return [
			'title'       => 'Test Title of something',
			'rating'      => 10,
			'from_bot'    => TRUE,
			'description' => 'Some random long description ... bla, blub etc.',
			'keywords'    => [],
			'bibleverses' => []
		];
	}


}
