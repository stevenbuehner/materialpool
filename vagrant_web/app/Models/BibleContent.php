<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BibleverseContent
 *
 * @package App\Models
 * @property int    $bible_id;
 * @property int    $verse;
 * @property string text;
 *
 */
class BibleContent extends Model {

	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var bool
	 */
	public $timestamps = FALSE;

	protected $fillable = [
		'bible_id', 'verse', 'text'
	];

	protected $casts = [
		'bible_id' => 'integer',
		'verse'    => 'integer',
	];

	public function setVerse($bookId, $chaptrNo, $verseNo) {
		$this->setAttribute('verse', self::getCombi($bookId, $chaptrNo, $verseNo));
	}

	protected static function getCombi($bookId, $chapter, $verse) {
		return (int) sprintf('%03d%03d%03d', $bookId, $chapter, $verse);
	}

	public function bible() {
		return $this->belongsTo(Bible::class);
	}


}
