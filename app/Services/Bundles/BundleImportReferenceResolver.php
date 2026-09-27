<?php

namespace App\Services\Bundles;

use App\Models\Bibleverse;
use App\Models\Exceptions\InvalidKeywordTypeException;
use App\Models\Keyword;
use Illuminate\Support\Facades\Cache;
use StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface;

/**
 * Löst gemeinsam genutzte Referenzen beim Import von Bundle-Materialien auf.
 *
 * Material-Jobs laufen parallel. Ein neues Nested-Set-Schlagwort verändert den
 * gesamten Baum; eine Bibelstelle hat einen datenbankweiten natürlichen Schlüssel.
 * Bereits vorhandene Referenzen benötigen die Sperre dagegen nicht.
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

			if (! $keyword->exists) {
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
		if ($bibleId === null) {
			$query->whereNull('bible_id');
		} else {
			$query->where('bible_id', $bibleId);
		}
		$existing = $query->first();

		if ($existing !== null) {
			return $existing;
		}

		return Cache::lock("bundle-import:bibleverse:{$bibleId}:{$from}:{$to}", 30)->block(10, function () use ($bibleverse, $bibleId) {
			return Bibleverse::findOrCreateFromBibleverseInterface($bibleverse, $bibleId);
		});
	}
}
