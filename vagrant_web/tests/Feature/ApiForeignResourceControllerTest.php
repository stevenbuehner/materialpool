<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\ForeignResourceId;
use App\Models\ImageFile;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\Text;
use App\Models\Url;
use App\Models\User;
use Database\Seeders\ResourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Tests\TestCase;

class ApiForeignResourceControllerTest extends TestCase {

	use RefreshDatabase, ResourceTrait;

	/** @var  Filesystem $testStorage */
	protected $testStorage;

	/** @var  User $testUser */
	protected $testUser;

	protected function setUp(): void {
		parent::setUp();

		Storage::fake(config('app.disks.resources'));

		// Setup TestStorage
		$this->testStorage = Storage::disk(config('app.disks.testfiles'));

		$this->setUpTestData();

		/** @var User $testUser */
		$this->testUser = User::first();
		$this->assertInstanceOf(User::class, $this->testUser);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testCreateResourceImageFileUpload() {

		$this->authenticatePassport();

		// Bild Upload
		$dataBild = [
			'id'                            => uniqid('frid_test_'),
			'file'                          => $this->getUploadedFile(__DIR__ . '/../testFiles/Bild.jpg',
																	  'Grafik, Bild, Darstellung, Evangelium, Verlorene Menschheit, Jesus, rettender Gott, Blatt 1.jpg'),
			'notes'                         => 'keine Notiz',
			'is_public'                     => FALSE,
			'create_material_from_resource' => TRUE,
			'foreign_material_id'           => uniqid('test_', TRUE)
		];

		$response          = $this->uploadFilesSuccessful($dataBild);
		$foreignResourceId = $response->getOriginalContent();
		$this->assertInstanceOf(ImageFile::class, $foreignResourceId->resource);
	}

	protected function getUploadedFile($path, $name) {
		return new UploadedFile($path, $name, mime_content_type($path), filesize($path), FALSE, TRUE);
	}

	protected function uploadFilesSuccessful($data, $isFileResource = TRUE) {

		// Resources-Uri
		$uri      = route('api.v1.foreignResources.store');
		$response = $this->json('post', $uri, $data);

		/** @var \Illuminate\Http\Testing\File $file */
		$file = $isFileResource ? $data['file'] : NULL;

		$responseData = $response->json();
		$response->assertStatus($isFileResource ? 201 : 200);

		$this->verifyResourceJsonResult($response, $isFileResource);

		// Inhalt validieren
		$response->assertJsonFragment(['is_public' => $data['is_public'],
									   'notes'     => $data['notes']]);

		if (TRUE == $isFileResource) {
			$response->assertJsonFragment(
				['original_filename' => $file->getClientOriginalName()]
			);
			$this->assertFalse(isset($responseData['content']), 'content not allowed here');
		} else if (isset($data['content'])) {
			$responseContent = $responseData['content'];
			$this->assertGreaterThanOrEqual(0, strpos($data['content'], $responseContent),
											'Content is included propperly');
			$this->assertFalse(isset($responseData['original_filename']), 'original_filename not allowed here');
		}

		/** @var ForeignResourceId $foreignResourceId */
		$foreignResourceId = ForeignResourceId::where('foreign_id', '=', $responseData['id'])->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);
		$this->assertTrue($foreignResourceId->exists, 'Expecting ForeignResourceId to exist');

		/** @var Resource $resource */
		$resource = $foreignResourceId->resource;
		$this->assertInstanceOf(Resource::class, $resource);
		$this->assertTrue($resource->exists, 'Expecting Resource to exist');

		if (TRUE === $isFileResource) {
			$this->assertInstanceOf(File::class, $resource);

			$this->assertTrue(Storage::disk(config('app.disks.resources'))->exists($resource->getLocalFilePath()));
			$this->assertTrue($resource->hasLocalFile());
			$this->assertNotNull($resource->getLocalUrl());
			$this->assertNotNull($resource->getLocalMimeType());
			$this->assertTrue($resource->hasLocalFile());

			$this->assertTrue($resource->deleteLocalFile());

			$this->assertFalse(Storage::disk(config('app.disks.resources'))->exists($resource->getLocalFilePath()));
			$this->assertNull($resource->local_path);
			$this->assertFalse($resource->hasLocalFile());
		}

		$this->assertNotEmpty($responseData['content_hash'], "Expecting a content hash.");


		// FIXME: Not working for documents :/
		// $this->assertGreaterThan(0, $resource->getLocalSize());
		// $this->assertEquals($file->getSize(), $resource->getLocalSize());


		return $response;
	}

