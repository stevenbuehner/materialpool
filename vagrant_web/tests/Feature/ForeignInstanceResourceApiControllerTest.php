<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\ForeignInstance;
use App\Models\ForeignResourceKey;
use App\Models\Resource;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ForeignInstanceResourceApiControllerTest extends TestCase {

	use DatabaseMigrations, ResourceTrait;

	public function setUp() {
		parent::setUp();

		$this->setUpTestData();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testGetResourceIndex() {

		/** @var ForeignInstance $fi */
		$fi = ForeignInstance::first();

		$counts = $fi->foreignResourceKeys->count();
		$this->assertGreaterThan(0, $counts);

		// Resources-Uri
		$uri      = '/api/v1/' . $fi->id . '/resources';
		$response = $this->json('get', $uri);

		$response->assertStatus(200);
		$response->assertJsonStructure(['total', 'per_page', 'current_page', 'last_page', 'next_page_url', 'prev_page_url', 'from', 'to',
										'data' => [
											'*' => [
												'remote_id', 'resource_id', 'created_at', 'updated_at'
											]
										]
									   ]);
		$response->assertJson(['total' => $counts, 'current_page' => 1, 'from' => 1]);
	}

	public function testGetResourceWrongForeignInstance() {

		// Resources-Uri
		$uri      = '/api/v1/99999/resources';
		$response = $this->json('get', $uri);

		$response->assertStatus(404);
	}

	public function testAddImageByRemoteId() {
		$data     = $this->getImageUploadData();
		$response = $this->uploadFileSuccessful($data, $type = 'image');
	}


	protected function uploadFileSuccessful($data, $type) {
		$storageDisk = 'resources';
		Storage::fake($storageDisk);

		/** @var ForeignInstance $fi */
		$fi = ForeignInstance::first();
		$this->assertTrue($fi->exists);

		// Resources-Uri
		$uri      = '/api/v1/' . $fi->id . '/resources/' . $type;
		$response = $this->json('post', $uri, $data);
		/** @var \Illuminate\Http\Testing\File $file */
		$file = $data['file'];

		$response->assertStatus(200);
		$response->assertJsonStructure([
										   'is_public',
										   'notes',
										   'type',
										   'updated_at',
										   'created_at',
										   'id',
										   'original_filename'
									   ]
		);
		$response->assertJson(['is_public'         => $data['is_public'],
							   'notes'             => $data['notes'],
							   'type'              => $type,
							   'original_filename' => $file->getClientOriginalName()]);

		// check if file exists
		$this->assertArrayHasKey('id', $response->decodeResponseJson());

		/** @var File $resource */
		$resource = File::find($response->decodeResponseJson()['id']);
		$this->assertTrue($resource->exists, 'Expecting Resource to exist');

		// FIXME: Not working for documents :/
		// $this->assertGreaterThan(0, $resource->getLocalSize());
		// $this->assertEquals($file->getSize(), $resource->getLocalSize());

		$this->assertTrue(Storage::disk($storageDisk)->exists($resource->getLocalDiskPath()));
		$this->assertTrue($resource->hasLocalFile());
		$this->assertNotNull($resource->getLocalUrl());
		$this->assertNotNull($resource->getLocalMimeType());
		$this->assertTrue($resource->hasLocalFile());

		$this->assertTrue($resource->deleteLocalFile());

		$this->assertFalse(Storage::disk($storageDisk)->exists($resource->getLocalDiskPath()));
		$this->assertNull($resource->local_path);
		$this->assertFalse($resource->hasLocalFile());

		return $response;
	}

	public function testAddDocumentByRemoteId() {
		$data         = $this->getImageUploadData();
		$data['file'] = UploadedFile::fake()->create('test.doc', 100);

		// Test without remote_path
		$response = $this->uploadFileSuccessful($data, $type = 'doc');

		// Test with remote_path
		$data['remote_path'] = 'http://whatever.de/test';
		$data['remote_id']   = 1234;
		$response            = $this->uploadFileSuccessful($data, $type = 'doc');
		$response->assertJson(['remote_path' => $data['remote_path']]);
	}

	public function testDestroy() {
		/** @var ForeignInstance $fi */
		$fi = ForeignInstance::first();
		$this->assertTrue($fi->exists);

		/** @var ForeignResourceKey $key */
		$key        = $fi->foreignResourceKeys->first();
		$resourceId = $key->resource_id;
		$this->assertTrue($key->exists);


		$uri = route('foreignInstanceResourceDelete',
					 ['foreignInstanceId' => $fi->id, 'remoteResourceId' => $key->remote_id]);

		$response = $this->json('delete', $uri);
		$response->assertStatus(200);

		$this->assertNull(Resource::find($resourceId));
		$this->assertNull(ForeignResourceKey::findOneWhere($fi->id, $key->remote_id));
	}


}
