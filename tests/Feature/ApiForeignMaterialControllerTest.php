<?php

namespace Tests\Feature;

use App\Jobs\CheckLonelyBibleverse;
use App\Jobs\CheckLonelyKeyword;
use App\Models\ForeignMaterialId;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiForeignMaterialControllerTest extends TestCase {

	use RefreshDatabase, ResourceTrait;

	protected function setUp(): void {
		parent::setUp();

		$this->setUpTestData();
	}

	protected function tearDown(): void {
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
		$uri = route('api.v1.foreignMaterialShow', ['foreignMaterialId' => $fm->foreign_id]);

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
		unset($shouldBe['id'], $shouldBe['flag'], $shouldBe['icon_of_bundle']);
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
					 'content_hash',
					 'pivot' =>
						 ['limitation'],
				 ]
			 ]
			];
	}

	protected function assertArraySubset(array $expected, array $actual): void {
		foreach ($expected as $key => $expectedValue) {
			$this->assertArrayHasKey($key, $actual);

			if (is_array($expectedValue)) {
				$this->assertIsArray($actual[$key]);
				$this->assertArraySubset($expectedValue, $actual[$key]);
			} else {
				$this->assertEquals($expectedValue, $actual[$key]);
			}
		}
	}

	public function testShowFailUnauthorizied() {
		/** @var ForeignMaterialId $fm */
		$fm = ForeignMaterialId::inRandomOrder()->take(1)->get()->first();
		$this->assertInstanceOf(ForeignMaterialId::class, $fm);

		// Dont't authorize via oAuth

		// Resources-Uri
		$uri = route('api.v1.foreignMaterialShow', ['foreignMaterialId' => $fm->foreign_id]);

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
		$uri = route('api.v1.foreignMaterialShow', ['foreignMaterialId' => $fm->foreign_id]);

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
		$uid = 'test_' . ForeignMaterialId::factory()->make()->foreign_id;

		$data = $this->getTestDataMaterial();

		/** @var Keyword $kw1 */
		/** @var Person $kw2 */
		$kw1 = Keyword::factory()->create();
		$kw2 = Keyword::factory()->make(['type' => 'person']);

		$data['keywords'][] = [
			'title'     => $kw1->title,
			'type'      => $kw1->type,
			'relevance' => 200
		];

		$data['keywords'][] = [
			'title' => $kw2->title,
			'type'  => $kw2->type,
		];

		$data['bibleverses'][] = ['from' => 1001001, 'to' => 1001002];

		// Resources-Uri
		$uri = route('api.v1.foreignMaterialStore', ['foreignMaterialId' => $uid]);

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

	public function testCreateFailExistsAlready() {
		/** @var User $testUser */
		$testUser = User::take(1)->get()->first();
		$this->assertInstanceOf(User::class, $testUser);

		Passport::actingAs(
			$testUser,
			[]
		);

		// ForeignMaterialUID
		$uid  = 'test_' . ForeignMaterialId::factory()->make()->foreign_id;
		$data = $this->getTestDataMaterial();
		$uri  = route('api.v1.foreignMaterialStore', ['foreignMaterialId' => $uid]);

		// Create Material for the first time (everything should be fine)
		$response = $this->json('post', $uri, $data);
		$response->assertStatus(200);

		$fm = ForeignMaterialId::where([
										   'foreign_id' => $uid,
										   'user_id'    => $testUser->id
									   ])->firstOrFail();
		$this->validMaterialShouldLookLike($fm, $response);

		// Create same (!) material for the secibd time (should fail)
		$response = $this->json('post', $uri, $data);

		$response->assertStatus(409);
	}

	public function testUpdate() {

		$fm = $this->setUpUpdateTest();

		$uri  = route('api.v1.foreignMaterialUpdate', ['foreignMaterialId' => $fm->foreign_id]);
		$data = [
			'title'       => 'Test Update',
			'rating'      => 12,
			'from_bot'    => FALSE,
			'description' => 'Description changed',
			'author'      => "Steven Buehner",
			'keywords'    => [
				['title'     => 'Steven Buehner',
				 'type'      => 'person',
				 'relevance' => 200
				],
				['title'     => 'Stuttgart',
				 'type'      => 'place',
				 'relevance' => 100
				]
			],
			'bibleverses' => [
				['from'      => 1001001,
				 'to'        => 1001002,
				 'relevance' => 200]
			]
		];


		$response     = $this->json('put', $uri, $data);
		$responseData = $response->json();

		$response->assertStatus(200);
		$this->validMaterialShouldLookLike($fm, $response);

		$this->assertEquals($data['title'], $responseData['title']);
		$this->assertEquals($data['rating'], $responseData['rating']);
		$this->assertEquals($data['from_bot'], $responseData['from_bot']);
		$this->assertEquals($data['from_bot'], $responseData['from_bot']);
		$this->assertEquals($data['author'], $responseData['author']);

		$this->assertArraySubset(
			[
				[
					'title' => $data['keywords'][0]['title'],
					'type'  => $data['keywords'][0]['type'],
					'pivot' => [
						'relevance' => $data['keywords'][0]['relevance']
					]
				],
				[
					'title' => $data['keywords'][1]['title'],
					'type'  => $data['keywords'][1]['type'],
					'pivot' => [
						'relevance' => $data['keywords'][1]['relevance']
					]
				]
			],
			$responseData['keywords']
		);

		$this->assertArraySubset(
			[

				[
					'from'  => $data['bibleverses'][0]['from'],
					'to'    => $data['bibleverses'][0]['to'],
					'pivot' => [
						'relevance' => $data['bibleverses'][0]['relevance']
					]
				]
			],
			$responseData['bibleverses']
		);


		// Test Removing Keywords and Bibleveres
		$data['keywords']    =
			[
				['title'     => 'Stuttgart',
				 'type'      => 'place',
				 'relevance' => 100
				]
			];
		$data['bibleverses'] = [];
		$data['author']      = "Max Mustermann";

		Bus::fake([
			CheckLonelyBibleverse::class,
			CheckLonelyKeyword::class,
		]);

		$response     = $this->json('put', $uri, $data);
		$responseData = $response->json();

		Bus::assertDispatched(CheckLonelyBibleverse::class);
		Bus::assertDispatched(CheckLonelyKeyword::class);

		$this->assertEquals($data['title'], $responseData['title']);
		$this->assertEquals($data['rating'], $responseData['rating']);
		$this->assertEquals($data['from_bot'], $responseData['from_bot']);
		$this->assertEquals($data['description'], $responseData['description']);
		$this->assertEquals($data['author'], $responseData['author']);
		$this->assertCount(1, $responseData['keywords']);
		$this->assertCount(0, $responseData['bibleverses']);

		$this->assertArraySubset(
			[
				[
					'title' => $data['keywords'][0]['title'],
					'type'  => $data['keywords'][0]['type'],
					'pivot' => [
						'relevance' => $data['keywords'][0]['relevance']
					]
				]
			],
			$responseData['keywords']
		);

	}

	/**
	 * @return ForeignMaterialId
	 */
	public function setUpUpdateTest() {
		/** @var User $testUser */
		$testUser = User::take(1)->get()->first();
		$this->assertInstanceOf(User::class, $testUser);

		Passport::actingAs(
			$testUser,
			[]
		);

		// ForeignMaterialUID
		$uid  = 'test_' . ForeignMaterialId::factory()->make()->foreign_id;
		$data = $this->getTestDataMaterial();
		$uri  = route('api.v1.foreignMaterialStore', ['foreignMaterialId' => $uid]);

		$response = $this->json('post', $uri, $data);
		$response->assertStatus(200);

		$fm = ForeignMaterialId::where([
										   'foreign_id' => $uid,
										   'user_id'    => $testUser->id
									   ])->firstOrFail();

		return $fm;
	}


	public function testUpdateFailUnauthorized() {
		$fm       = ForeignMaterialId::firstOrFail();
		$uri      = route('api.v1.foreignMaterialUpdate', ['foreignMaterialId' => $fm->foreign_id]);
		$response = $this->json('put', $uri, $data = []);

		$response->assertStatus(401); // Unauthorized
	}

	public function testUpdateFailForbidden() {
		/** @var User $testUser */
		$testUser = User::take(1)->get()->first();
		$this->assertInstanceOf(User::class, $testUser);

		Passport::actingAs(
			$testUser,
			[]
		);

		/** @var ForeignMaterialId $fm */
		$fm = ForeignMaterialId::where('user_id', '!=', $testUser->id)->firstOrFail();

		// ForeignMaterialUID
		$data = $this->getTestDataMaterial();
		$uri  = route('api.v1.foreignMaterialUpdate', ['foreignMaterialId' => $fm->foreign_id]);

		$response = $this->json('put', $uri, $data);

		$response->assertStatus(403); // Forbidden
	}


	public function testDelete() {
		/** @var ForeignMaterialId $fm */
		$fm  = ForeignMaterialId::inRandomOrder()->take(1)->get()->first();
		$uid = $fm->foreign_id;
		$this->assertInstanceOf(ForeignMaterialId::class, $fm);

		/** @var User $testUser */
		$testUser = $fm->user;
		$this->assertInstanceOf(User::class, $testUser);

		Passport::actingAs(
			$testUser,
			[]
		);

		/** @var Material $mat */
		$mat = $fm->material;

		// Resources-Uri
		$uri = route('api.v1.foreignMaterialDelete', ['foreignMaterialId' => $fm->foreign_id]);

		$response     = $this->json('delete', $uri);
		$responseData = $response->json();

		$response->assertStatus(200); // Forbidden
		$this->assertTrue($responseData['success'], 'Success-Status');


		// Check if material and associations really are deleted
		$mat->fresh(['keywords', 'bibleverses']);
		$this->assertCount(0, $mat->keywords->toArray());
		$this->assertCount(0, $mat->bibleverses->toArray());

		$fmAfter = ForeignMaterialId::where('foreign_id', '=', $uid)->get();
		$this->assertEquals(0, $fmAfter->count());

		/** @var Collection $forIds */
		// Check if there is
		$forIds = $mat->foreignIds;

		// Material still exists => But hopefully nut with the same $uid (!)
		if ($forIds->count() > 0) {
			$restMat = $forIds->filter(function (ForeignMaterialId $foreignMaterialId) use ($uid) {
				return $foreignMaterialId->foreign_id == $uid;
			});
			$this->assertEquals(0, $restMat->count());
		} else {
			// Material should have been deleted
			$this->assertNull(Material::find($mat->id));
		}
	}

	public function testDeleteFailUnauthorized() {
		$fm       = ForeignMaterialId::firstOrFail();
		$uri      = route('api.v1.foreignMaterialDelete', ['foreignMaterialId' => $fm->foreign_id]);
		$response = $this->json('delete', $uri);

		$response->assertStatus(401); // Unauthorized
	}

	public function testDeleteFailForbidden() {
		/** @var User $testUser */
		$testUser = User::take(1)->get()->first();
		$this->assertInstanceOf(User::class, $testUser);

		Passport::actingAs(
			$testUser,
			[]
		);

		/** @var ForeignMaterialId $fm */
		$fm  = ForeignMaterialId::where('user_id', '!=', $testUser->id)->firstOrFail();
		$uri = route('api.v1.foreignMaterialDelete', ['foreignMaterialId' => $fm->foreign_id]);

		$response = $this->json('delete', $uri);

		$response->assertStatus(403); // Forbidden
	}

}