	protected function verifyResourceJsonResult(TestResponse $response, $isFileResource = TRUE) {


		// Struktur validieren
		$expectedStucture = [
			'id',
			'type',
			'remote_path',
			'notes',
			'is_public',
			'content_hash'
		];

		if (TRUE === $isFileResource) {
			$expectedStucture[] = 'original_filename';
		} else {
			$expectedStucture[] = 'content';
		}

		$response->assertJsonStructure($expectedStucture);

		$response->assertJsonMissing(['message']);
	}

	public function testCreateResourcePdfFileUpload() {

		$this->authenticatePassport();

		// PDF Upload
		$dataPdf = [
			'id'                            => uniqid('frid_test_'),
			'file'                          => $this->getUploadedFile(__DIR__ . '/../testFiles/PDF.pdf',
																	  'Balloning.pdf'),
			'notes'                         => 'Viele Notizen',
			'is_public'                     => TRUE,
			'create_material_from_resource' => TRUE,
			'foreign_material_id'           => uniqid('test_', TRUE)
		];

		$response          = $this->uploadFilesSuccessful($dataPdf);
		$foreignResourceId = $response->getOriginalContent();
		$this->assertInstanceOf(PdfFile::class, $foreignResourceId->resource);

	}

	public function testCreateResourceTextFileUpload() {

		$this->authenticatePassport();

		// Text Upload as file
		$dataText = [
			'id'                            => uniqid('frid_test_'),
			'file'                          => $this->getUploadedFile(__DIR__ . '/../testFiles/Text.txt',
																	  'Einfältig, Beispiel, Papier, von Klaus-Dieter Mauer, am 18.10.2015, Richtung.txt'),
			'notes'                         => 'Das ist egal',
			'is_public'                     => FALSE,
			'create_material_from_resource' => TRUE,
			'foreign_material_id'           => uniqid('test_', TRUE)
		];

		$response          = $this->uploadFilesSuccessful($dataText, $isFileResource = FALSE);
		$foreignResourceId = $response->getOriginalContent();
		$this->assertInstanceOf(Text::class, $foreignResourceId->resource);
	}

	public function testCreateResourceByTextContent() {

		$this->authenticatePassport();

		// Text Upload
		$dataText = [
			'id'                            => uniqid('frid_test_'),
			'content'                       => 'Das ist mein toller Inhalt, den es zu würdigen sich lohnt!',
			'notes'                         => 'keine Notiz',
			'is_public'                     => TRUE,
			'create_material_from_resource' => TRUE,
			'foreign_material_id'           => uniqid('test_', TRUE)
		];

		$response          = $this->uploadFilesSuccessful($dataText, FALSE);
		$foreignResourceId = $response->getOriginalContent();
		$this->assertInstanceOf(Text::class, $foreignResourceId->resource);
	}


	public function testCreateResourceByOtherTextContent() {

		$this->authenticatePassport();

		// Check Without MaterialCreation
		// Text Upload
		$dataText = [
			'id'        => uniqid('frid_test_'),
			'content'   => 'Das ist mein anderer Inhalt, den es zu würdigen sich lohnt!',
			'notes'     => 'keine Notiz',
			'is_public' => TRUE,
		];

		$response          = $this->uploadFilesSuccessful($dataText, FALSE);
		$foreignResourceId = $response->getOriginalContent();
		$resource          = $foreignResourceId->resource;
		$this->assertInstanceOf(Text::class, $resource);
		$this->assertEquals(0, $resource->materials->count(), "Erwarte kein verknüpftes Material");

	}


