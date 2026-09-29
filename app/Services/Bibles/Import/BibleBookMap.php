<?php

namespace App\Services\Bibles\Import;

use RuntimeException;

final class BibleBookMap
{
    private const NAMES = [
        'Genesis', 'Exodus', 'Leviticus', 'Numbers', 'Deuteronomy', 'Joshua', 'Judges', 'Ruth',
        '1 Samuel', '2 Samuel', '1 Kings', '2 Kings', '1 Chronicles', '2 Chronicles', 'Ezra',
        'Nehemiah', 'Esther', 'Job', 'Psalms', 'Proverbs', 'Ecclesiastes', 'Song of Solomon',
        'Isaiah', 'Jeremiah', 'Lamentations', 'Ezekiel', 'Daniel', 'Hosea', 'Joel', 'Amos',
        'Obadiah', 'Jonah', 'Micah', 'Nahum', 'Habakkuk', 'Zephaniah', 'Haggai', 'Zechariah',
        'Malachi', 'Matthew', 'Mark', 'Luke', 'John', 'Acts', 'Romans', '1 Corinthians',
        '2 Corinthians', 'Galatians', 'Ephesians', 'Philippians', 'Colossians', '1 Thessalonians',
        '2 Thessalonians', '1 Timothy', '2 Timothy', 'Titus', 'Philemon', 'Hebrews', 'James',
        '1 Peter', '2 Peter', '1 John', '2 John', '3 John', 'Jude', 'Revelation',
    ];

    private const OPENBIBLE = [
        'Gen', 'Exod', 'Lev', 'Num', 'Deut', 'Josh', 'Judg', 'Ruth', '1Sam', '2Sam',
        '1Kgs', '2Kgs', '1Chr', '2Chr', 'Ezra', 'Neh', 'Esth', 'Job', 'Ps', 'Prov',
        'Eccl', 'Song', 'Isa', 'Jer', 'Lam', 'Ezek', 'Dan', 'Hos', 'Joel', 'Amos',
        'Obad', 'Jonah', 'Mic', 'Nah', 'Hab', 'Zeph', 'Hag', 'Zech', 'Mal', 'Matt',
        'Mark', 'Luke', 'John', 'Acts', 'Rom', '1Cor', '2Cor', 'Gal', 'Eph', 'Phil',
        'Col', '1Thess', '2Thess', '1Tim', '2Tim', 'Titus', 'Phlm', 'Heb', 'Jas',
        '1Pet', '2Pet', '1John', '2John', '3John', 'Jude', 'Rev',
    ];

    public static function scrollmapper(string $name): int
    {
        $aliases = [
            'Psalm' => 'Psalms', 'Song of Songs' => 'Song of Solomon',
            'Revelation of John' => 'Revelation',
        ];
        $name = $aliases[$name] ?? $name;
        $name = preg_replace_callback('/^(III|II) /', fn (array $match): string => strlen($match[1]) . ' ', $name);
        $name = preg_replace('/^I /', '1 ', $name);
        $index = array_search($name, self::NAMES, true);
        if ($index === false) {
            throw new RuntimeException("Unbekanntes Bibelbuch: {$name}");
        }

        return $index + 1;
    }

    public static function openBible(string $name): int
    {
        $index = array_search($name, self::OPENBIBLE, true);
        if ($index === false) {
            throw new RuntimeException("Unbekanntes OpenBible-Buch: {$name}");
        }

        return $index + 1;
    }

    public static function reference(int $book, int $chapter, int $verse): int
    {
        if ($book < 1 || $book > 66 || $chapter < 1 || $chapter > 999 || $verse < 1 || $verse > 999) {
            throw new RuntimeException('Ungültige Bibelstelle in den Quelldaten.');
        }

        return $book * 1000000 + $chapter * 1000 + $verse;
    }
}
