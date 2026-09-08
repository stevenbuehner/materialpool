<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BibleverseApiControllerTest extends TestCase {

	use RefreshDatabase;

	public function testBibleverseCreateWithFromAndTo() {

		$method = 'post';
		$uri    = route('api.v1.bibleverses.store');
		$data   = [
			'from' => 1001001,
			'to'   => '1001001'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(201);
		$this->assertKeywordStructure($response);

		$response->assertJson([
								  'label' => "1Mo 1,1"
							  ]);
	}

	protected function assertKeywordStructure(TestResponse $response) {
		$response->assertJsonStructure([
										   'id', 'label',
										   'from_book_id',
										   'from_chapter',
										   'from_verse',
										   'to_book_id',
										   'to_chapter',
										   'to_verse',
										   'label'
									   ]);
	}

	public function testBibleverseCreateWithLabel() {
		$method = 'post';
		$uri    = route('api.v1.bibleverses.store');
		$data   = [
			'label' => 'Gen 1,1'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(201);
		$this->assertKeywordStructure($response);

		$response->assertJson([
								  'label' => "1Mo 1,1"
							  ]);
	}

}
