<?php

namespace App\Models;

use App\Models\Exceptions\InvalidParameterCombinationException;
use App\Models\Exceptions\MultipleBooksExceptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use StevenBuehner\BibleVerseBundle\Exceptions\InvalidBibleVerseRangeException;
use StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;

/**
 * Class Bibleverse
 *
 * @property int $id
 * @property int $from
 * @property int $to
 * @property int|null $bible_id
 * @property string $label
 * @property int $from_book_id
 * @property int $from_chapter
 * @property int $from_verse
 * @property int $to_book_id
 * @property int $to_chapter
 * @property int $to_verse
 * @property int $icon
 */
class Bibleverse extends Model implements BibleVerseInterface {
	use HasFactory;

	protected static $fromColumn        = 'from';
	protected static $toColumn          = 'to';
	protected        $casts             = ['from' => 'integer', 'to' => 'integer'];
	protected        $fillable          = ['from',
	                                       'to',
	                                       'bible_id',
	                                       'book_id',
	                                       'from_book_id',
	                                       'from_chapter',
	                                       'from_verse',
	                                       'to_book_id',
	                                       'to_chapter',
	                                       'to_verse',
	                                       'label'];
	protected        $bibleVerseService = NULL;

	// Default values
	protected $attributes = [
		'bible_id' => NULL,
		'from'     => 0,
		'to'       => 0
	];

	protected $hidden = [
		'created_at', 'updated_at'
	];

	protected $appends = [
		'from_book_id',
		'from_chapter',
		'from_verse',
		'to_book_id',
		'to_chapter',
		'to_verse',
		'label',
		'icon'
	];

	public static function findOrCreateFromBibleverseInterface(BibleVerseInterface $bibleVerse, $bibleId = NULL) {
		return self::firstOrCreate(self::getBibleverseCreateData($bibleVerse, $bibleId));
	}

	protected static function getBibleverseCreateData(BibleVerseInterface $bibleVerse, $bibleId = NULL) {

		list($from, $to) = self::getFromToCombi($bibleVerse);

		return [
			self::$fromColumn => $from,
			self::$toColumn   => $to,
			'bible_id'        => $bibleId
		];
	}

	public static function getFromToCombi(BibleVerseInterface $bibleVerse) {
		$from = self::getCombi($bibleVerse->getBookId(), $bibleVerse->getFromChapter(), $bibleVerse->getFromVerse());
		$to   = self::getCombi($bibleVerse->getBookId(), $bibleVerse->getToChapter(), $bibleVerse->getToVerse());

		return [$from, $to];
	}

	public static function getCombi($bookId, $chapter, $verse) {
		return (int)sprintf('%03d%03d%03d', $bookId, $chapter, $verse);
	}

	/**
	 * Get bookId
	 *
	 * @return int
	 * @throws MultipleBooksExceptions
	 */
	public function getBookId() {
		$from = $this->getFromBookId();
		$to   = $this->getToBookId();

		if ($from === $to) {
			return $from;
		} else {
			throw new MultipleBooksExceptions("Using different books in from ({$from}) and to ({$to})");
		}
	}

	public function getFromBookId() {
		return self::getBookFromCombi($this->getAttribute(self::$fromColumn));
	}

	/**
	 * @param int $chapterVerseNum
	 * @return int
	 */
	protected static function getBookFromCombi($chapterVerseNum) {
		return (int)floor($chapterVerseNum / 1000000);
	}

	public function getToBookId() {
		return self::getBookFromCombi($this->getAttribute(self::$toColumn));
	}

	/**
	 * Get fromChapter
	 *
	 * @return int
	 */
	public function getFromChapter() {
		return (self::getChapterFromCombi($this->getAttribute(self::$fromColumn)));
	}

	/**
	 * @param int $chapterVerseNum
	 * @return int
	 */
	protected static function getChapterFromCombi($chapterVerseNum) {
		// cut off bookId and then cut of verses
		return (int)floor(($chapterVerseNum % 1000000) / 1000);
	}

