<?php

namespace Tests\Handlers;

use App\Models\Text;
use App\Models\User;
use App\Services\TagExtraction\ResourceHandles\FileNameHandler;
use App\Services\TagExtraction\ResourceHandles\TextContentHandler;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TextContentHandlerTest extends TestCase {

	use DatabaseMigrations;

	/** @var $service FileNameHandler */
	protected $service = NULL;

	public function setUp() {
		parent::setUp();
		$this->service = resolve(TextContentHandler::class);

		$content = "Hallo, Test, Person: Steven Buehner, Title: Mein Testtitel; 1Kor 3,16
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
	}

	public function testServiceProvider() {
		$this->assertInstanceOf(TextContentHandler::class, $this->service);
	}

	public function testHandleText() {

		$textResource = Text::all()->first();
		/** @var Collection $result */
		$result = $this->service->handle($textResource);

		$this->assertInstanceOf(Collection::class, $result);
		$this->assertGreaterThan(0, $result->count());

		$groups = $result->groupBy(function ($el) {
			return class_basename($el);
		});

		$this->assertCount(1, $groups->get('OcrTextProperty')); // Inhalt ...
		$this->assertCount(1, $groups->get('TitleProperty')); // Mein Testtitel
		$this->assertCount(1, $groups->get('BibleverseProperty')); // 1Kor 3,16
		$this->assertCount(3, $groups->get('KeywordProperty')); // Hallo, Test, Person: Steven Buehner,

	}


}
