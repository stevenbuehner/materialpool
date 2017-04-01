<?php

namespace Tests\Handlers;

use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\User;
use App\Services\TagExtraction\Properties\AuthorProperty;
use App\Services\TagExtraction\Properties\KeywordProperty;
use App\Services\TagExtraction\Properties\TitleProperty;
use App\Services\TagExtraction\ResourceHandles\FileNameHandler;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Tests\TestCase;

class FileNameHandlerTest extends TestCase {

	use DatabaseMigrations;

	/** @var $service FileNameHandler */
	protected $service = NULL;

	public function setUp() {
		parent::setUp();
		$this->service = resolve(FileNameHandler::class);


	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testServiceProvider() {
		$this->assertInstanceOf(FileNameHandler::class, $this->service);
	}

	public function testExtractSingleKeyword() {
		$resource = $this->createResourceWithFilename('test Dateiname.jpg');
		$this->assertNotNull($resource);
		$this->assertNotNull($resource->id);

		$result = $this->service->handle($resource);

		$this->assertInstanceOf(Collection::class, $result);
		$this->assertNotNull($result);
		$this->assertCount(1, $result);

		$this->assertInstanceOf(TitleProperty::class, $result[0]);
		$this->assertEquals('test Dateiname', $result[0]->getValue());
	}

	public function testExtractMultipleCommas() {
		$resource = $this->createResourceWithFilename('test, Dateiname, von Steven B; Haus.jpg');
		$this->assertNotNull($resource);
		$this->assertNotNull($resource->id);

		$result = $this->service->handle($resource);

		$this->assertInstanceOf(Collection::class, $result);
		$this->assertNotNull($result);
		$this->assertCount(6, $result);


		$this->assertInstanceOf(KeywordProperty::class, $result->get(0));
		$this->assertInstanceOf(KeywordProperty::class, $result->get(1));
		$this->assertInstanceOf(KeywordProperty::class, $result->get(2));
		$this->assertInstanceOf(AuthorProperty::class, $result->get(3));
		$this->assertInstanceOf(KeywordProperty::class, $result->get(4));
		$this->assertInstanceOf(TitleProperty::class, $result->get(5));

		$this->assertInstanceOf(Keyword::class, $result->get(0)->getValue());
		$this->assertInstanceOf(Keyword::class, $result->get(1)->getValue());
		$this->assertInstanceOf(Keyword::class, $result->get(2)->getValue());
		$this->assertEquals('Steven B', $result->get(3)->getValue());
		$this->assertInstanceOf(Keyword::class, $result->get(4)->getValue());
		$this->assertEquals('test, Dateiname, von Steven B; Haus', $result->get(5)->getValue());
	}

	protected function createResourceWithFilename($filename = 'test Dateiname.jpg') {
		$user  = factory(User::class)->create();
		$image = factory(ImageFile::class)
			->create([
						 'created_by'        => $user->id,
						 'is_public'         => FALSE,
						 'original_filename' => $filename
					 ]);

		return $image;
	}


}