	public function testCreateResourceByUrlContent() {

		$this->authenticatePassport();

		// Url-Content
		$dataUrl = [
			'id'        => uniqid('frid_test_'),
			'content'   => "http://www.google.de",
			'notes'     => 'Super Suchmaschine - oder auch nicht',
			'is_public' => TRUE,
		];

		$response          = $this->uploadFilesSuccessful($dataUrl, FALSE);
		$foreignResourceId = $response->getOriginalContent();
		$resource          = $foreignResourceId->resource;
		$this->assertInstanceOf(Url::class, $resource);
		$this->assertEquals(0, $resource->materials->count(), "Erwarte kein verknüpftes Material");
	}

	public function testCreateFailUnauthorizied() {

		// Dont't authorize via oAuth

		// Resources-Uri
		$uri      = route('api.v1.foreignResources.store');
		$dataText = [
			'id'        => uniqid('frid_test_'),
			'content'   => 'Das ist mein anderer Inhalt, den es zu würdigen sich lohnt!',
			'notes'     => 'keine Notiz',
			'is_public' => TRUE,
		];

		$response = $this->json('post', $uri, $dataText);
		$response->assertStatus(401); // Unauthorized
	}


	public function testGetResourceShow() {

		$this->authenticatePassport();

		/** @var User $testuser */
		$testuser          = $this->testUser;
		$foreignResourceId = $testuser->foreignResourceIds->first();
		$resource          = $foreignResourceId->resource;
		$this->assertInstanceOf(Resource::class, $resource);

		$uri          = route('api.v1.foreignResources.show', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$response     = $this->json('get', $uri);
		$responseData = $response->json();


		$response->assertStatus(200);
		$this->verifyResourceJsonResult($response, $isFileResource = $resource instanceof File);
	}

	public function testGetResourceFile() {

		$resource = File::has('foreignIds')->first();
		$this->assertInstanceOf(File::class, $resource);
		$foreignResourceId = $resource->foreignIds()->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);

		/** @var User $user */
		$user = $foreignResourceId->user;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);

		$uri      = route('api.v1.foreignResources.show', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$response = $this->json('get', $uri);

		$response->assertStatus(200);
		$this->verifyResourceJsonResult($response, $isFileResource = $resource instanceof File);
	}

	public function testGetResourceText() {

		$resource = Text::has('foreignIds')->first();
		$this->assertInstanceOf(Text::class, $resource);
		$foreignResourceId = $resource->foreignIds()->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);

