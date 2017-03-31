<?php

namespace Tests\Feature;

use App\Models\Keyword;
use App\Models\Language;
use App\Models\Person;
use App\Models\Place;
use App\Services\TagExtraction\Properties\KeywordProperty;
use App\Services\TagExtraction\Properties\Property;
use App\Services\TagExtraction\TagExtractionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TagExtractionServiceTest extends TestCase {

	use DatabaseMigrations;

	/** @var $service TagExtractionService */
	protected $service = NULL;

	public function setUp() {
		parent::setUp();

		$this->service = resolve('app.resource.keyword.recognition');
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testServiceProvider() {
		$this->assertInstanceOf(TagExtractionService::class, $this->service);
	}

	public function testExtractSingleKeyword() {

		$notExistingKeyword = Keyword::all();
		$this->assertCount(0, $notExistingKeyword);

		$coll = $this->service->extractPartsFromStrings('Blub', 1);
		$this->assertCount(1, $coll);

		$existingKeyword = Keyword::where(['title' => 'Blub'])->first();
		$this->assertNull($existingKeyword);

		$this->assertInstanceOf(KeywordProperty::class, $coll->first());
		$this->assertInstanceOf(Keyword::class, $coll->first()->getValue());

		$coll->first()->getValue()->save();
		$existingKeyword = Keyword::where(['title' => 'Blub'])->first();
		$this->assertNotNull($existingKeyword);
	}

	public function testExtractSingleKeywordNot() {

		$notExistingKeyword = Keyword::all();
		$this->assertCount(0, $notExistingKeyword);

		$coll = $this->service->extractPartsFromStrings('Blub', 2);
		$this->assertCount(0, $coll);

		$existingKeyword = Keyword::where(['title' => 'Blub'])->first();
		$this->assertNull($existingKeyword);
	}

	public function testExtractTwoKeywords() {

		$notExistingKeyword = Keyword::all();
		$this->assertCount(0, $notExistingKeyword);

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
		$this->assertNotNull($coll->first()->getValue()->id);

	}

	protected function saveCollectionEntities(Collection $coll) {
		$coll->each(function ($el) {
			if ($el instanceof Property) {
				$el->getValue()->save();
			} else if ($el instanceof Model) {
				$el->save();
			}
		});
	}

	public function testExtractPersons() {

		$notExistingKeyword = Keyword::all();
		$this->assertCount(0, $notExistingKeyword);

		$coll = $this->service->extractPartsFromStrings('Blub, Person:Steven Buehner', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Person::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Steven Buehner", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, Person: Steven Buehner', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Person::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Steven Buehner", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, Person:   Steven Buehner ', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Person::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Steven Buehner", $existingKeyword->first()->title);
	}

	public function testExtractLanguage() {

		$notExistingKeyword = Keyword::all();
		$this->assertCount(0, $notExistingKeyword);

		$coll = $this->service->extractPartsFromStrings('Blub, lang:de', 2);
		$this->saveCollectionEntities($coll);

		$this->assertCount(2, $coll);
		$existingKeyword = Language::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("DE", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, lang: de', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Language::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("DE", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, LANG:DE', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Language::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("DE", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, lang:deutsch', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Language::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("DE", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, deutsch', 2);
		$this->assertCount(2, $coll);
		$existingKeyword = Language::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("DE", $existingKeyword->first()->title);
	}

	public function testExtractPlace() {
		$notExistingKeyword = Keyword::all();
		$this->assertCount(0, $notExistingKeyword);

		$coll = $this->service->extractPartsFromStrings('Blub, place:Hamburg', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Place::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Hamburg", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, place: Hamburg', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Place::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Hamburg", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, city:  Hamburg', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Place::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Hamburg", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, ort:Hamburg', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Place::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Hamburg", $existingKeyword->first()->title);


		$coll = $this->service->extractPartsFromStrings('Blub, stadt: Hamburg', 2);
		$this->saveCollectionEntities($coll);
		$this->assertCount(2, $coll);
		$existingKeyword = Place::all();
		$this->assertCount(1, $existingKeyword);
		$this->assertEquals("Hamburg", $existingKeyword->first()->title);
	}


}
