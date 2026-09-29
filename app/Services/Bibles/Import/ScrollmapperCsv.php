<?php

namespace App\Services\Bibles\Import;

use RuntimeException;

final class ScrollmapperCsv
{
    public function normalize(string $source, string $target): array
    {
        $input = fopen($source, 'rb');
        if ($input === false) {
            throw new RuntimeException('Bibel-CSV konnte nicht geöffnet werden.');
        }
        try {
            $header = fgetcsv($input, 0, ',', '"', '');
            if ($header !== ['Book', 'Chapter', 'Verse', 'Text']) {
                throw new RuntimeException('Unbekanntes Scrollmapper-CSV-Format.');
            }
            $rows = [];
            $books = [];
            $placeholders = 0;
            $seen = [];
            while (($row = fgetcsv($input, 0, ',', '"', '')) !== false) {
                if (count($row) !== 4 || ! preg_match('/^\d+$/', $row[1]) || ! preg_match('/^\d+$/', $row[2])
                    || ! mb_check_encoding($row[3], 'UTF-8') || strlen(trim($row[3])) > 65530) {
                    throw new RuntimeException('Ungültige Scrollmapper-Verszeile.');
                }
                if (trim($row[3]) === '') {
                    $placeholders++;
                    continue;
                }
                $book = BibleBookMap::scrollmapper($row[0]);
                $reference = BibleBookMap::reference($book, (int) $row[1], (int) $row[2]);
                if (isset($seen[$reference])) {
                    throw new RuntimeException('Doppelte Scrollmapper-Bibelstelle.');
                }
                $seen[$reference] = true;
                $rows[$reference] = trim($row[3]);
                $books[$book] = true;
            }
            if ($rows === []) {
                throw new RuntimeException('Scrollmapper-Ausgabe enthält keine Verse.');
            }
            ksort($rows, SORT_NUMERIC);
            $output = fopen($target, 'wb');
            if ($output === false) {
                throw new RuntimeException('Temporäre Bibeldatei konnte nicht angelegt werden.');
            }
            try {
                foreach ($rows as $reference => $text) {
                    fwrite($output, json_encode([(int) $reference, $text], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
                }
            } finally {
                fclose($output);
            }

            return [
                'count' => count($rows), 'books' => count($books), 'placeholders' => $placeholders,
                'scope' => count($books) === 66 ? 'Vollbibel' : 'Teilbestand',
                'hash' => hash_file('sha256', $target),
            ];
        } finally {
            fclose($input);
        }
    }

    public function rows(string $path): \Generator
    {
        $input = fopen($path, 'rb');
        if ($input === false) {
            throw new RuntimeException('Temporäre Bibeldatei fehlt.');
        }
        try {
            while (($line = fgets($input)) !== false) {
                $row = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                yield ['verse' => $row[0], 'text' => $row[1]];
            }
        } finally {
            fclose($input);
        }
    }
}
