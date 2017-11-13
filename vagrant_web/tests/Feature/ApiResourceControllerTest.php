<?php

namespace Tests\Feature;

use App\Jobs\CheckDuplicateResources;
use App\Jobs\UpdateResourceHashes;
use App\Models\File;
use App\Models\ImageFile;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\Text;
use App\Models\Url;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\TestCase;
use function GuzzleHttp\Psr7\mimetype_from_filename;

class ApiResourceControllerTest extends TestCase {

	use DatabaseMigrations, ResourceTrait;

	/** @var  Filesystem $testStorage */
	protected $testStorage;
	protected $testUser;

	public function setUp() {
		parent::setUp();

		$this->setUpTestData();

		/** @var User $testUser */
		$this->testUser = User::first();
		$this->assertInstanceOf(User::class, $this->testUser);

		// Setup TestStorage
		$localAdapter      = new Local(__DIR__ . '/../testFiles');
		$this->testStorage = new \League\Flysystem\Filesystem($localAdapter);
	}

	public function tearDown() {
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

	protected function authenticatePassport() {

		Passport::actingAs(
			$this->testUser,
			[]
		);

	}

	protected function getUploadedFile($path, $name) {
		return new UploadedFile($path, $name, mimetype_from_filename($path), filesize($path), NULL, TRUE);
	}

	protected function uploadFilesSuccessful($data, $isFileResource = TRUE) {
		$storageDisk = 'resources';
		Storage::fake($storageDisk);

		// Resources-Uri
		$uri      = route('api.v1.resources.store');
		$response = $this->json('post', $uri, $data);

		/** @var \Illuminate\Http\Testing\File $file */
		$file = $isFileResource ? $data['file'] : NULL;

		$responseData = $response->json();
		$response->assertStatus(200);


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


		// Inhalt validieren
		$response->assertJsonFragment(['is_public' => $data['is_public'],
									   'notes'     => $data['notes']]);

		if (TRUE == $isFileResource) {
			$response->assertJsonFragment(
				['original_filename' => $file->getClientOriginalName()]
			);
		} else if (isset($data['content'])) {
			$responseContent = $responseData['content'];
			$this->assertGreaterThanOrEqual(0, strpos($data['content'], $responseContent),
											'Content is included propperly');
		}

		/** @var Resource $resource */
		$resource = Resource::find($responseData['id']);

		$this->assertInstanceOf(Resource::class, $resource);
		$this->assertTrue($resource->exists, 'Expecting Resource to exist');

		if (TRUE === $isFileResource) {
			$this->assertInstanceOf(File::class, $resource);

			$this->assertTrue(Storage::disk($storageDisk)->exists($resource->getLocalFilePath()));
			$this->assertTrue($resource->hasLocalFile());
			$this->assertNotNull($resource->getLocalUrl());
			$this->assertNotNull($resource->getLocalMimeType());
			$this->assertTrue($resource->hasLocalFile());

			$this->assertTrue($resource->deleteLocalFile());

			$this->assertFalse(Storage::disk($storageDisk)->exists($resource->getLocalFilePath()));
			$this->assertNull($resource->local_path);
			$this->assertFalse($resource->hasLocalFile());
		}

		$this->assertNotEmpty($responseData['content_hash'], "Expecting a content hash.");


		// FIXME: Not working for documents :/
		// $this->assertGreaterThan(0, $resource->getLocalSize());
		// $this->assertEquals($file->getSize(), $resource->getLocalSize());


		return $response;
	}

	public function testCreateResourcePdfFileUpload() {

		$this->authenticatePassport();

		// PDF Upload
		$dataPdf = [
			'file'                          => $this->getUploadedFile(__DIR__ . '/../testFiles/PDF.pdf',
																	  'Balloning.pdf'),
			'notes'                         => 'Viele Notizen',
			'is_public'                     => TRUE,
			'create_material_from_resource' => TRUE,
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
