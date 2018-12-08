<?php

namespace App\Services\Bibles\Import;

use App\Models\Bible;
use App\Models\BibleContent;
use App\Models\Bibleverse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class ZefaniaImportService {

	public function __construct() {
	}

	public function fileAlreadyInstalled($xmlFilePath) {

	}

	public function update($xmlFilePath) {

	}

	public function import($xmlFilePath) {

		/** @var \SimpleXMLElement $xml */
		$xml          = simplexml_load_file($xmlFilePath);
		$title        = trim((string) $xml->INFORMATION->title);
		$uid          = trim((string) $xml->INFORMATION->identifier);
		$description  = trim((string) $xml->INFORMATION->description);
		$version_date = trim((string) $xml->INFORMATION->date);
		$creator      = join(', ', $xml->INFORMATION->xpath('//creator'));
		$language     = trim((string) $xml->INFORMATION->language);
		$rights       = trim((string) $xml->INFORMATION->rights);
		$source       = trim((string) $xml->INFORMATION->source);
		$version      = trim((string) $xml['version']);


		/** @var Bible $bible */
		$bible = Bible::updateOrCreate(
			['uuid' => $uid],
			compact('title', 'description', 'version_date', 'creator', 'language', 'rights', 'source')
		);


		foreach ($xml->BIBLEBOOK as $book) {
			$bookId = (int) $book['bnumber'];

			$this->deleteAllFromBook($bible->id, $bookId);

			foreach ($book->CHAPTER as $chapter) {
				$chapterNo = (int) $chapter['cnumber'];

				if ($chapterNo === 13 && $bookId === 40) {
					$t = "...";
				}

				foreach ($chapter->VERS as $verse) {
					$verseNo = (int) $verse['vnumber'];
					$text    = (string) $verse;

					$content           = new BibleContent();
					$content->bible_id = $bible->id;
					$content->setVerse($bookId, $chapterNo, $verseNo);
					$content->text = trim($text);

					try {
						$content->save();
					} catch (QueryException $e) {
						// Einige Bibelübersetungen bieten mehrere Varianten des selben Verses an (Z.B. die Volxbibel) - ich nehme nur die erste Variante
						Log::error('Fehler beim importieren eines BibelversInhaltes ... vermutlich Dopplung', [
							'Bibel' => $bible->title,
							'trace' => $e->getTraceAsString(),
							'vers'  => $content->getAttributes()
						]);
					}
				}
			}
		}

		$bible->touch();

	}

	protected function deleteAllFromBook($bible_id, $bookId) {

		$bv = new Bibleverse();
		$bv->setBookId($bookId);

		$from = $bv->from;

		$bv->setFromChapter(999);
		$to = $bv->from;

		BibleContent::where('bible_id', $bible_id)
					->whereBetween('verse', [$from, $to])
					->delete();

		/*
		DB::table($bv->getTable())
		  ->where('bible_id', $bible_id)
		  ->whereBetween('verse', [$from, $to])
		  ->delete();
		*/

	}

	protected function translateBookId($zefaniaBookId) {
		return (int) $zefaniaBookId;

		/* ZEFANIABIBLE_BIBLE_BOOK_NAME_1  = "Genesis"
ZEFANIABIBLE_BIBLE_BOOK_NAME_2 = "Exodus"
ZEFANIABIBLE_BIBLE_BOOK_NAME_3 = "Leviticus"
ZEFANIABIBLE_BIBLE_BOOK_NAME_4 = "Numbers"
ZEFANIABIBLE_BIBLE_BOOK_NAME_5 = "Deuteronomy"
ZEFANIABIBLE_BIBLE_BOOK_NAME_6 = "Joshua"
ZEFANIABIBLE_BIBLE_BOOK_NAME_7 = "Judges"
ZEFANIABIBLE_BIBLE_BOOK_NAME_8 = "Ruth"
ZEFANIABIBLE_BIBLE_BOOK_NAME_9 = "1 Samuel"
ZEFANIABIBLE_BIBLE_BOOK_NAME_10 = "2 Samuel"
ZEFANIABIBLE_BIBLE_BOOK_NAME_11 = "1 Kings"
ZEFANIABIBLE_BIBLE_BOOK_NAME_12 = "2 Kings"
ZEFANIABIBLE_BIBLE_BOOK_NAME_13 = "1 Chronicles"
ZEFANIABIBLE_BIBLE_BOOK_NAME_14 = "2 Chronicles"
ZEFANIABIBLE_BIBLE_BOOK_NAME_15 = "Ezra"
ZEFANIABIBLE_BIBLE_BOOK_NAME_16 = "Nehemiah"
ZEFANIABIBLE_BIBLE_BOOK_NAME_17 = "Esther"
ZEFANIABIBLE_BIBLE_BOOK_NAME_18 = "Job"
ZEFANIABIBLE_BIBLE_BOOK_NAME_19 = "Psalm"
ZEFANIABIBLE_BIBLE_BOOK_NAME_20 = "Proverbs"
ZEFANIABIBLE_BIBLE_BOOK_NAME_21 = "Ecclesiastes"
ZEFANIABIBLE_BIBLE_BOOK_NAME_22 = "Song of Songs"
ZEFANIABIBLE_BIBLE_BOOK_NAME_23 = "Isaiah"
ZEFANIABIBLE_BIBLE_BOOK_NAME_24 = "Jeremiah"
ZEFANIABIBLE_BIBLE_BOOK_NAME_25 = "Lamentations"
ZEFANIABIBLE_BIBLE_BOOK_NAME_26 = "Ezekiel"
ZEFANIABIBLE_BIBLE_BOOK_NAME_27 = "Daniel"
ZEFANIABIBLE_BIBLE_BOOK_NAME_28 = "Hosea"
ZEFANIABIBLE_BIBLE_BOOK_NAME_29 = "Joel"
ZEFANIABIBLE_BIBLE_BOOK_NAME_30 = "Amos"
ZEFANIABIBLE_BIBLE_BOOK_NAME_31 = "Obadiah"
ZEFANIABIBLE_BIBLE_BOOK_NAME_32 = "Jonah"
ZEFANIABIBLE_BIBLE_BOOK_NAME_33 = "Micah"
ZEFANIABIBLE_BIBLE_BOOK_NAME_34 = "Nahum"
ZEFANIABIBLE_BIBLE_BOOK_NAME_35 = "Habakkuk"
ZEFANIABIBLE_BIBLE_BOOK_NAME_36 = "Zephaniah"
ZEFANIABIBLE_BIBLE_BOOK_NAME_37 = "Haggai"
ZEFANIABIBLE_BIBLE_BOOK_NAME_38 = "Zechariah"
ZEFANIABIBLE_BIBLE_BOOK_NAME_39 = "Malachi"
ZEFANIABIBLE_BIBLE_BOOK_NAME_40 = "Matthew"
ZEFANIABIBLE_BIBLE_BOOK_NAME_41 = "Mark"
ZEFANIABIBLE_BIBLE_BOOK_NAME_42 = "Luke"
ZEFANIABIBLE_BIBLE_BOOK_NAME_43 = "John"
ZEFANIABIBLE_BIBLE_BOOK_NAME_44 = "Acts"
ZEFANIABIBLE_BIBLE_BOOK_NAME_45 = "Romans"
ZEFANIABIBLE_BIBLE_BOOK_NAME_46 = "1 Corinthians"
ZEFANIABIBLE_BIBLE_BOOK_NAME_47 = "2 Corinthians"
ZEFANIABIBLE_BIBLE_BOOK_NAME_48 = "Galatians"
ZEFANIABIBLE_BIBLE_BOOK_NAME_49 = "Ephesians"
ZEFANIABIBLE_BIBLE_BOOK_NAME_50 = "Philippians"
ZEFANIABIBLE_BIBLE_BOOK_NAME_51 = "Colossians"
ZEFANIABIBLE_BIBLE_BOOK_NAME_52 = "1 Thessalonians"
ZEFANIABIBLE_BIBLE_BOOK_NAME_53 = "2 Thessalonians"
ZEFANIABIBLE_BIBLE_BOOK_NAME_54 = "1 Timothy"
ZEFANIABIBLE_BIBLE_BOOK_NAME_55 = "2 Timothy"
ZEFANIABIBLE_BIBLE_BOOK_NAME_56 = "Titus"
ZEFANIABIBLE_BIBLE_BOOK_NAME_57 = "Philemon"
ZEFANIABIBLE_BIBLE_BOOK_NAME_58 = "Hebrews"
ZEFANIABIBLE_BIBLE_BOOK_NAME_59 = "James"
ZEFANIABIBLE_BIBLE_BOOK_NAME_60 = "1 Peter"
ZEFANIABIBLE_BIBLE_BOOK_NAME_61 = "2 Peter"
ZEFANIABIBLE_BIBLE_BOOK_NAME_62 = "1 John"
ZEFANIABIBLE_BIBLE_BOOK_NAME_63 = "2 John"
ZEFANIABIBLE_BIBLE_BOOK_NAME_64 = "3 John"
ZEFANIABIBLE_BIBLE_BOOK_NAME_65 = "Jude"
ZEFANIABIBLE_BIBLE_BOOK_NAME_66 = "Revelation"
		*/

	}


}