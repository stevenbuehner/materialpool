<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Text;
use App\Models\User;
use App\Services\TagExtraction\MaterialExtractionService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MaterialExtractionServiceTest extends TestCase {

	use DatabaseMigrations;

	/** @var $service MaterialExtractionService */
	protected $service = NULL;

	public function setUp() {
		parent::setUp();

		$this->service = resolve(MaterialExtractionService::class);
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testServiceProvider() {
		$this->assertInstanceOf(MaterialExtractionService::class, $this->service);
	}

	public function testTextBasedMaterialExtraction() {
		$content = "Hallo, Test, Hallo, Person: Steven Buehner, Title: Mein Testtitel; 1Kor 3,16
		danach kommt noch was ganz anderes - nämlich:
		Hallo du!";

		$localPath = config('filesystems.disks.resources.root');
		$user      = factory(User::class)->create();

		$user = factory(User::class)->create();
		$text = factory(Text::class)
			->create([
						 'created_by'  => $user->id,
						 'is_public'   => FALSE,
						 'content'     => $content,
						 'local_path'  => NULL,
						 'remote_path' => NULL
					 ]);


		$material = $this->service->createGuessedMaterialFromResource($text);
		$this->assertInstanceOf(Material::class, $material);
		$this->assertTrue($material->exists);

		$this->assertCount(3, $material->keywords);
		$this->assertCount(1, $material->persons);
		$this->assertCount(1, $material->bibleverses);
		$this->assertEquals('Mein Testtitel', $material->title);
		$this->assertEquals('1Kor 3,16', $material->bibleverses->first()->label);
	}

}
