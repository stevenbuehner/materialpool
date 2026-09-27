<?php

namespace App\Services\ContextSearch\Extraction;

use InvalidArgumentException;

final readonly class OcrQualityGate
{
    public function __construct(
        private float $minimumMeanConfidence,
        private int $minimumRecognizedWords,
        private float $minimumAlphanumericRatio,
        private float $maximumReplacementCharacterRatio,
    ) {
        if ($minimumMeanConfidence < 0 || $minimumMeanConfidence > 1
            || $minimumRecognizedWords < 0
            || $minimumAlphanumericRatio < 0 || $minimumAlphanumericRatio > 1
            || $maximumReplacementCharacterRatio < 0 || $maximumReplacementCharacterRatio > 1) {
            throw new InvalidArgumentException('OCR quality thresholds are outside their supported ranges.');
        }
    }

    /** @param array<string, float|int> $metrics @return array{accepted: bool, reasons: list<string>} */
    public function assess(array $metrics): array
    {
        $reasons = [];

        if (($metrics['recognized_word_count'] ?? 0) < $this->minimumRecognizedWords) {
            $reasons[] = 'too_few_words';
        }
        if (($metrics['mean_confidence'] ?? 0.0) < $this->minimumMeanConfidence) {
            $reasons[] = 'low_mean_confidence';
        }
        if (($metrics['alphanumeric_ratio'] ?? 0.0) < $this->minimumAlphanumericRatio) {
            $reasons[] = 'low_alphanumeric_ratio';
        }
        if (($metrics['replacement_character_ratio'] ?? 1.0) > $this->maximumReplacementCharacterRatio) {
            $reasons[] = 'replacement_characters';
        }

        return ['accepted' => $reasons === [], 'reasons' => $reasons];
    }
}
