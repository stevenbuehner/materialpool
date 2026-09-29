<?php

namespace App\Services\Bibles\Import;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use ZipArchive;

final class OpenBibleData
{
    public const ID = 'openbible:cross-references';
    public const VERSION = 1;
    public const PAYLOAD = 'cross-references.tsv';
    public const MANIFEST = 'manifest.json';

    public function directory(): string
    {
        return config('bible_data.directory', database_path('bible-data'));
    }

    public function prepare(): array
    {
        $directory = $this->directory();
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Ausgabeverzeichnis konnte nicht angelegt werden.');
        }
        $zipPath = tempnam(sys_get_temp_dir(), 'openbible-');
        $payloadPath = $directory.'/'.self::PAYLOAD;
        try {
            $response = Http::withOptions(['allow_redirects' => false])->timeout(60)->connectTimeout(10)->retry(2, 500)
                ->sink($zipPath)->get(config('bible_data.openbible_url'));
            if (! $response->successful() || filesize($zipPath) > config('bible_data.max_cross_reference_bytes')) {
                throw new RuntimeException('OpenBible-Download fehlgeschlagen oder zu groß.');
            }
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true || $zip->numFiles !== 1 || $zip->getNameIndex(0) !== 'cross_references.txt') {
                throw new RuntimeException('OpenBible-Archiv hat ein unbekanntes Format.');
            }
            if (($zip->statIndex(0)['size'] ?? PHP_INT_MAX) > 64 * 1024 * 1024) {
                throw new RuntimeException('OpenBible-Archiv ist entpackt zu groß.');
            }
            $input = $zip->getStream('cross_references.txt');
            if ($input === false) {
                throw new RuntimeException('OpenBible-Daten konnten nicht geöffnet werden.');
            }
            try {
                $header = fgets($input);
                if (! is_string($header) || ! preg_match('/^From Verse\tTo Verse\tVotes\t#www\.openbible\.info CC-BY (\d{4}-\d{2}-\d{2})\r?\n$/', $header, $match)) {
                    throw new RuntimeException('OpenBible-Kopfzeile ist unbekannt.');
                }
                $sourceDate = $match[1];
                $count = 0;
                $seen = [];
                $lines = [];
                while (($line = fgets($input)) !== false) {
                    $row = $this->parseSourceLine($line);
                    $key = $row[0].':'.$row[2].':'.$row[3];
                    if (isset($seen[$key])) {
                        throw new RuntimeException('Doppelte OpenBible-Referenz.');
                    }
                    $seen[$key] = true;
                    $lines[] = implode("\t", $row)."\n";
                    $count++;
                }
                if ($count < 100000) {
                    throw new RuntimeException('OpenBible-Datenbestand ist unerwartet klein.');
                }
                unset($seen);
                sort($lines, SORT_STRING);
                $output = fopen($payloadPath.'.partial', 'wb');
                if ($output === false) {
                    throw new RuntimeException('OpenBible-Ausgabedatei konnte nicht geöffnet werden.');
                }
                try {
                    foreach ($lines as $normalizedLine) {
                        fwrite($output, $normalizedLine);
                    }
                } finally {
                    fclose($output);
                }
            } finally {
                fclose($input);
                $zip->close();
            }
            rename($payloadPath.'.partial', $payloadPath);
            $manifest = [
                'schema' => self::VERSION,
                'dataset_id' => self::ID,
                'source_url' => config('bible_data.openbible_url'),
                'source_date' => $sourceDate,
                'rights' => 'OpenBible.info, CC BY; https://www.openbible.info/labs/cross-references/',
                'payload' => self::PAYLOAD,
                'sha256' => hash_file('sha256', $payloadPath),
                'row_count' => $count,
            ];
            file_put_contents($directory.'/'.self::MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

            return $manifest;
        } finally {
            @unlink($zipPath);
            @unlink($payloadPath.'.partial');
        }
    }

    public function validate(): array
    {
        $directory = $this->directory();
        $path = $directory.'/'.self::MANIFEST;
        $manifest = is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : null;
        if (! is_array($manifest) || $manifest['schema'] !== self::VERSION || $manifest['dataset_id'] !== self::ID
            || $manifest['payload'] !== self::PAYLOAD || $manifest['source_url'] !== config('bible_data.openbible_url')
            || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $manifest['source_date'] ?? '')
            || ! is_string($manifest['rights'] ?? null) || $manifest['rights'] === ''
            || ! is_int($manifest['row_count'] ?? null)
            || ! preg_match('/^[a-f0-9]{64}$/', $manifest['sha256'] ?? '')
            || ! is_file($directory.'/'.self::PAYLOAD)
            || ! hash_equals($manifest['sha256'], hash_file('sha256', $directory.'/'.self::PAYLOAD))) {
            throw new RuntimeException('Cross-Reference-Manifest oder Prüfsumme ungültig.');
        }
        $count = 0;
        $seen = [];
        foreach ($this->rows() as $row) {
            $key = $row[0].':'.$row[2].':'.$row[3];
            if (isset($seen[$key])) {
                throw new RuntimeException('Doppelte Cross Reference im Release.');
            }
            $seen[$key] = true;
            $count++;
        }
        if ($count !== $manifest['row_count'] || $count < 1) {
            throw new RuntimeException('Cross-Reference-Anzahl stimmt nicht.');
        }

        return $manifest;
    }

    public function rows(): \Generator
    {
        $handle = fopen($this->directory().'/'.self::PAYLOAD, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Cross-Reference-Payload fehlt.');
        }
        try {
            while (($line = fgets($handle)) !== false) {
                if (! preg_match('/^(\d+)\t(\d+)\t(\d+)\t(\d+)\n$/', $line, $match)) {
                    throw new RuntimeException('Ungültige Cross-Reference-Zeile.');
                }
                $row = array_map('intval', array_slice($match, 1));
                if ($row[0] < 1001001 || $row[0] > 660999999 || $row[2] < 1001001 || $row[3] > 660999999) {
                    throw new RuntimeException('Ungültige Cross-Reference-Bibelstelle.');
                }
                yield $row;
            }
        } finally {
            fclose($handle);
        }
    }

    private function parseSourceLine(string $line): array
    {
        $parts = explode("\t", rtrim($line, "\r\n"));
        if (count($parts) !== 3 || ! preg_match('/^-?\d+$/', $parts[2])) {
            throw new RuntimeException('Ungültige OpenBible-Zeile.');
        }
        $from = $this->reference($parts[0]);
        $target = explode('-', $parts[1]);
        if (count($target) > 2) {
            throw new RuntimeException('Ungültiger OpenBible-Versbereich.');
        }
        $targetFrom = $this->reference($target[0]);
        $targetTo = isset($target[1]) ? $this->reference($target[1]) : 0;
        if ($targetTo !== 0 && $targetTo < $targetFrom) {
            throw new RuntimeException('Rückwärts gerichteter OpenBible-Versbereich.');
        }

        return [$from, max(0, (int) $parts[2]), $targetFrom, $targetTo];
    }

    private function reference(string $value): int
    {
        if (! preg_match('/^([A-Za-z0-9]+)\.(\d+)\.(\d+)$/', $value, $match)) {
            throw new RuntimeException('Ungültige OpenBible-Bibelstelle.');
        }

        return BibleBookMap::reference(BibleBookMap::openBible($match[1]), (int) $match[2], (int) $match[3]);
    }
}
