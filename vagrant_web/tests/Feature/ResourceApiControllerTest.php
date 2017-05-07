<?php

namespace Tests\Feature;

use App\Models\File;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ResourceApiControllerTest extends TestCase {

	use DatabaseMigrations, ResourceTrait;

	public function setUp() {
		parent::setUp();

		$this->setUpTestData();
	}

	public function tearDown() {
		parent::tearDown();
	}


	public function testGetResourceShow() {
		$resource = File::first();
		$this->assertNotNull($resource);
		$uri      = '/api/v1/resources/' . $resource->id;
		$response = $this->json('get', $uri);

		$response->assertStatus(200);
		$response->assertJsonStructure([
										   'id', 'remote_path', 'notes', 'is_public', 'content_hash', 'type', 'created_at', 'updated_at', 'original_filename'
									   ]);
		$response->assertJson([
								  'id'                => $resource->id,
								  'remote_path'       => $resource->remote_path,
								  'notes'             => $resource->notes,
								  'is_public'         => $resource->is_public,
								  'content_hash'      => $resource->content_hash,
								  'type'              => $resource->type,
								  'created_at'        => $resource->created_at,
								  'updated_at'        => $resource->updated_at,
								  'original_filename' => $resource->original_filename
							  ]);

	}

	public function testGetResourceShowNotFound() {
		$uri      = '/api/v1/resources/9999999';
		$response = $this->json('get', $uri);

		$response->assertStatus(404);
	}


}
