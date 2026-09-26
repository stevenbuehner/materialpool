<?php

namespace App\Jobs\ContextSearch;

use App\Models\ContextSearchOcrCalibrationPage;
use App\Models\ContextSearchOcrCalibrationRun;
use App\Models\PdfFile;
use App\Services\ContextSearch\Extraction\OcrProcessor;
use App\Services\ResourceHandling\FileHandlingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ProcessOcrCalibrationPage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 480;
    public int $tries = 3;
    public array $backoff = [15, 60];

    public function __construct(private readonly string $runId, private readonly int $pageId)
    {
        $this->onConnection((string) config('context_search.indexing.connection'));
        $this->onQueue((string) config('context_search.indexing.ocr_calibration_queue'));
    }

    public function handle(OcrProcessor $ocr, FileHandlingService $files): void
    {
        $lock = Cache::lock('context-search:ocr-calibration:'.$this->pageId, 570);
        if (! $lock->get()) {
            return;
        }

        try {
            $this->processPage($ocr, $files);
        } finally {
            $lock->release();
        }
    }

    private function processPage(OcrProcessor $ocr, FileHandlingService $files): void
    {
        $page = ContextSearchOcrCalibrationPage::query()->where('run_id', $this->runId)->find($this->pageId);
        if ($page === null || $page->status !== ContextSearchOcrCalibrationPage::STATUS_PENDING) {
            return;
        }
        $pdf = (new PdfFile())->newQueryWithoutScopes()->find($page->resource_id);
        if (! $pdf instanceof PdfFile) {
            $this->finishPage(['status' => ContextSearchOcrCalibrationPage::STATUS_FAILED]);
            return;
        }
        $path = $files->getLocalFilePath($pdf);
        $revision = is_file($path) ? hash_file('sha256', $path) : false;
        if ($revision === false || ! hash_equals($page->source_revision_hash, $revision)) {
            $this->finishPage(['status' => ContextSearchOcrCalibrationPage::STATUS_FAILED, 'review_note' => 'Source revision changed after sampling.']);
            return;
        }
        try {
            $result = $ocr->extractPage($path, $page->page_number);
            $this->finishPage(['status' => ContextSearchOcrCalibrationPage::STATUS_PROCESSED, 'metrics' => $result->metrics, 'ocr_text' => $result->text]);
        } catch (Throwable $exception) {
            if ($this->attempts() < $this->tries) {
                throw $exception;
            }
            $this->finishPage(['status' => ContextSearchOcrCalibrationPage::STATUS_FAILED]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->finishPage(['status' => ContextSearchOcrCalibrationPage::STATUS_FAILED]);
    }

    /** @param array<string, mixed> $data */
    private function finishPage(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $run = ContextSearchOcrCalibrationRun::query()->lockForUpdate()->find($this->runId);
            if ($run === null || in_array($run->status, [ContextSearchOcrCalibrationRun::STATUS_EVALUATED, ContextSearchOcrCalibrationRun::STATUS_APPROVED], true)) {
                return;
            }
            $changed = ContextSearchOcrCalibrationPage::query()->where('run_id', $this->runId)->whereKey($this->pageId)
                ->where('status', ContextSearchOcrCalibrationPage::STATUS_PENDING)->update($data);
            if ($changed === 0) {
                return;
            }
            $terminal = $run->pages()->whereIn('status', [ContextSearchOcrCalibrationPage::STATUS_PROCESSED, ContextSearchOcrCalibrationPage::STATUS_FAILED])->count();
            $failed = $run->pages()->where('status', ContextSearchOcrCalibrationPage::STATUS_FAILED)->exists();
            $run->update([
                'processed_pages' => $terminal,
                'status' => $terminal >= $run->total_pages
                    ? ($failed ? ContextSearchOcrCalibrationRun::STATUS_FAILED : ContextSearchOcrCalibrationRun::STATUS_REVIEWING)
                    : ContextSearchOcrCalibrationRun::STATUS_PROCESSING,
            ]);
        });
    }
}
