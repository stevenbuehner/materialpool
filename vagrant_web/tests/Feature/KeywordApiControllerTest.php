<?php

namespace Tests\Feature;

use App\Models\Keyword;
use App\Models\Person;
use Defuse\Crypto\Key;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Tests\TestCase;

class KeywordApiControllerTest extends TestCase {

	use DatabaseMigrations;

	public function testKeywordCreatePersonWithType() {

		$method = 'post';
		$uri    = route('api.v1.keywords.create');
		$data   = [
			'type'  => Person::getSingleTableType(),
			'title' => 'My Name'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(200);
		$this->assertKeywordStructure($response);
		$this->assertKeywordData($response, "My Name", Person::getSingleTableType(), Person::getSingleTableType()."_my_name", NULL);
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
			'type'  => Keyword::getSingleTableType(),
			'title' => 'My Test'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(200);
		$this->assertKeywordStructure($response);
		$this->assertKeywordData($response, "My Test", Keyword::getSingleTableType(), Keyword::getSingleTableType(). "_my_test", NULL);
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
		$this->assertKeywordData($response, "Some Keyword", Keyword::getSingleTableType(), Keyword::getSingleTableType()."_some_keyword", NULL);
	}

	public function testKeywordCreatePersonWithoutType() {

		$method = 'post';
		$uri    = route('api.v1.keywords.create');
		$data   = [
			'title' => 'Person: Some Person'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(200);
		$this->assertKeywordStructure($response);
		$this->assertKeywordData($response, "Some Person", Person::getSingleTableType(), Person::getSingleTableType()."_some_person", NULL);
	}

}
