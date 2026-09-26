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
use Throwable;

final class ProcessOcrCalibrationPage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [15, 60];

    public function __construct(private readonly string $runId, private readonly int $pageId)
    {
        $this->onConnection((string) config('context_search.indexing.connection'));
        $this->onQueue((string) config('context_search.indexing.ocr_calibration_queue'));
    }

    public function handle(OcrProcessor $ocr, FileHandlingService $files): void
    {
        $page = ContextSearchOcrCalibrationPage::query()->where('run_id', $this->runId)->find($this->pageId);
        if ($page === null || $page->status !== ContextSearchOcrCalibrationPage::STATUS_PENDING) {
            return;
        }
        $pdf = (new PdfFile())->newQueryWithoutScopes()->find($page->resource_id);
        if (! $pdf instanceof PdfFile) {
            $page->update(['status' => ContextSearchOcrCalibrationPage::STATUS_FAILED]);
            $this->updateRunProgress();
            return;
        }
        $path = $files->getLocalFilePath($pdf);
        $revision = is_file($path) ? hash_file('sha256', $path) : false;
        if ($revision === false || ! hash_equals($page->source_revision_hash, $revision)) {
            $page->update(['status' => ContextSearchOcrCalibrationPage::STATUS_FAILED, 'review_note' => 'Source revision changed after sampling.']);
            $this->updateRunProgress();
            return;
        }
        try {
            $result = $ocr->extractPage($path, $page->page_number);
            $page->update(['status' => ContextSearchOcrCalibrationPage::STATUS_PROCESSED, 'metrics' => $result->metrics, 'ocr_text' => $result->text]);
        } catch (Throwable $exception) {
            if ($this->attempts() < $this->tries) {
                throw $exception;
            }
            $page->update(['status' => ContextSearchOcrCalibrationPage::STATUS_FAILED]);
        }
        $this->updateRunProgress();
    }

    public function failed(?Throwable $exception): void
    {
        ContextSearchOcrCalibrationPage::query()->where('run_id', $this->runId)->whereKey($this->pageId)
            ->where('status', ContextSearchOcrCalibrationPage::STATUS_PENDING)
            ->update(['status' => ContextSearchOcrCalibrationPage::STATUS_FAILED]);
        $this->updateRunProgress();
    }

    private function updateRunProgress(): void
    {
        $run = ContextSearchOcrCalibrationRun::query()->find($this->runId);
        if ($run === null) {
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
    }
}
