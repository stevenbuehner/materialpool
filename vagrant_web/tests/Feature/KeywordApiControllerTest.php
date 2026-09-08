<?php

namespace Tests\Feature;

use App\Models\Keyword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Tests\TestCase;

class KeywordApiControllerTest extends TestCase {

	use RefreshDatabase;

	protected function setUp(): void {
		parent::setUp();

		Passport::actingAs(User::factory()->create());
	}

	public function testKeywordCreatePersonWithType() {

		$method = 'post';
		$uri    = route('api.v1.keywords.create');
		$data   = [
			'type'  => 'person',
			'title' => 'My Name'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(200);
		$this->assertKeywordStructure($response);
		$this->assertKeywordData($response, "My Name", 'person', 'person_my_name', NULL);
	}

	protected function assertKeywordStructure(TestResponse $response) {
		$response->assertJsonStructure([
										   'id', 'title', 'type', 'lc_title', 'parent_id',  'custom_icon'
									   ]);
	}

	protected function assertKeywordData(TestResponse $response, $title, $type, $lc_title, $parent_id = NULL) {
		$response->assertJson([
								  'title'     => $title,
								  'type'      => $type,
								  'lc_title'  => $lc_title,
								  'parent_id' => $parent_id
							  ]);
	}

	public function testKeywordCreateKeywordWithType() {

		$method = 'post';
		$uri    = route('api.v1.keywords.create');
		$data   = [
			'type'  => 'key',
			'title' => 'My Test'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(200);
		$this->assertKeywordStructure($response);
		$this->assertKeywordData($response, "My Test", 'key', 'key_my_test', NULL);
	}

	public function testKeywordCreateKeywordWithoutType() {

		$method = 'post';
		$uri    = route('api.v1.keywords.create');
		$data   = [
			'title' => 'Some Keyword'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(200);
		$this->assertKeywordStructure($response);
		$this->assertKeywordData($response, "Some Keyword", 'key', 'key_some_keyword', NULL);
	}

	public function testKeywordCreatePrefixedTitleWithoutType() {

		$method = 'post';
		$uri    = route('api.v1.keywords.create');
		$data   = [
			'title' => 'Person: Some Person'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(200);
		$this->assertKeywordStructure($response);
		$this->assertKeywordData($response, "Person: Some Person", 'key', 'key_person_some_person', NULL);
	}

}