	/**
	 * Get fromVerse
	 *
	 * @return int
	 */
	public function getFromVerse() {
		return (self::getVerseFromCombi($this->getAttribute(self::$fromColumn)));
	}

	/**
	 * @param int $chapterVerseNum
	 * @return int
	 */
	protected static function getVerseFromCombi($chapterVerseNum) {
		return (int)($chapterVerseNum % 1000);
	}

	/**
	 * Get toChapter
	 *
	 * @return int
	 */
	public function getToChapter() {
		return (self::getChapterFromCombi($this->getAttribute(self::$toColumn)));
	}

	/**
	 * Get toVerse
	 *
	 * @return int
	 */
	public function getToVerse() {
		return (self::getVerseFromCombi($this->getAttribute(self::$toColumn)));
	}

	public static function makeFromBibleverseInterface(BibleVerseInterface $bibleVerse, $bibleId = NULL) {
		return new self(self::getBibleverseCreateData($bibleVerse, $bibleId));
	}

	/**
	 * @param BibleVerseInterface $bibleVerse
	 * @param null $bibleId
	 * @return NULL|Bibleverse
	 */
	public static function findOrNewFromBibleverseInterface(BibleVerseInterface $bibleVerse, $bibleId = NULL) {
		return self::firstOrNew(self::getBibleverseCreateData($bibleVerse, $bibleId));
	}

	/**
	 * @param Collection $coll of BibleVerseInterface
	 * @return Builder
	 */
	public static function findWhereInRange(Collection $coll) {
		$query = self::query();

		$bibleverses = $coll->filter(function ($el) {
			return $el instanceof BibleVerseInterface;
		});

		foreach ($bibleverses as $bv) {
			/** @var BibleVerseInterface $bv */
			/** @var Builder $query */
			list($from, $to) = self::getFromToCombi($bv);
			$query->orWhereBetween('from', [$from, $to]);
			$query->orWhereBetween('to', [$from, $to]);
			$query->orWhere(function ($q) use ($from, $to) {
				$q->where('from', '>', $from);
				$q->where('to', '<', $to);
			});
		}

		return $query;
	}

	public function toArray() {
		$attributes = $this->attributesToArray();
		$attributes = array_merge($attributes, $this->relationsToArray());

		if (isset($attributes['pivot']['material_id'])) {
			unset($attributes['pivot']['material_id']);
		}

		if (isset($attributes['pivot']['bibleverse_id'])) {
			unset($attributes['pivot']['bibleverse_id']);
		}

		return $attributes;
	}

	public function getLabelAttribute() {
		return $this->getBibleVerseString();
	}

	public function getBibleVerseString($length = 'short', $lang = 'de') {
		try {
			$result = $this->getBibleVerseService()->bibleVerseToString($this, $length, $lang);
		} catch (InvalidBibleVerseRangeException $e) {
			$result = 'Invalid Bibleverse';
		}

		return $result;
	}

	/**
	 * @return BibleVerseService
	 */
	protected function getBibleVerseService() {
		if (!$this->bibleVerseService) {
			$this->bibleVerseService = resolve('BibleVerseService');
		}

		return $this->bibleVerseService;
	}

	public function getFromBookIdAttribute() {
		return $this->getFromBookId();
	}

	public function setFromBookIdAttribute(int $fromBookId) {
		$this->setFromBookId($fromBookId);
	}

	public function setFromBookId($fromBookId) {
		$this->setFromCombined($fromBookId, $this->getFromChapter(), $this->getFromVerse());
	}

	protected function setFromCombined($bookId, $chapter, $verse) {
		$this->setAttribute(self::$fromColumn, self::getCombi($bookId, $chapter, $verse));
	}

	public function getFromChapterAttribute() {
		return $this->getFromChapter();
	}

	public function setFromChapterAttribute(int $fromChapter) {
		return $this->setFromChapter($fromChapter);
	}

