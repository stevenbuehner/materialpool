<?php

namespace App\Services\Bundles;

use App\Models\Bibleverse;
use App\Models\Keyword;
use App\Models\Exceptions\InvalidKeywordTypeException;
use Illuminate\Support\Facades\Cache;
use StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface;

/**
 * Resolves shared reference data created while bundle materials are imported.
 *
 * Material jobs may run in parallel. Creating a nested-set keyword changes the
 * complete keyword tree, while a Bibleverse has a database-wide natural key.
 * Existing references deliberately take the lock-free fast path.
 */
class BundleImportReferenceResolver {
	/**
	 * @throws InvalidKeywordTypeException
	 */
	public function keyword(string $value, string $type): Keyword {
		$keyword = Keyword::make($value, $type);

		if ($keyword->exists) {
			return $keyword;
		}

		return Cache::lock('bundle-import:keyword-tree', 30)->block(10, function () use ($value, $type) {
			$keyword = Keyword::make($value, $type);

			if (!$keyword->exists) {
				$keyword->saveOrFail();
			}

			return $keyword;
		});
	}

	public function person(string $name): Keyword {
		return $this->keyword($name, 'person');
	}

	public function bibleverse(BibleVerseInterface $bibleverse, ?int $bibleId = null): Bibleverse {
		[$from, $to] = Bibleverse::getFromToCombi($bibleverse);
		$query = Bibleverse::query()->where(['from' => $from, 'to' => $to]);
		$bibleId === null ? $query->whereNull('bible_id') : $query->where('bible_id', $bibleId);
		$existing = $query->first();

		if ($existing !== null) {
			return $existing;
		}

		return Cache::lock("bundle-import:bibleverse:{$bibleId}:{$from}:{$to}", 30)->block(10, function () use ($bibleverse, $bibleId) {
			return Bibleverse::findOrCreateFromBibleverseInterface($bibleverse, $bibleId);
		});
	}
}
