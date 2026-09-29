<?php

namespace Tests\Feature;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchOcrCalibrationPage;
use App\Models\ContextSearchOcrCalibrationRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContextSearchOcrCalibrationEvaluateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_evaluates_a_completed_run_without_changing_ocr_text(): void
    {
        $run = $this->makeRun();
        foreach (range(1, 11) as $number) {
            $usable = $number <= 7 || $number === 11;
            $run->pages()->create([
                'resource_id' => $number,
                'source_revision_hash' => str_repeat('a', 64),
                'page_number' => 1,
                'split' => $number === 11 ? ContextSearchOcrCalibrationPage::SPLIT_HOLDOUT : ContextSearchOcrCalibrationPage::SPLIT_CALIBRATION,
                'status' => ContextSearchOcrCalibrationPage::STATUS_PROCESSED,
                'quality_label' => $usable ? 'usable' : 'unusable',
                'reference_text' => $usable ? 'Test' : null,
                'ocr_text' => 'Test',
                'metrics' => [
                    'mean_confidence' => 0.9,
                    'recognized_word_count' => 1,
                    'alphanumeric_ratio' => 1.0,
                    'replacement_character_ratio' => 0.0,
                ],
            ]);
        }

        $this->artisan('context-search:ocr-calibration:evaluate', ['run' => $run->getKey()])
            ->expectsOutputToContain('wurden ausgewertet')
            ->assertExitCode(0);

        $this->assertSame(ContextSearchOcrCalibrationRun::STATUS_EVALUATED, $run->fresh()->status);
        $this->assertNull($run->fresh()->sweep_results['recommended_threshold']);
        $this->assertSame(['Test'], $run->pages()->distinct()->pluck('ocr_text')->all());
    }

    public function test_command_rejects_missing_and_approved_runs(): void
    {
        $this->artisan('context-search:ocr-calibration:evaluate', ['run' => 'missing'])
            ->assertExitCode(1);

        $run = $this->makeRun();
        $run->update(['status' => ContextSearchOcrCalibrationRun::STATUS_APPROVED]);

        $this->artisan('context-search:ocr-calibration:evaluate', ['run' => $run->getKey()])
            ->assertExitCode(1);
        $this->assertNull($run->fresh()->sweep_results);
    }

    private function makeRun(): ContextSearchOcrCalibrationRun
    {
        $user = User::factory()->create();
        $dataset = ContextSearchEvaluationDataset::query()->create([
            'purpose' => 'ocr',
            'status' => ContextSearchEvaluationDataset::STATUS_FROZEN,
            'manifest' => [],
            'manifest_hash' => str_repeat('b', 64),
        ]);

        return ContextSearchOcrCalibrationRun::query()->create([
            'dataset_id' => $dataset->getKey(),
            'created_by' => $user->getKey(),
            'status' => ContextSearchOcrCalibrationRun::STATUS_REVIEWING,
            'random_seed' => 1,
            'sample_limit' => 11,
            'total_pages' => 11,
            'processed_pages' => 11,
            'reviewed_pages' => 11,
            'ocr_profile' => [],
        ]);
    }
}