	/**
	 * Set fromChapter
	 *
	 * @param integer $fromChapter
	 */
	public function setFromChapter($fromChapter) {
		$this->setFromCombined($this->getFromBookId(), $fromChapter, $this->getFromVerse());
	}

	public function getFromVerseAttribute() {
		return $this->getFromVerse();
	}

	public function setFromVerseAttribute(int $fromVerse) {
		return $this->setFromVerse($fromVerse);
	}

	/**
	 * Set fromVerse
	 *
	 * @param integer $fromVerse
	 */
	public function setFromVerse($fromVerse) {
		$this->setFromCombined($this->getFromBookId(), $this->getFromChapter(), $fromVerse);
	}

	public function getToBookIdAttribute() {
		return $this->getToBookId();
	}

	public function setToBookIdAttribute(int $toBookId) {
		$this->setToBookId($toBookId);
	}

	public function setToBookId($toBookId) {
		$this->setToCombined($toBookId, $this->getFromChapter(), $this->getFromVerse());

	}

	/**
	 * @param int $bookId
	 * @param int $chapter
	 * @param int $verse
	 */
	public function setToCombined($bookId, $chapter, $verse) {
		$this->setAttribute(self::$toColumn, self::getCombi($bookId, $chapter, $verse));
	}

	public function getToChapterAttribute() {
		return $this->getToChapter();
	}

	public function setToChapterAttribute(int $toChapter) {
		$this->setToChapter($toChapter);
	}

	/**
	 * Set toChapter
	 *
	 * @param integer $toChapter
	 */
	public function setToChapter($toChapter) {
		$this->setToCombined($this->getToBookId(), $toChapter, $this->getToVerse());
	}

	public function getToVerseAttribute() {
		return $this->getToVerse();
	}

	public function setToVerseAttribute(int $toVerse) {
		return $this->setToVerse($toVerse);
	}

	/**
	 * Set toVerse
	 *
	 * @param integer $toVerse
	 */
	public function setToVerse($toVerse) {
		$this->setToCombined($this->getToBookId(), $this->getToChapter(), $toVerse);
	}

	public function setBookIdAttribute($bookdId) {
		$this->setBookId($bookdId);
	}

	/**
	 * Set bookId
	 *
	 * @param integer $bookId
	 */
	public function setBookId($bookId) {
		$this->setFromBookId($bookId);
		$this->setToBookId($bookId);
	}

	/**
	 * @param int $bookId
	 * @param int $fromChapter
	 * @param int $fromVerse
	 * @param int|NULL $toChapter
	 * @param int|NULL $toVerse
	 *
	 * @return Bibleverse
	 * @throws InvalidParameterCombinationException
	 */
	public function setVerse($bookId, $fromChapter, $fromVerse, $toChapter = NULL, $toVerse = NULL) {
		$this->setFromCombined($bookId, $fromChapter, $fromVerse);

		if (empty($toChapter) xor empty($toVerse)) {
			throw new InvalidParameterCombinationException('You need to specify both, toChapter and fromChapter, if you want to specify a to at all.');
		}

		if (empty($toChapter)) {
			$toChapter = $fromChapter;
			$toVerse   = $fromVerse;
		}

		$this->setToCombined($bookId, $toChapter, $toVerse);

		return $this;
	}

	public function getIconAttribute() {
		return '/img/icons/bible.svg';
	}

	/*
	 *
	 * RELATIONS
	 *
	 */

	public function materials() {
		return $this->belongsToMany(Material::class)
			->withPivot('relevance')
			->using(MaterialBibleverse::class);
	}

	public function bibleverseCrossReferencesQuery() {
		return BibleverseCrossReference::getCrossReferencesByBibleverseQuery($this);
	}

	public function __toString() {
		return "Model\Bibleverse: id={$this->getAttribute('id')}, from={$this->getAttribute('from')}, to={$this->getAttribute('to')}";
	}
}
