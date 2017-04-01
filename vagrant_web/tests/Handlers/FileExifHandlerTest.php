<?php

namespace Tests\Handlers;

use App\Models\ImageFile;
use App\Models\User;
use App\Services\TagExtraction\ResourceHandles\FileExifHandler;
use App\Services\TagExtraction\ResourceHandles\FileNameHandler;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileExifHandlerTest extends TestCase {

	use DatabaseMigrations;

	/** @var $service FileNameHandler */
	protected $service = NULL;

	public function setUp() {
		parent::setUp();
		$this->service = resolve(FileExifHandler::class);

		Storage::fake(config('app.disks.resources'));

		$localPath = config('filesystems.disks.resources.root');
		$user      = factory(User::class)->create();

		// Thos would not copy the EXIF-Meta-Data !!!
		// $canvas = Image::make(__DIR__ . DIRECTORY_SEPARATOR . 'testbild.jpg')->stream();
		// Storage::disk(config('app.disks.resources'))->put($path = 'testimage.jpg', $canvas);

		$image = Storage::disk('local')->get('tests/testbild.jpg');
		Storage::disk(config('app.disks.resources'))->put($path = 'testimage.jpg', $image);


		$user  = factory(User::class)->create();
		$image = factory(ImageFile::class)
			->create([
						 'created_by'        => $user->id,
						 'is_public'         => FALSE,
						 'original_filename' => 'testimage.jpg',
						 'local_path'        => config('app.disks.resources') . '::' . $path
					 ]);

		return $image;

	}

	public function tearDown() {
		Storage::disk(config('app.disks.resources'))->delete('testimage.jpg');

		parent::tearDown();
	}

	public function testServiceProvider() {
		$this->assertInstanceOf(FileExifHandler::class, $this->service);
	}

	public function testHandleJpg() {

		$imageResource = ImageFile::all()->first();
		$result        = $this->service->handle($imageResource);

		$this->assertInstanceOf(Collection::class, $result);
		$this->assertGreaterThan(0, $result->count());
	}


}
