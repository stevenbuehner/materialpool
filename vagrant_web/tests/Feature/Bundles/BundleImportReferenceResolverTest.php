<?php

namespace Tests\Feature\Bundles;

use App\Models\Bibleverse;
use App\Models\Keyword;
use App\Services\Bundles\BundleImportReferenceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleImportReferenceResolverTest extends TestCase {
	use RefreshDatabase;

	public function test_it_reuses_keyword_references_after_their_first_creation(): void {
		$resolver = app(BundleImportReferenceResolver::class);

		$first = $resolver->keyword('Parallel erzeugt', 'key');
		$second = $resolver->keyword('Parallel erzeugt', 'key');

		$this->assertTrue($first->exists);
		$this->assertSame($first->id, $second->id);
		$this->assertSame(1, Keyword::query()->where('title', 'Parallel erzeugt')->where('type', 'key')->count());
	}

	public function test_it_reuses_bibleverse_references_after_their_first_creation(): void {
		$resolver = app(BundleImportReferenceResolver::class);
		$input = new Bibleverse(['from_book_id' => 1, 'from_chapter' => 1, 'from_verse' => 1, 'to_book_id' => 1, 'to_chapter' => 1, 'to_verse' => 1]);

		$first = $resolver->bibleverse($input);
		$second = $resolver->bibleverse($input);

		$this->assertSame($first->id, $second->id);
		$this->assertDatabaseCount('bibleverses', 1);
	}
}
