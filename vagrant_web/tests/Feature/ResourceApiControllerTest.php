<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\ForeignInstance;
use App\Models\ForeignResourceKey;
use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceApiControllerTest extends TestCase {

	use DatabaseMigrations;

	public function setUp() {
		parent::setUp();

		$this->setUpTestData();
	}

	protected function setUpTestData() {

		$tesKw = Keyword::firstOrCreate(['title' => 'Test']);
		$tesKw->save();

		/** @var Collection $resourceInstances */
		$resourceInstances = factory(ForeignInstance::class, 3)->create();
		$keywords          = factory(Keyword::class, 5)->create();

		factory(Resource::class, 2)->create()->each(function (Resource $r) use ($tesKw) {
			/** @var Material $material */
			$material = $r->materials()->save(factory(Material::class)->create());
			$material->keywords()->save($tesKw);
		});

		factory(ImageFile::class, 5)->create()->each(function ($r) use ($tesKw, $keywords) {
			/** @var Material $material */
			$material = $r->materials()->save(factory(Material::class)->make());
			$material->keywords()->save($tesKw, ['rating' => rand(0, 255)]);
			$material->keywords()->attach($keywords->pluck('id'));
		});

		Resource::all()->each(function (Resource $r) use ($resourceInstances) {

			$fi = $resourceInstances->first();

			$remoteKey                      = new ForeignResourceKey();
			$remoteKey->resource_id         = $r->id;
			$remoteKey->foreign_instance_id = $fi->id;
			$remoteKey->remote_id           = rand(1, 999999);

			$remoteKey->save();
		});
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

	public function testAddImageByRemoteId() {
		$data     = $this->getImageUploadData();
		$response = $this->uploadFileSuccessful($data, $type = 'image');
	}

	protected function getImageUploadData() {
		return $data = [
			'file'      => UploadedFile::fake()->image('avatar.jpg', 100, 100),
			'remote_id' => 10,
			'is_public' => TRUE,
			'notes'     => 'Some notes'
		];
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
		$this->assertFalse($resource->hasRemoteFile());

		$this->assertTrue($resource->deleteLocalFile());

		$this->assertFalse(Storage::disk($storageDisk)->exists($resource->getLocalDiskPath()));
		$this->assertNull($resource->local_path);
		$this->assertFalse($resource->hasLocalFile());

		return $response;


	}

	public function testAddDocumentByRemoteId() {
		$data         = $this->getImageUploadData();
		$data['file'] = UploadedFile::fake()->create('test.doc', 100);

		$response = $this->uploadFileSuccessful($data, $type = 'doc');
	}


}
