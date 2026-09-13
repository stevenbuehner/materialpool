<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\ImageFile;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\Text;
use App\Models\Url;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiResourceControllerTest extends TestCase {

	use RefreshDatabase, ResourceTrait;

	/** @var FilesystemAdapter $testStorage */
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
			'file'                          => $this->getUploadedFile(__DIR__ . '/../testFiles/Bild.jpg',
																	  'Grafik, Bild, Darstellung, Evangelium, Verlorene Menschheit, Jesus, rettender Gott, Blatt 1.jpg'),
			'notes'                         => 'keine Notiz',
			'is_public'                     => FALSE,
			'create_material_from_resource' => TRUE,
			'foreign_material_id'           => uniqid('test_', TRUE)
		];

		$response = $this->uploadFilesSuccessful($dataBild);
		$resource = $response->getOriginalContent();
		$this->assertInstanceOf(ImageFile::class, $resource);
	}

	protected function getUploadedFile($path, $name) {
		return new UploadedFile($path, $name, mime_content_type($path), UPLOAD_ERR_OK, TRUE);
	}

	protected function uploadFilesSuccessful($data, $isFileResource = TRUE) {

		// Resources-Uri
		$uri      = route('api.v1.resources.store');
		$response = $this->json('post', $uri, $data);

		/** @var \Illuminate\Http\Testing\File $file */
		$file = $isFileResource ? $data['file'] : NULL;

		$responseData = $response->json();
		$response->assertStatus(200);

		$this->verifyResourceJsonResult($response, $isFileResource);

		// Inhalt validieren
		$response->assertJsonFragment(['is_public' => $data['is_public'],
									   'notes'     => $data['notes']]);

		if (TRUE == $isFileResource) {
			$response->assertJsonFragment(
				['original_filename' => $file->getClientOriginalName()]
			);
			$this->assertSame($file->getSize(), $responseData['filesize']);
			$this->assertFalse(isset($responseData['content']), 'content not allowed here');
		} else if (isset($data['content'])) {
			$responseContent = $responseData['content'];
			$this->assertGreaterThanOrEqual(0, strpos($data['content'], $responseContent),
											'Content is included propperly');
			$expectedFilesize = $responseData['type'] === 'text'
				? strlen($responseContent)
				: NULL;
			$this->assertSame($expectedFilesize, $responseData['filesize']);
			$this->assertFalse(isset($responseData['original_filename']), 'original_filename not allowed here');
		}

		/** @var Resource $resource */
		$resource = Resource::find($responseData['id']);

		$this->assertInstanceOf(Resource::class, $resource);
		$this->assertTrue($resource->exists, 'Expecting Resource to exist');

		if (TRUE === $isFileResource) {
			$this->assertInstanceOf(File::class, $resource);
			$localFilePath = $resource->getLocalFilePath();

			$this->assertTrue(Storage::disk(config('app.disks.resources'))->exists($localFilePath));
			$this->assertTrue($resource->hasLocalFile());
			$this->assertNotNull($resource->getLocalUrl());
			$this->assertNotNull($resource->getLocalMimeType());
			$this->assertTrue($resource->hasLocalFile());

			$this->assertTrue($resource->deleteLocalFile());

			$this->assertFalse(Storage::disk(config('app.disks.resources'))->exists($localFilePath));
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
			'content_hash',
			'filesize'
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
			'file'                          => $this->getUploadedFile(__DIR__ . '/../testFiles/PDF.pdf',
																	  'Balloning.pdf'),
			'notes'                         => 'Viele Notizen',
			'is_public'                     => TRUE,
			'create_material_from_resource' => FALSE,
			'foreign_material_id'           => uniqid('test_', TRUE)
		];

		$response = $this->uploadFilesSuccessful($dataPdf);
		$resource = $response->getOriginalContent();
		$this->assertInstanceOf(PdfFile::class, $resource);

	}

	public function testCreateResourceTextFileUpload() {

		$this->authenticatePassport();

		// Text Upload as file
		$dataText = [
			'file'                          => $this->getUploadedFile(__DIR__ . '/../testFiles/Text.txt',
																	  'Einfältig, Beispiel, Papier, von Klaus-Dieter Mauer, am 18.10.2015, Richtung.txt'),
			'notes'                         => 'Das ist egal',
			'is_public'                     => FALSE,
			'create_material_from_resource' => TRUE,
			'foreign_material_id'           => uniqid('test_', TRUE)
		];

		$response = $this->uploadFilesSuccessful($dataText, $isFileResource = FALSE);
		$resource = $response->getOriginalContent();
		$this->assertInstanceOf(Text::class, $resource);
	}

	public function testCreateResourceByTextContent() {

		$this->authenticatePassport();

		// Text Upload
		$dataText = [
			'content'                       => 'Das ist mein toller Inhalt, den es zu würdigen sich lohnt!',
			'notes'                         => 'keine Notiz',
			'is_public'                     => TRUE,
			'create_material_from_resource' => TRUE,
			'foreign_material_id'           => uniqid('test_', TRUE)
		];

		$response = $this->uploadFilesSuccessful($dataText, FALSE);
		$resource = $response->getOriginalContent();
		$this->assertInstanceOf(Text::class, $resource);
	}


	public function testCreateResourceByOtherTextContent() {

		$this->authenticatePassport();

		// Check Without MaterialCreation
		// Text Upload
		$dataText = [
			'content'   => 'Das ist mein anderer Inhalt, den es zu würdigen sich lohnt!',
			'notes'     => 'keine Notiz',
			'is_public' => TRUE,
		];

		$response = $this->uploadFilesSuccessful($dataText, FALSE);
		$resource = $response->getOriginalContent();
		$this->assertInstanceOf(Text::class, $resource);
		$this->assertEquals(0, $resource->materials->count(), "Erwarte kein verknüpftes Material");

	}


	public function testCreateResourceByUrlContent() {

		$this->authenticatePassport();

		// Url-Content
		$dataUrl = [
			'content'   => "http://www.google.de",
			'notes'     => 'Super Suchmaschine - oder auch nicht',
			'is_public' => TRUE,
		];

		$response = $this->uploadFilesSuccessful($dataUrl, FALSE);
		$resource = $response->getOriginalContent();
		$this->assertInstanceOf(Url::class, $resource);
		$this->assertEquals(0, $resource->materials->count(), "Erwarte kein verknüpftes Material");
	}

	public function testCreateFailUnauthorizied() {

		// Dont't authorize via oAuth

		// Resources-Uri
		$uri      = route('api.v1.resources.store');
		$dataText = [
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
		$testuser = $this->testUser;
		$resource = $testuser->resources->first();
		$this->assertInstanceOf(Resource::class, $resource);

		$uri          = route('api.v1.resources.show', ['resource' => $resource->id]);
		$response     = $this->json('get', $uri);
		$responseData = $response->json();


		$response->assertStatus(200);
		$this->verifyResourceJsonResult($response, $isFileResource = $resource instanceof File);
	}

	public function testGetResourceFile() {

		$resource = File::all()->first();
		$this->assertInstanceOf(File::class, $resource);

		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);

		$uri          = route('api.v1.resources.show', ['resource' => $resource->id]);
		$response     = $this->json('get', $uri);
		$responseData = $response->json();


		$response->assertStatus(200);
		$this->verifyResourceJsonResult($response, $isFileResource = $resource instanceof File);
	}

	public function testGetResourceText() {

		$resource = Text::all()->first();
		$this->assertInstanceOf(Text::class, $resource);

		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);

		$uri          = route('api.v1.resources.show', ['resource' => $resource->id]);
		$response     = $this->json('get', $uri);
		$responseData = $response->json();


		$response->assertStatus(200);
		$this->verifyResourceJsonResult($response, $isFileResource = $resource instanceof File);
	}

	public function testGetResourceShowNotFound() {

		$this->authenticatePassport();
		$uri      = route('api.v1.resources.show', ['resource' => 999999]);
		$response = $this->json('get', $uri);

		$response->assertStatus(404);
	}


	public function testGetResourceForbidden() {

		$this->authenticatePassport();

		$resource = Resource::where(
			'created_by', '!=', $this->testUser->id
		)->first();
		$this->assertInstanceOf(Resource::class, $resource);

		// Make shure it is not public -> not accessible
		$resource->is_public = FALSE;
		$resource->save();

		$uri      = route('api.v1.resources.show', ['resource' => $resource->id]);
		$response = $this->json('get', $uri);

		$response->assertStatus(403); // Forbidden
	}

	public function testGetResourceUnauthorized() {

		$uri      = route('api.v1.resources.show', ['resource' => 999999]);
		$response = $this->json('get', $uri);

		$response->assertStatus(401); // Unauthorized
	}

	public function testUpdateResourceAttributes() {

		$this->authenticatePassport();

		/** @var User $user */
		$user = $this->testUser;
		$this->assertInstanceOf(User::class, $user);

		$resource = $user->resources->first();
		$this->assertInstanceOf(Resource::class, $resource);

		$uri = route('api.v1.resources.update', ['resource' => $resource->id]);

		// Not allowed to change created_by
		$response = $this->put($uri, ['created_by' => 999]);
		$response->assertStatus(200);
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent());
		$this->assertNotSame($resource, $response->getOriginalContent());
		$this->assertEquals($resource->created_by, $response->getOriginalContent()->created_by);

		// Not allowed to change id
		$response = $this->put($uri, ['id' => 999]);
		$response->assertStatus(200);
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent());
		$this->assertNotSame($resource, $response->getOriginalContent());
		$this->assertEquals($resource->id, $response->getOriginalContent()->id);

		// Not allowed to change options
		$response = $this->put($uri, ['options' => 'Test']);
		$response->assertStatus(200);
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent());
		$this->assertNotSame($resource, $response->getOriginalContent());
		$this->assertEquals($resource->attributesToArray(), $response->getOriginalContent()->attributesToArray());

		// Not allowed to change type
		$response = $this->put($uri, ['type' => 'test']);
		$response->assertStatus(200);
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent());
		$this->assertNotSame($resource, $response->getOriginalContent());
		$this->assertEquals($resource->type, $response->getOriginalContent()->type);

		// Not allowed to change hash
		$response = $this->put($uri, ['content_hash' => 'test']);
		$response->assertStatus(200);
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent());
		$this->assertNotSame($resource, $response->getOriginalContent());
		$this->assertEquals($resource->content_hash, $response->getOriginalContent()->content_hash);

		// Not allowed to change created_at
		$response = $this->put($uri, ['created_at' => '2017-09-09']);
		$response->assertStatus(200);
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent());
		$this->assertNotSame($resource, $response->getOriginalContent());
		$this->assertEquals($resource->created_at, $response->getOriginalContent()->created_at);

		// Not allowed to change updated_at
		$response = $this->put($uri, ['updated_at' => '2017-09-09']);
		$response->assertStatus(200);
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent());
		$this->assertNotSame($resource, $response->getOriginalContent());
		$this->assertEquals($resource->updated_at, $response->getOriginalContent()->updated_at);


		// Should work!
		$response = $this->put($uri, ['notes' => $notes = 'This is a different title']);
		$response->assertStatus(200);
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent());
		$this->assertNotSame($resource, $response->getOriginalContent());
		$this->assertEquals($notes, $response->getOriginalContent()->notes);

		// Should work!
		$response = $this->put($uri, ['remote_path' => $remotePath = 'http://google.de']);
		$response->assertStatus(200);
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent());
		$this->assertNotSame($resource, $response->getOriginalContent());
		$this->assertEquals($remotePath, $response->getOriginalContent()->remote_path);

		// Should work!
		$resource->is_public = FALSE;
		$resource->save();
		$response = $this->put($uri, ['is_public' => $isPublic = TRUE]);
		$response->assertStatus(200);
		$this->assertInstanceOf(Resource::class, $response->getOriginalContent());
		$this->assertNotSame($resource, $response->getOriginalContent());
		$this->assertEquals($isPublic, $response->getOriginalContent()->is_public);
	}

	public function testUpdateFileIsIgnoredForNonFileResource() {

		// Not Image-Resource
		$resource = Text::first();
		$this->assertInstanceOf(Resource::class, $resource);


		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);


		$originalContent = $resource->content;

		// Bestehender Vertrag: Ein Datei-Parameter ändert eine Text-Resource nicht.
		$uri      = route('api.v1.resources.update', ['resource' => $resource->id]);
		$data     = [
			'file' => $this->getUploadedFile(__DIR__ . '/../testFiles/Bild.jpg',
											 $originalFilename = 'Langer Bildname.jpg'),
		];
		$response = $this->put($uri, $data);

		$response->assertStatus(200);
		$this->assertInstanceOf(Text::class, $response->getOriginalContent());
		$this->assertEquals($originalContent, $resource->fresh()->content);
	}

	public function testUpdateFileFailNotContenttype() {

		// Not Image-Resource
		$resource = File::first();
		$this->assertInstanceOf(File::class, $resource);


		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);


		// Should not work, because not a filetype
		$uri      = route('api.v1.resources.update', ['resource' => $resource->id]);
		$data     = [
			'content' => 'Das waere schlecht, wenn das ginge'
		];
		$response = $this->put($uri, $data);

		$response->assertStatus(500);
		$response->assertJsonStructure(['message']);
	}


	public function testUpdateFileSuccess() {

		// Not Image-Resource
		$resource = File::first();
		$this->assertInstanceOf(File::class, $resource);

		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);


		// Should not work, because not a filetype
		$uri      = route('api.v1.resources.update', ['resource' => $resource->id]);
		$data     = [
			'file' => $this->getUploadedFile(__DIR__ . '/../testFiles/Bild.jpg',
											 $originalFilename = 'Langer Bildname.jpg'),
		];
		$response = $this->put($uri, $data);

		$response->assertStatus(200);
		$this->verifyResourceJsonResult($response, $isFile = TRUE);
		$this->assertInstanceOf(ImageFile::class, $response->getOriginalContent());
		$resource = $resource->fresh();
		$this->assertEquals($response->getOriginalContent()->attributesToArray(), $resource->attributesToArray());

	}

	public function testUpdateContentSuccess() {

		// Resource
		$resource = Text::first();
		$this->assertInstanceOf(Text::class, $resource);

		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);


		// Should not work, because not a filetype
		$uri      = route('api.v1.resources.update', ['resource' => $resource->id]);
		$data     = [
			'content' => "Ein schöner Rücken kann auch entzücken"
		];
		$response = $this->put($uri, $data);

		$response->assertStatus(200);
		$this->verifyResourceJsonResult($response, $isFile = FALSE);
		$this->assertInstanceOf(Text::class, $response->getOriginalContent());
		$resource = $resource->fresh();
		$this->assertEquals($response->getOriginalContent()->attributesToArray(), $resource->attributesToArray());
	}

	public function testUpdateForbidden() {

		// Resource
		$resource = Text::first();
		$this->assertInstanceOf(Text::class, $resource);

		// Different user than Resource-Owner
		$user = User::where('id', '!=', $resource->created_by)->first();
		$this->assertInstanceOf(User::class, $user);

		// authorize via oAuth
		$this->authenticatePassport($user);

		// Resources-Uri
		$uri      = route('api.v1.resources.update', ['resource' => $resource->id]);
		$dataText = [
			'content'   => 'Das ist mein anderer Inhalt, den es zu würdigen sich lohnt!',
			'notes'     => 'keine Notiz',
			'is_public' => TRUE,
		];

		$response = $this->json('put', $uri, $dataText);
		$response->assertStatus(403); // Forbidden
	}

	public function testUpdateUnauthorized() {

		// Resource
		$resource = Text::first();
		$this->assertInstanceOf(Text::class, $resource);

		// Dont't authorize via oAuth

		// Resources-Uri
		$uri      = route('api.v1.resources.update', ['resource' => $resource->id]);
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
		$resource = Text::first();
		$this->assertInstanceOf(Text::class, $resource);

		// Resource needs to have material
		$this->assertGreaterThanOrEqual(1, $resource->materials->count());

		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);

		// First Fail (because materials exist)
		$uri      = route('api.v1.resources.delete', ['resource' => $resource->id]);
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

	public function testDeleteFileSuccess() {
		/** @var File $resource */
		$resource = File::first();
		$this->assertInstanceOf(File::class, $resource);

		$resource->materials()->detach();
		$this->assertLessThan(1, $resource->materials->count());

		/** @var User $user */
		$user = $resource->creator;
		$this->assertInstanceOf(User::class, $user);

		$this->authenticatePassport($user);

		$this->assertTrue($resource->localFileExists(), 'There is not real file attached to be deleted');

		// First Fail (because materials exist)
		$uri      = route('api.v1.resources.delete', ['resource' => $resource->id]);
		$response = $this->json('delete', $uri);

		$response->assertStatus(200);
		$response->assertJsonMissing(['message']);

		$this->assertFalse($resource->localFileExists(), 'File has not been deleted');


		$resource = $resource->fresh();
		$this->assertNull($resource);
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


		$this->assertInstanceOf(Text::class, $resource);

		// Resource needs to have material
		$this->assertLessThan(1, $resource->materials->count());

		$uri      = route('api.v1.resources.delete', ['resource' => $resource->id]);
		$response = $this->json('delete', $uri);

		$response->assertStatus(403); // Forbidden

	}

	public function testDeleteUnauthorized() {
		// Resource
		$resource = Text::first();
		$this->assertInstanceOf(Text::class, $resource);

		// NO authorization

		$uri      = route('api.v1.resources.delete', ['resource' => $resource->id]);
		$response = $this->json('delete', $uri);
		$response->assertStatus(401); // Unauthorized
	}


}
