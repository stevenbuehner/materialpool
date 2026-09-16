<?php

namespace Tests\Feature;

use App\Models\Keyword;
use App\Services\TagExtraction\Properties\KeywordProperty;
use App\Services\TagExtraction\Properties\Property;
use App\Services\TagExtraction\TagRecognition\Created;
use App\Services\TagExtraction\TagExtractionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TagExtractionServiceTest extends TestCase {

	use RefreshDatabase;

	/** @var $service TagExtractionService */
	protected $service = NULL;

	protected function setUp(): void {
		parent::setUp();

		$this->service = resolve('app.resource.keyword.recognition');
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testServiceProvider() {
		$this->assertInstanceOf(TagExtractionService::class, $this->service);
	}

	public function testCreatedRecognitionPreProcessesNullInputWithoutDeprecationWarning() {
		[$result, $tags] = (new Created())->preProcessInput(NULL, []);

		$this->assertSame('', $result);
		$this->assertSame([], $tags);
	}

	public function testExtractSingleKeyword() {

		$notExistingKeyword = Keyword::all();

		$coll = $this->service->extractPartsFromStrings('Blub', 1);
		$this->assertCount(1, $coll);

		$existingKeyword = Keyword::where(['title' => 'Blub'])->first();
		$this->assertNull($existingKeyword);

		$this->assertInstanceOf(KeywordProperty::class, $coll->first());
		$this->assertInstanceOf(Keyword::class, $coll->first()->getKeywordValue());

		$coll->first()->getKeywordValue()->save();
		$existingKeyword = Keyword::where(['title' => 'Blub'])->first();
		$this->assertNotNull($existingKeyword);
	}

	public function testExtractSingleKeywordNot() {

		$notExistingKeyword = Keyword::all();

		$coll = $this->service->extractPartsFromStrings('Blub', 2);
		$this->assertCount(0, $coll);

		$existingKeyword = Keyword::where(['title' => 'Blub'])->first();
		$this->assertNull($existingKeyword);
	}

	public function testExtractTwoKeywords() {

		$notExistingKeyword = Keyword::all();

		$coll = $this->service->extractPartsFromStrings('Blub, Blub2', 2);
		$this->assertCount(2, $coll);

		$existingKeyword = Keyword::where(['title' => 'Blub'])->first();
		$this->assertNull($existingKeyword);

		$this->saveCollectionEntities($coll);

		$existingKeyword = Keyword::where(['title' => 'Blub'])->first();
		$this->assertNotNull($existingKeyword);
		$existingKeyword = Keyword::where(['title' => 'Blub2'])->first();
		$this->assertNotNull($existingKeyword);

		$coll = $this->service->extractPartsFromStrings('Blub, Blub2', 2);
		$this->assertNotNull($coll->first()->getKeywordValue()->id);

	}

	protected function saveCollectionEntities(Collection $coll) {
		$coll->each(function ($el) {
			if ($el instanceof Property) {
				$el->getKeywordValue()->save();
			} else if ($el instanceof Model) {
				$el->save();
			}
		});
	}

	public function testExtractPersons() {

		$notExistingKeyword = Keyword::all();

		$coll = $this->service->extractPartsFromStrings('Blub, Person:Steven Buehner', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'person')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Steven Buehner", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, Person: Steven Buehner', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'person')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Steven Buehner", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, Person:   Steven Buehner ', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'person')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Steven Buehner", $existingKeyword->first()->title);
	}

	public function testExtractLanguage() {

		$notExistingKeyword = Keyword::all();

		$coll = $this->service->extractPartsFromStrings('Blub, lang:de', 2);
		$this->saveCollectionEntities($coll);

		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'lang')->where('title', 'DE')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("DE", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, lang: de', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'lang')->where('title', 'DE')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("DE", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, LANG:DE', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'lang')->where('title', 'DE')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("DE", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, lang:deutsch', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'lang')->where('title', 'DE')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("DE", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, deutsch', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'lang')->where('title', 'DE')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("DE", $existingKeyword->first()->title);
	}

	public function testExtractPlace() {
		$notExistingKeyword = Keyword::all();

		$coll = $this->service->extractPartsFromStrings('Blub, place:Hamburg', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'place')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Hamburg", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, place: Hamburg', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'place')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Hamburg", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, city:  Hamburg', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'place')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Hamburg", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, ort:Hamburg', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'place')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Hamburg", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, stadt: Hamburg', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Keyword::where('type', 'place')->get();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Hamburg", $existingKeyword->first()->title);
	}


}
