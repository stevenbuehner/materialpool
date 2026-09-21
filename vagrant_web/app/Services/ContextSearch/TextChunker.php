<?php

namespace App\Services\ContextSearch;

use InvalidArgumentException;

final class TextChunker
{
    public function __construct(
        private readonly int $targetCharacters = 1400,
        private readonly int $overlapCharacters = 200,
    ) {
        if ($this->targetCharacters < 1) {
            throw new InvalidArgumentException('The chunk target must be positive.');
        }

        if ($this->overlapCharacters < 0 || $this->overlapCharacters >= $this->targetCharacters) {
            throw new InvalidArgumentException('The overlap must be smaller than the chunk target.');
        }
    }

    /**
     * @return array<int, TextChunk>
     */
    public function chunk(string $text): array
    {
        if (blank($text)) {
            return [];
        }

        $units = $this->splitIntoUnits($text);
        $chunks = [];
        $cursor = 0;
        $carry = [];

        while ($cursor < count($units)) {
            $chunkUnits = $carry;
            $carriedUnits = count($carry);

            while ($cursor < count($units)) {
                $candidate = $units[$cursor];

                while ($carriedUnits > 0 && $this->rangeLength([...$chunkUnits, $candidate]) > $this->targetCharacters) {
                    array_shift($chunkUnits);
                    $carriedUnits--;
                }

                if ($this->rangeLength([...$chunkUnits, $candidate]) > $this->targetCharacters) {
                    break;
                }

                $chunkUnits[] = $candidate;
                $cursor++;
            }

            $start = $chunkUnits[0]['start'];
            $end = $chunkUnits[array_key_last($chunkUnits)]['end'];

            $chunks[] = new TextChunk(
                ordinal: count($chunks),
                content: mb_substr($text, $start, $end - $start),
                startCharacter: $start,
                endCharacter: $end,
            );

            $carry = $this->overlapUnits($chunkUnits);
        }

        return $chunks;
    }

    /**
     * @return array<int, array{start: int, end: int}>
     */
    private function splitIntoUnits(string $text): array
    {
        preg_match_all('/\S(?:.*?\S)?(?=\s{2,}|\z)/us', $text, $paragraphs, PREG_OFFSET_CAPTURE);

        $units = [];

        foreach ($paragraphs[0] as [$paragraph, $byteOffset]) {
            $start = mb_strlen(substr($text, 0, $byteOffset));

            array_push($units, ...$this->splitLongParagraph($paragraph, $start));
        }

        return $units;
    }

    /**
     * @return array<int, array{start: int, end: int}>
     */
    private function splitLongParagraph(string $paragraph, int $start): array
    {
        if (mb_strlen($paragraph) <= $this->targetCharacters) {
            return [['start' => $start, 'end' => $start + mb_strlen($paragraph)]];
        }

        preg_match_all('/\S.*?(?:[.!?]+(?=\s|\z)|\z)/us', $paragraph, $sentences, PREG_OFFSET_CAPTURE);

        $units = [];

        foreach ($sentences[0] as [$sentence, $byteOffset]) {
            $sentenceStart = $start + mb_strlen(substr($paragraph, 0, $byteOffset));

            array_push($units, ...$this->splitLongSentence($sentence, $sentenceStart));
        }

        return $units;
    }

    /**
     * @return array<int, array{start: int, end: int}>
     */
    private function splitLongSentence(string $sentence, int $start): array
    {
        if (mb_strlen($sentence) <= $this->targetCharacters) {
            return [['start' => $start, 'end' => $start + mb_strlen($sentence)]];
        }

        preg_match_all('/\S+(?:\s+|\z)/u', $sentence, $words, PREG_OFFSET_CAPTURE);

        $units = [];
        $currentStart = $start;
        $currentLength = 0;

        foreach ($words[0] as [$word, $byteOffset]) {
            $wordStart = $start + mb_strlen(substr($sentence, 0, $byteOffset));
            $wordLength = mb_strlen($word);

            if ($currentLength > 0 && $currentLength + $wordLength > $this->targetCharacters) {
                $units[] = ['start' => $currentStart, 'end' => $wordStart];
                $currentStart = $wordStart;
                $currentLength = 0;
            }

            while ($wordLength > $this->targetCharacters) {
                $units[] = ['start' => $wordStart, 'end' => $wordStart + $this->targetCharacters];
                $wordStart += $this->targetCharacters;
                $wordLength -= $this->targetCharacters;
                $currentStart = $wordStart;
            }

            $currentLength += $wordLength;
        }

        if ($currentLength > 0) {
            $units[] = ['start' => $currentStart, 'end' => $currentStart + $currentLength];
        }

        return $units;
    }

    /**
     * @param  array<int, array{start: int, end: int}>  $units
     */
    private function rangeLength(array $units): int
    {
        return $units[array_key_last($units)]['end'] - $units[0]['start'];
    }

    /**
     * @param  array<int, array{start: int, end: int}>  $units
     * @return array<int, array{start: int, end: int}>
     */
    private function overlapUnits(array $units): array
    {
        if ($this->overlapCharacters === 0) {
            return [];
        }

        $overlap = [];
        $length = 0;

        foreach (array_reverse($units) as $unit) {
            $overlap[] = $unit;
            $length += $unit['end'] - $unit['start'];

            if ($length >= $this->overlapCharacters) {
                break;
            }
        }

        return array_reverse($overlap);
    }
}