		/** @var User $user */
		$user = $foreignResourceId->user;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);

		$uri          = route('api.v1.foreignResources.show', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$response     = $this->json('get', $uri);
		$responseData = $response->json();


		$response->assertStatus(200);
		$this->verifyResourceJsonResult($response, $isFileResource = $resource instanceof File);
	}

	public function testGetResourceShowNotFound() {

		$this->authenticatePassport();
		$uri      = route('api.v1.foreignResources.show', ['foreignResourceId' => 999999]);
		$response = $this->json('get', $uri);

		$response->assertStatus(404);
	}


	public function testGetResourceForbidden() {

		$this->authenticatePassport();

		$foreignResourceId = ForeignResourceId::where(
			'user_id', '!=', $this->testUser->id
		)->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);


		// Make shure it is not public -> not accessible
		$resource = $foreignResourceId->resource;
		$this->assertInstanceOf(Resource::class, $resource);
		$resource->is_public = FALSE;
		$resource->save();

		$uri      = route('api.v1.foreignResources.show', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$response = $this->json('get', $uri);

		$response->assertStatus(403); // Forbidden
	}

	public function testGetResourceUnauthorized() {

		$uri      = route('api.v1.foreignResources.show', ['foreignResourceId' => 999999]);
		$response = $this->json('get', $uri);

		$response->assertStatus(401); // Unauthorized
	}

	public function testUpdateResourceAttributes() {

		$this->authenticatePassport();

		/** @var User $user */
		$user = $this->testUser;
		$this->assertInstanceOf(User::class, $user);

		$foreignResourceId = $user->foreignResourceIds()->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);

		$resource = $foreignResourceId->resource;
		$this->assertInstanceOf(Resource::class, $resource);

		$uri = route('api.v1.foreignResources.update', ['foreignResourceId' => $foreignResourceId->foreign_id]);


		// Not allowed to change created_by
		$response = $this->put($uri, ['created_by' => 999]);
		$response->assertStatus(200);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent()->resource);
		$this->assertNotSame($resource, $response->getOriginalContent()->resource);
		$this->assertEquals($resource->created_by, $response->getOriginalContent()->resource->created_by);

		// Not allowed to change id
		$response = $this->put($uri, ['id' => 999]);
		$response->assertStatus(200);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent()->resource);
		$this->assertNotSame($resource, $response->getOriginalContent()->resource);
		$this->assertEquals($resource->id, $response->getOriginalContent()->resource->id);

		// Not allowed to change options
		$response = $this->put($uri, ['options' => 'Test']);
		$response->assertStatus(200);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent()->resource);
		$this->assertNotSame($resource, $response->getOriginalContent()->resource);
		$this->assertEquals($resource->toArray(), $response->getOriginalContent()->resource->toArray());

		// Not allowed to change type
		$response = $this->put($uri, ['type' => 'test']);
		$response->assertStatus(200);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent()->resource);
		$this->assertNotSame($resource, $response->getOriginalContent()->resource);
		$this->assertEquals($resource->type, $response->getOriginalContent()->resource->type);

		// Not allowed to change hash
		$response = $this->put($uri, ['content_hash' => 'test']);
		$response->assertStatus(200);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent()->resource);
		$this->assertNotSame($resource, $response->getOriginalContent()->resource);
		$this->assertEquals($resource->content_hash, $response->getOriginalContent()->resource->content_hash);

		// Not allowed to change created_at
		$response = $this->put($uri, ['created_at' => '2017-09-09']);
		$response->assertStatus(200);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent()->resource);
		$this->assertNotSame($resource, $response->getOriginalContent()->resource);
		$this->assertEquals($resource->created_at, $response->getOriginalContent()->resource->created_at);

		// Not allowed to change updated_at
		$response = $this->put($uri, ['updated_at' => '2017-09-09']);
		$response->assertStatus(200);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent()->resource);
		$this->assertNotSame($resource, $response->getOriginalContent()->resource);
		$this->assertEquals($resource->updated_at, $response->getOriginalContent()->resource->updated_at);


		// Should work!
		$response = $this->put($uri, ['notes' => $notes = 'This is a different title']);
		$response->assertStatus(200);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent()->resource);
		$this->assertNotSame($resource, $response->getOriginalContent()->resource);
		$this->assertEquals($notes, $response->getOriginalContent()->resource->notes);

		// Should work!
		$response = $this->put($uri, ['remote_path' => $remotePath = 'http://google.de']);
		$response->assertStatus(200);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent()->resource);
		$this->assertNotSame($resource, $response->getOriginalContent()->resource);
		$this->assertEquals($remotePath, $response->getOriginalContent()->resource->remote_path);

		// Should work!
		$resource->is_public = FALSE;
		$resource->save();
		$response = $this->put($uri, ['is_public' => $isPublic = TRUE]);
		$response->assertStatus(200);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent()->resource);
		$this->assertNotSame($resource, $response->getOriginalContent()->resource);
		$this->assertEquals($isPublic, $response->getOriginalContent()->resource->is_public);
	}

	public function testUpdateFileFailNotFiletype() {

		// Not Image-Resource
		$resource = Text::has('foreignIds')->first();
		$this->assertInstanceOf(Resource::class, $resource);

		$foreignResourceId = $resource->foreignIds()->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);


		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);


		// Should not work, because not a filetype
		$uri      = route('api.v1.foreignResources.update', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$data     = [
			'file' => $this->getUploadedFile(__DIR__ . '/../testFiles/Bild.jpg',
											 $originalFilename = 'Langer Bildname.jpg'),
		];
		$response = $this->put($uri, $data);

		$response->assertStatus(500);
		$response->assertJsonStructure(['message']);
	}

	public function testUpdateFileFailNotContenttype() {

		// Not Image-Resource
		$resource = File::has('foreignIds')->first();
		$this->assertInstanceOf(File::class, $resource);

		$foreignResourceId = $resource->foreignIds()->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);


		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);


		// Should not work, because not a filetype
		$uri      = route('api.v1.foreignResources.update', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$data     = [
			'content' => 'Das waere schlecht, wenn das ginge'
		];
		$response = $this->put($uri, $data);

		$response->assertStatus(500);
		$response->assertJsonStructure(['message']);
	}


	public function testUpdateFileSuccess() {

		$resource = File::has('foreignIds')->first();
		$this->assertInstanceOf(File::class, $resource);

		$foreignResourceId = $resource->foreignIds()->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);

		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);


		// Should not work, because not a filetype
		$uri      = route('api.v1.foreignResources.update', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$data     = [
			'file' => $this->getUploadedFile(__DIR__ . '/../testFiles/Bild.jpg',
											 $originalFilename = 'Langer Bildname.jpg'),
		];
		$response = $this->put($uri, $data);

		$response->assertStatus(200);
		$this->verifyResourceJsonResult($response, $isFile = TRUE);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(ImageFile::class, $response->getOriginalContent()->resource);
		$resource = $resource->fresh();
		$this->assertEquals($response->getOriginalContent()->resource->toArray(), $resource->toArray());

	}

	public function testUpdateContentSuccess() {

		$resource = Text::has('foreignIds')->first();
		$this->assertInstanceOf(Resource::class, $resource);

		// Resource
		$foreignResourceId = $resource->foreignIds()->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);

		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);


		// Should not work, because not a filetype
		$uri      = route('api.v1.foreignResources.update', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$data     = [
			'content' => "Ein schöner Rücken kann auch entzücken"
		];
		$response = $this->put($uri, $data);

		$response->assertStatus(200);
		$this->verifyResourceJsonResult($response, $isFile = FALSE);
		$this->assertInstanceOf(ForeignResourceId::class, $response->getOriginalContent());
		$this->assertInstanceOf(Text::class, $response->getOriginalContent()->resource);
		$resource = $resource->fresh();
		$this->assertEquals($response->getOriginalContent()->resource->toArray(), $resource->toArray());
	}

	public function testUpdateOfSharedResource() {
		$this->markTestSkipped('Der Ablauf ist noch nicht implementiert.');
	}

	public function testUpdateForbidden() {

		// Resource
		$foreignResourceId = ForeignResourceId::first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);

		// Different user than Resource-Owner
		$user = User::where('id', '!=', $foreignResourceId->user_id)->first();
		$this->assertInstanceOf(User::class, $user);

		// authorize via oAuth
		$this->authenticatePassport($user);

		// Resources-Uri
		$uri      = route('api.v1.foreignResources.update', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$dataText = [
			'content'   => 'Das ist mein anderer Inhalt, den es zu würdigen sich lohnt!',
			'notes'     => 'keine Notiz',
			'is_public' => TRUE,
		];

		$response = $this->json('put', $uri, $dataText);
		$response->assertStatus(403); // Forbidden
	}

	public function testUpdateUnauthorized() {

		$foreignResourceId = ForeignResourceId::first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);

		// Dont't authorize via oAuth

		// Resources-Uri
		$uri      = route('api.v1.foreignResources.update', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$dataText = [
			'content'   => 'Das ist mein anderer Inhalt, den es zu würdigen sich lohnt!',
			'notes'     => 'keine Notiz',
			'is_public' => TRUE,
		];

		$response = $this->json('put', $uri, $dataText);
		$response->assertStatus(401); // Unauthorized
	}

	public function testDeleteTextSuccess() {
		// Resource
		$resource = Text::has('foreignIds')->first();
		$this->assertInstanceOf(Text::class, $resource);

		$foreignResourceId = $resource->foreignIds()->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);

		// Resource needs to have material
		$this->assertGreaterThanOrEqual(1, $resource->materials->count());

		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);

		// First Fail (because materials exist)
		$uri      = route('api.v1.foreignResources.delete', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$response = $this->json('delete', $uri);
		$response->assertStatus(409);
		$response->assertJsonStructure(['message']);
		$resource = $resource->fresh();
		$this->assertNotNull($resource);

		$resource->materials()->detach();
		$response = $this->json('delete', $uri);

		$response->assertStatus(200);
		$response->assertJsonMissing(['message']);

		$resource = $resource->fresh();
		$this->assertNull($resource);
	}

	public function testDeleteFileSuccessAndRemoved() {
		/** @var File $resource */
		$resource = File::has('foreignIds')->first();
		$this->assertNull($resource);

		$foreignResourceIds = $resource->foreignIds;
		$foreignResourceIds->pop();
		$foreignResourceIds->each(function (ForeignResourceId $frid) {
			$frid->delete();
		});

		$resource = $resource->fresh(['foreignIds']);
		$this->assertInstanceOf(File::class, $resource);
		$this->assertEquals(1, $resource->foreignIds->count());

		$foreignResourceId = $resource->foreignIds->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);

		$resource->materials()->detach();
		$this->assertLessThan(1, $resource->materials->count());

		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);

		$this->assertTrue($resource->localFileExists(), 'There is not real file attached to be deleted');

		// First Fail (because materials exist)
		$uri      = route('api.v1.foreignResources.delete', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$response = $this->json('delete', $uri);

		$response->assertStatus(200);
		$response->assertJsonMissing(['message']);

		$this->assertFalse($resource->localFileExists(), 'File has not been deleted');


		$resource = $resource->fresh();
		$this->assertNull($resource);
	}

	public function testDeleteFileSuccessButNotRemoved() {
		/** @var File $resource */
		$resource = File::has('foreignIds', '>=', 2)->first();
		$this->assertInstanceOf(File::class, $resource);

		$foreignResourceId = $resource->foreignIds()->first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);

		$resource->materials()->detach();
		$this->assertLessThan(1, $resource->materials->count());

		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);

		$this->assertTrue($resource->localFileExists(), 'There is not real file attached to be deleted');

		// First Fail (because materials exist)
		$uri      = route('api.v1.foreignResources.delete', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$response = $this->json('delete', $uri);

		$response->assertStatus(200);
		$response->assertJsonMissing(['message']);

		$this->assertFalse($resource->localFileExists(), 'File should be deleted');

		$resource = $resource->fresh();
		$this->assertNull($resource);

		$foreignResourceId = $foreignResourceId->fresh();
		$this->assertNull($foreignResourceId);
	}

	public function testDeleteForbidden() {

		$this->authenticatePassport();

		$resourceUser = User::where('id', '!=', $this->testUser->id)->first();
		$this->assertInstanceOf(User::class, $resourceUser);

		$resource = new Text();
		$resource->creator()->associate($resourceUser);
		$resource->content = "Das ist viel Text :-)";
		$resource->notes   = "Meine Notitzen";
		$resource->save();

		$foreignResourceId = ResourceSeeder::addRandomResourceUid($resource, $resourceUser);

		$this->assertInstanceOf(Text::class, $resource);

		// Resource needs to have material
		$this->assertLessThan(1, $resource->materials->count());

		$uri      = route('api.v1.foreignResources.delete', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$response = $this->json('delete', $uri);

		$response->assertStatus(403); // Forbidden

	}

	public function testDeleteUnauthorized() {
		// Resource
		$foreignResourceId = ForeignResourceId::first();
		$this->assertInstanceOf(ForeignResourceId::class, $foreignResourceId);

		// NO authorization

		$uri      = route('api.v1.foreignResources.delete', ['foreignResourceId' => $foreignResourceId->foreign_id]);
		$response = $this->json('delete', $uri);
		$response->assertStatus(401); // Unauthorized
	}


}
