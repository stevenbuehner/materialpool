<?php

namespace Tests\Unit\Services\ContextSearch\Extraction;

use App\Services\ContextSearch\Extraction\OcrQualityGate;
use InvalidArgumentException;
use Tests\TestCase;

final class OcrQualityGateTest extends TestCase
{
    public function test_accepts_a_page_meeting_each_configured_quality_threshold(): void
    {
        $gate = new OcrQualityGate(0.72, 5, 0.6, 0.01);

        $result = $gate->assess([
            'mean_confidence' => 0.8,
            'recognized_word_count' => 20,
            'alphanumeric_ratio' => 0.75,
            'replacement_character_ratio' => 0.0,
        ]);

        $this->assertSame(['accepted' => true, 'reasons' => []], $result);
    }

    public function test_rejects_pages_with_explained_quality_reasons(): void
    {
        $gate = new OcrQualityGate(0.7, 4, 0.5, 0.0);

        $result = $gate->assess([
            'mean_confidence' => 0.42,
            'recognized_word_count' => 2,
            'alphanumeric_ratio' => 0.2,
            'replacement_character_ratio' => 0.1,
        ]);

        $this->assertSame(['too_few_words', 'low_mean_confidence', 'low_alphanumeric_ratio', 'replacement_characters'], $result['reasons']);
        $this->assertFalse($result['accepted']);
    }

    public function test_rejects_out_of_range_thresholds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OcrQualityGate(1.1, 1, 0, 0);
    }
}
