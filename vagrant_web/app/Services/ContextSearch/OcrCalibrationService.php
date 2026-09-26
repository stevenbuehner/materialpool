<?php

namespace App\Services\ContextSearch;

use App\Jobs\ContextSearch\ProcessOcrCalibrationPage;
use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\ContextSearchOcrCalibrationPage;
use App\Models\ContextSearchOcrCalibrationRun;
use App\Models\PdfFile;
use App\Models\User;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\PdfHandlingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class OcrCalibrationService
{
    public function __construct(
        private readonly FileHandlingService $files,
        private readonly PdfHandlingService $pdfs,
        private readonly ContextSearchQueueSafety $queueSafety,
    ) {
    }

    public function start(string $datasetId, User $user, int $sampleLimit, ?string $title = null): ContextSearchOcrCalibrationRun
    {
        if (app()->isProduction()) {
            throw new RuntimeException('OCR calibration is available only in development and test environments.');
        }

        $this->queueSafety->assertOcrCalibrationDispatchAllowed();

        $dataset = ContextSearchEvaluationDataset::query()->whereKey($datasetId)->firstOrFail();
        if ($dataset->purpose !== 'ocr' || $dataset->status !== ContextSearchEvaluationDataset::STATUS_FROZEN) {
            throw new RuntimeException('Choose a frozen OCR evaluation dataset.');
        }

        $members = ContextSearchEvaluationDatasetMember::query()
            ->where('dataset_id', $dataset->getKey())
            ->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_RESOURCE)
            ->get(['member_id', 'source_revision_hash']);
        $seed = random_int(1, 2_000_000_000);
        $sampleQueue = new \SplPriorityQueue();
        $sampleQueue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
        foreach ($members as $member) {
            $pdf = (new PdfFile())->newQueryWithoutScopes()->find($member->member_id);
            if (! $pdf instanceof PdfFile) {
                continue;
            }
            $path = $this->files->getLocalFilePath($pdf);
            $revision = is_file($path)
                ? ($member->source_revision_hash ?: hash_file('sha256', $path))
                : false;
            if ($revision === false) {
                continue;
            }
            $pageCount = $this->pdfs->countPdfPagesInFilepath($path);
            for ($page = 1; $page <= $pageCount; $page++) {
                $rank = hexdec(substr(hash('sha256', $seed.':'.$pdf->getKey().':'.$page), 0, 13));
                if ($sampleQueue->count() < $sampleLimit || $rank < $sampleQueue->top()['priority']) {
                    if ($sampleQueue->count() >= $sampleLimit) {
                        $sampleQueue->extract();
                    }
                    $sampleQueue->insert(['resource_id' => (int) $pdf->getKey(), 'revision' => $revision, 'page' => $page], $rank);
                }
            }
        }

        if ($sampleQueue->isEmpty()) {
            throw new RuntimeException('The frozen OCR dataset contains no readable PDF pages.');
        }

        $selected = [];
        $selectedQueue = clone $sampleQueue;
        $selectedQueue->setExtractFlags(\SplPriorityQueue::EXTR_DATA);
        while (! $selectedQueue->isEmpty()) {
            $selected[] = $selectedQueue->extract();
        }
        $sampledResources = array_values(array_unique(array_column($selected, 'resource_id')));
        if (count($sampledResources) < 2) {
            throw new RuntimeException('The sample must include pages from at least two different PDFs so the holdout can be separated by document.');
        }
        app(ContextSearchCapacityGate::class)->assertCanStart(count($selected));
        usort($sampledResources, static fn (int $a, int $b): int => strcmp(hash('sha256', $seed.':document:'.$a), hash('sha256', $seed.':document:'.$b)));
        $holdoutResources = array_fill_keys(array_slice($sampledResources, 0, max(1, (int) ceil(count($sampledResources) / 5))), true);
        $profile = [
            'profile_id' => (string) config('context_search.indexing.ocr_quality_profile'),
            'languages' => (string) config('context_search.indexing.ocr_languages'),
            'render_dpi' => (int) config('context_search.indexing.ocr_render_dpi', 300),
            'max_image_pixels' => (int) config('context_search.indexing.ocr_max_image_pixels', 12_000_000),
            'page_segmentation_mode' => (int) config('context_search.indexing.ocr_page_segmentation_mode', 3),
            'tesseract_version' => (string) config('context_search.indexing.ocr_engine_version', 'tesseract-5'),
        ];

        $run = DB::transaction(function () use ($dataset, $user, $sampleLimit, $title, $seed, $selected, $profile, $holdoutResources): ContextSearchOcrCalibrationRun {
            $run = ContextSearchOcrCalibrationRun::query()->create([
                'dataset_id' => $dataset->getKey(), 'created_by' => $user->getKey(), 'title' => $title,
                'status' => ContextSearchOcrCalibrationRun::STATUS_PROCESSING, 'random_seed' => $seed,
                'sample_limit' => $sampleLimit, 'total_pages' => count($selected), 'ocr_profile' => $profile,
            ]);
            foreach ($selected as $candidate) {
                $run->pages()->create([
                    'resource_id' => $candidate['resource_id'], 'source_revision_hash' => $candidate['revision'],
                    'page_number' => $candidate['page'], 'split' => isset($holdoutResources[$candidate['resource_id']]) ? 'holdout' : 'calibration',
                    'status' => ContextSearchOcrCalibrationPage::STATUS_PENDING,
                ]);
            }
            return $run;
        });

        $run->pages()->pluck('id')->each(fn (int $id) => ProcessOcrCalibrationPage::dispatch($run->getKey(), $id));

        return $run->fresh('pages');
    }

    public function resumePending(string $runId): int
    {
        if (app()->isProduction()) {
            throw new RuntimeException('OCR calibration is available only in development and test environments.');
        }
        $this->queueSafety->assertOcrCalibrationDispatchAllowed();

        $run = ContextSearchOcrCalibrationRun::query()->findOrFail($runId);
        if ($run->status !== ContextSearchOcrCalibrationRun::STATUS_PROCESSING) {
            throw new RuntimeException('Only a processing OCR calibration run can be resumed.');
        }

        $queue = (string) config('context_search.indexing.ocr_calibration_queue');
        if (DB::table('jobs')->where('queue', $queue)->exists()) {
            throw new RuntimeException('Die OCR-Kalibrierungsqueue muss vor der Wiederaufnahme leer sein.');
        }

        $pageIds = $run->pages()->where('status', ContextSearchOcrCalibrationPage::STATUS_PENDING)->pluck('id');
        if ($pageIds->isEmpty()) {
            throw new RuntimeException('Der Lauf enthält keine wartenden OCR-Seiten.');
        }
        app(ContextSearchCapacityGate::class)->assertCanStart($pageIds->count());

        $pageIds->each(fn (int $id) => ProcessOcrCalibrationPage::dispatch($runId, $id));

        return $pageIds->count();
    }

    public function review(ContextSearchOcrCalibrationPage $page, User $user, string $label, ?string $reference, ?string $note): void
    {
        $run = $page->run;
        if ($run->status === ContextSearchOcrCalibrationRun::STATUS_APPROVED) {
            throw new RuntimeException('Approved calibration reviews are immutable; start a new run to change the profile.');
        }
        $data = ['quality_label' => $label, 'review_note' => $note, 'reviewed_by' => $user->getKey(), 'reviewed_at' => now()];
        if ($reference !== null) {
            $data['reference_text'] = trim($reference);
        }
        $page->update($data);
        $run->reviewed_pages = $run->pages()->whereNotNull('quality_label')->count();
        $run->sweep_results = null;
        if ($run->processed_pages >= $run->total_pages && $run->reviewed_pages > 0) {
            $run->status = ContextSearchOcrCalibrationRun::STATUS_REVIEWING;
        }
        $run->save();
    }

    public function evaluate(ContextSearchOcrCalibrationRun $run): array
    {
        $labeled = $run->pages()->where('status', ContextSearchOcrCalibrationPage::STATUS_PROCESSED)
            ->whereIn('quality_label', ['usable', 'unusable', 'handwriting', 'blank'])->get();
        $calibration = $labeled->where('split', 'calibration');
        $holdout = $labeled->where('split', 'holdout');
        if ($calibration->count() < 10 || $calibration->where('quality_label', 'usable')->count() < 3 || $calibration->whereIn('quality_label', ['unusable', 'handwriting', 'blank'])->count() < 3) {
            throw new RuntimeException('Label at least 10 calibration pages, including 3 usable and 3 unusable/handwriting/blank pages.');
        }
        if ($calibration->filter(fn ($page): bool => $page->quality_label === 'usable' && filled($page->reference_text))->count() < 3
            || $holdout->filter(fn ($page): bool => $page->quality_label === 'usable' && filled($page->reference_text))->count() < 1) {
            throw new RuntimeException('Provide exact reference transcriptions for at least 3 usable calibration pages and 1 usable holdout page before evaluation.');
        }
        $sweep = [];
        for ($threshold = 0.0; $threshold <= 1.0001; $threshold += 0.05) {
            $sweep[] = $this->score($calibration, round($threshold, 2));
        }
        $eligible = array_values(array_filter($sweep, static fn (array $score): bool => $score['usable_precision'] !== null && $score['usable_precision'] >= 0.95));
        usort($eligible, static fn (array $a, array $b): int => ($b['coverage'] <=> $a['coverage']) ?: ($b['threshold'] <=> $a['threshold']));
        $recommended = $eligible[0] ?? null;
        $holdoutResult = $recommended === null ? null : $this->score($holdout, $recommended['threshold']);
        $results = [
            'method' => 'max_coverage_at_minimum_95_percent_usable_precision',
            'calibration_pages' => $calibration->count(),
            'holdout_pages' => $holdout->count(),
            'minimum_usable_precision' => 0.95,
            'threshold_sweep' => $sweep,
            'recommended_threshold' => $recommended['threshold'] ?? null,
            'holdout' => $holdoutResult,
            'evaluated_at' => now()->toIso8601String(),
        ];
        $run->update(['sweep_results' => $results, 'status' => ContextSearchOcrCalibrationRun::STATUS_EVALUATED]);
        return $results;
    }

    public function approve(ContextSearchOcrCalibrationRun $run, User $user): array
    {
        if (app()->isProduction()) {
            throw new RuntimeException('OCR profiles cannot be calibrated or approved in production.');
        }
        $results = $run->sweep_results ?? [];
        $threshold = $results['recommended_threshold'] ?? null;
        if ($run->status !== ContextSearchOcrCalibrationRun::STATUS_EVALUATED || ! is_numeric($threshold)
            || ($results['holdout']['usable_precision'] ?? 0) < 0.90
            || ! is_numeric($results['holdout']['character_error_rate'] ?? null)
            || ! is_numeric($results['holdout']['word_error_rate'] ?? null)) {
            throw new RuntimeException('The profile requires an evaluated candidate and at least 90% usable precision on the holdout set.');
        }
        $profile = $run->ocr_profile + [
            'minimum_mean_confidence' => (float) $threshold,
            'minimum_recognized_words' => (int) config('context_search.indexing.ocr_quality_minimum_recognized_words', 1),
            'minimum_alphanumeric_ratio' => (float) config('context_search.indexing.ocr_quality_minimum_alphanumeric_ratio', 0),
            'maximum_replacement_character_ratio' => (float) config('context_search.indexing.ocr_quality_maximum_replacement_character_ratio', 0),
            'calibration_run_id' => $run->getKey(),
            'holdout_usable_precision' => $results['holdout']['usable_precision'],
            'approved_by' => $user->getKey(),
        ];
        $profile['profile_id'] = 'tesseract-cal-'.substr((string) $run->getKey(), 0, 8);
        $profile['version'] = '1';
        $hash = hash('sha256', json_encode($profile, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $run->update(['approved_profile' => $profile, 'approved_profile_hash' => $hash, 'approved_at' => now(), 'status' => ContextSearchOcrCalibrationRun::STATUS_APPROVED]);
        return ['profile' => $profile, 'sha256' => $hash];
    }

    private function score($pages, float $threshold): array
    {
        $accepted = $usable = 0;
        $cerErrors = $cerChars = $werErrors = $werWords = 0;
        foreach ($pages as $page) {
            $predicted = (float) ($page->metrics['mean_confidence'] ?? 0) >= $threshold
                && (int) ($page->metrics['recognized_word_count'] ?? 0) >= (int) config('context_search.indexing.ocr_quality_minimum_recognized_words', 1)
                && (float) ($page->metrics['alphanumeric_ratio'] ?? 0) >= (float) config('context_search.indexing.ocr_quality_minimum_alphanumeric_ratio', 0)
                && (float) ($page->metrics['replacement_character_ratio'] ?? 1) <= (float) config('context_search.indexing.ocr_quality_maximum_replacement_character_ratio', 0);
            $actual = $page->quality_label === 'usable';
            if ($predicted) {
                $accepted++;
                $usable += (int) $actual;
                if ($actual && filled($page->reference_text)) {
                    [$ce, $cc] = $this->editStats($this->characters($page->reference_text), $this->characters($page->ocr_text ?? ''));
                    [$we, $wc] = $this->editStats($this->words($page->reference_text), $this->words($page->ocr_text ?? ''));
                    $cerErrors += $ce; $cerChars += $cc; $werErrors += $we; $werWords += $wc;
                }
            }
        }
        $total = count($pages);
        return [
            'threshold' => $threshold,
            'labeled_pages' => $total,
            'accepted_pages' => $accepted,
            'coverage' => $total === 0 ? 0 : $accepted / $total,
            'usable_precision' => $accepted === 0 ? null : $usable / $accepted,
            'character_error_rate' => $cerChars === 0 ? null : $cerErrors / $cerChars,
            'word_error_rate' => $werWords === 0 ? null : $werErrors / $werWords,
            'reference_pages_measured' => $cerChars === 0 ? 0 : $pages->filter(fn ($page): bool => $page->quality_label === 'usable' && filled($page->reference_text) && (float) ($page->metrics['mean_confidence'] ?? 0) >= $threshold)->count(),
        ];
    }

    /** @param list<string> $reference @param list<string> $hypothesis @return array{int, int} */
    private function editStats(array $reference, array $hypothesis): array
    {
        $originalReferenceLength = count($reference);
        $referenceStart = $hypothesisStart = 0;
        $referenceEnd = count($reference) - 1;
        $hypothesisEnd = count($hypothesis) - 1;
        while ($referenceStart <= $referenceEnd && $hypothesisStart <= $hypothesisEnd && $reference[$referenceStart] === $hypothesis[$hypothesisStart]) {
            $referenceStart++;
            $hypothesisStart++;
        }
        while ($referenceStart <= $referenceEnd && $hypothesisStart <= $hypothesisEnd && $reference[$referenceEnd] === $hypothesis[$hypothesisEnd]) {
            $referenceEnd--;
            $hypothesisEnd--;
        }
        $reference = array_slice($reference, $referenceStart, max(0, $referenceEnd - $referenceStart + 1));
        $hypothesis = array_slice($hypothesis, $hypothesisStart, max(0, $hypothesisEnd - $hypothesisStart + 1));
        $referenceLength = count($reference);
        $hypothesisLength = count($hypothesis);
        $previous = range(0, $hypothesisLength);
        foreach ($reference as $i => $token) {
            $current = [$i + 1];
            foreach ($hypothesis as $j => $candidate) {
                $current[$j + 1] = min($current[$j] + 1, $previous[$j + 1] + 1, $previous[$j] + (int) ($token !== $candidate));
            }
            $previous = $current;
        }
        return [$previous[$hypothesisLength], $originalReferenceLength];
    }

    /** @return list<string> */
    private function characters(string $text): array
    {
        return preg_split('//u', mb_strtolower(trim($text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /** @return list<string> */
    private function words(string $text): array
    {
        return preg_split('/[^\pL\pN]+/u', mb_strtolower(trim($text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
