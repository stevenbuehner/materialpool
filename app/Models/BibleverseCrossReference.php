<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface;

/**
 * @property int source
 * @property int target_from
 * @property int target_to
 * @property int relevance
 */
class BibleverseCrossReference extends Model {
	use HasFactory;

	protected $table      = 'bibleverses_cross_ref';
	public    $timestamps = FALSE;

	protected $casts    = [
		'source'      => 'integer',
		'target_from' => 'integer',
		'target_to'   => 'integer',
		'relevance'   => 'integer',
	];
	protected $fillable = [
		'source',
		'target_from',
		'target_to',
		'relevance',
	];

	// Default values
	protected $attributes = [
		'target_to' => 0,
		'relevance' => 0
	];

	public static function getCrossReferencesByBibleverseQuery(BibleVerseInterface $bibleVerse): \Illuminate\Database\Eloquent\Builder {

		$fromToArray = Bibleverse::getFromToCombi($bibleVerse);

		return self::query()
			->whereBetween('source', $fromToArray)
			->orderBy('relevance', 'desc');

	}

}
