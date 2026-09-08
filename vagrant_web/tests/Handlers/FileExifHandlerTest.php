<?php

namespace Tests\Handlers;

use App\Models\ImageFile;
use App\Models\User;
use App\Services\TagExtraction\ResourceHandles\FileExifHandler;
use App\Services\TagExtraction\ResourceHandles\FileNameHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileExifHandlerTest extends TestCase {

	use RefreshDatabase;

	/** @var $service FileNameHandler */
	protected $service = NULL;

	protected function setUp(): void {
		parent::setUp();
		$this->service = resolve(FileExifHandler::class);

		Storage::fake(config('app.disks.resources'));

		$image = file_get_contents(base_path('tests/testFiles/Bild.jpg'));
		Storage::disk(config('app.disks.resources'))->put($path = 'testimage.jpg', $image);


		$user  = User::factory()->create();
		$image = ImageFile::factory()
			->create([
						 'created_by'        => $user->id,
						 'is_public'         => FALSE,
						 'original_filename' => 'testimage.jpg',
						 'local_path'        => config('app.disks.resources') . '::' . $path
					 ]);

	}

	protected function tearDown(): void {
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
