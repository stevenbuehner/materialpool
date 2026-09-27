<?php

namespace Tests\Feature;

use App\Events\ResourceWasCreated;
use App\Jobs\ContextSearch\ProcessOcrCalibrationPage;
use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\ContextSearchOcrCalibrationPage;
use App\Models\ContextSearchOcrCalibrationRun;
use App\Models\PdfFile;
use App\Models\User;
use App\Services\ContextSearch\Extraction\OcrProcessor;
use App\Services\ContextSearch\Extraction\OcrResult;
use App\Services\ContextSearch\OcrCalibrationService;
use App\Services\ResourceHandling\FileHandlingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

final class ContextSearchOcrCalibrationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_delivery_does_not_repeat_ocr_or_regress_an_approved_run(): void
    {
        Event::fake([ResourceWasCreated::class]);
        $user = User::factory()->create();
        $pdf = PdfFile::factory()->create(['created_by' => $user->getKey()]);
        $run = $this->makeRun($user);
        $page = $run->pages()->create([
            'resource_id' => $pdf->getKey(),
            'source_revision_hash' => hash_file('sha256', $pdf->getAbsoluteLocalPath()),
            'page_number' => 1,
            'split' => ContextSearchOcrCalibrationPage::SPLIT_CALIBRATION,
            'status' => ContextSearchOcrCalibrationPage::STATUS_PENDING,
        ]);
        $ocr = new class implements OcrProcessor {
            public int $calls = 0;

            public function extractPage(string $pdfPath, int $pageNumber): OcrResult
            {
                $this->calls++;

                return new OcrResult('test text', 0.9, 'test-engine', ['mean_confidence' => 0.9]);
            }
        };
        $job = new ProcessOcrCalibrationPage($run->getKey(), $page->getKey());

        $this->assertSame(480, $job->timeout);
        $job->handle($ocr, app(FileHandlingService::class));
        $job->handle($ocr, app(FileHandlingService::class));

        $this->assertSame(1, $ocr->calls);
        $this->assertSame(ContextSearchOcrCalibrationPage::STATUS_PROCESSED, $page->fresh()->status);
        $this->assertSame(1, $run->fresh()->processed_pages);
        $this->assertSame(ContextSearchOcrCalibrationRun::STATUS_REVIEWING, $run->fresh()->status);

        $run->update(['status' => ContextSearchOcrCalibrationRun::STATUS_APPROVED]);
        $job->failed(new RuntimeException('late duplicate failure'));

        $this->assertSame(ContextSearchOcrCalibrationRun::STATUS_APPROVED, $run->fresh()->status);
        $this->assertSame(ContextSearchOcrCalibrationPage::STATUS_PROCESSED, $page->fresh()->status);
    }

    public function test_sample_is_rejected_before_creating_a_run_when_ocr_queue_capacity_is_exhausted(): void
    {
        Event::fake([ResourceWasCreated::class]);
        Bus::fake([ProcessOcrCalibrationPage::class]);
        config()->set('context_search.indexing.local_ocr_calibration_dispatch_enabled', true);
        config()->set('context_search.indexing.minimum_free_disk_bytes', 0);
        config()->set('context_search.indexing.maximum_queued_jobs', 1);
        $user = User::factory()->create();
        $dataset = ContextSearchEvaluationDataset::query()->create([
            'purpose' => 'ocr',
            'status' => ContextSearchEvaluationDataset::STATUS_FROZEN,
            'manifest' => [],
            'manifest_hash' => str_repeat('a', 64),
        ]);
        foreach (range(1, 2) as $_) {
            $pdf = PdfFile::factory()->create(['created_by' => $user->getKey()]);
            ContextSearchEvaluationDatasetMember::query()->create([
                'dataset_id' => $dataset->getKey(),
                'member_type' => ContextSearchEvaluationDatasetMember::TYPE_RESOURCE,
                'member_id' => $pdf->getKey(),
            ]);
        }

        try {
            app(OcrCalibrationService::class)->start($dataset->getKey(), $user, 15);
            $this->fail('The sample exceeded the configured queue capacity.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Queue-Kapazitätsgrenze', $exception->getMessage());
        }

        $this->assertDatabaseCount('context_search_ocr_calibration_runs', 0);
        Bus::assertNotDispatched(ProcessOcrCalibrationPage::class);
    }

    public function test_changed_pdf_is_rejected_without_running_ocr(): void
    {
        Event::fake([ResourceWasCreated::class]);
        $user = User::factory()->create();
        $pdf = PdfFile::factory()->create(['created_by' => $user->getKey()]);
        $run = $this->makeRun($user);
        $page = $run->pages()->create([
            'resource_id' => $pdf->getKey(),
            'source_revision_hash' => str_repeat('0', 64),
            'page_number' => 1,
            'split' => ContextSearchOcrCalibrationPage::SPLIT_CALIBRATION,
            'status' => ContextSearchOcrCalibrationPage::STATUS_PENDING,
        ]);
        $ocr = new class implements OcrProcessor {
            public function extractPage(string $pdfPath, int $pageNumber): OcrResult
            {
                throw new RuntimeException('OCR must not run on a changed PDF.');
            }
        };

        (new ProcessOcrCalibrationPage($run->getKey(), $page->getKey()))
            ->handle($ocr, app(FileHandlingService::class));

        $this->assertSame(ContextSearchOcrCalibrationPage::STATUS_FAILED, $page->fresh()->status);
        $this->assertSame(ContextSearchOcrCalibrationRun::STATUS_FAILED, $run->fresh()->status);
    }

    public function test_page_status_rolls_back_if_run_progress_cannot_be_saved(): void
    {
        Event::fake([ResourceWasCreated::class]);
        $user = User::factory()->create();
        $pdf = PdfFile::factory()->create(['created_by' => $user->getKey()]);
        $run = $this->makeRun($user);
        $page = $run->pages()->create([
            'resource_id' => $pdf->getKey(),
            'source_revision_hash' => hash_file('sha256', $pdf->getAbsoluteLocalPath()),
            'page_number' => 1,
            'split' => ContextSearchOcrCalibrationPage::SPLIT_CALIBRATION,
            'status' => ContextSearchOcrCalibrationPage::STATUS_PENDING,
        ]);
        $ocr = new class implements OcrProcessor {
            public function extractPage(string $pdfPath, int $pageNumber): OcrResult
            {
                return new OcrResult('test text', 0.9, 'test-engine');
            }
        };
        $failOnce = true;
        ContextSearchOcrCalibrationRun::updating(function () use (&$failOnce): void {
            if ($failOnce) {
                $failOnce = false;
                throw new RuntimeException('simulated progress failure');
            }
        });

        try {
            (new ProcessOcrCalibrationPage($run->getKey(), $page->getKey()))
                ->handle($ocr, app(FileHandlingService::class));
            $this->fail('The simulated progress failure should abort the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('simulated progress failure', $exception->getMessage());
        }

        $this->assertSame(ContextSearchOcrCalibrationPage::STATUS_PENDING, $page->fresh()->status);
        $this->assertSame(0, $run->fresh()->processed_pages);
        $this->assertSame(ContextSearchOcrCalibrationRun::STATUS_PROCESSING, $run->fresh()->status);
    }

    public function test_a_late_ocr_result_cannot_replace_a_terminal_failure(): void
    {
        Event::fake([ResourceWasCreated::class]);
        $user = User::factory()->create();
        $pdf = PdfFile::factory()->create(['created_by' => $user->getKey()]);
        $run = $this->makeRun($user);
        $page = $run->pages()->create([
            'resource_id' => $pdf->getKey(),
            'source_revision_hash' => hash_file('sha256', $pdf->getAbsoluteLocalPath()),
            'page_number' => 1,
            'split' => ContextSearchOcrCalibrationPage::SPLIT_CALIBRATION,
            'status' => ContextSearchOcrCalibrationPage::STATUS_PENDING,
        ]);
        $job = new ProcessOcrCalibrationPage($run->getKey(), $page->getKey());
        $ocr = new class($job) implements OcrProcessor {
            public function __construct(private readonly ProcessOcrCalibrationPage $job) {}

            public function extractPage(string $pdfPath, int $pageNumber): OcrResult
            {
                $this->job->failed(new RuntimeException('terminal failure'));

                return new OcrResult('late result', 0.9, 'test-engine');
            }
        };

        $job->handle($ocr, app(FileHandlingService::class));

        $this->assertSame(ContextSearchOcrCalibrationPage::STATUS_FAILED, $page->fresh()->status);
        $this->assertNull($page->fresh()->ocr_text);
        $this->assertSame(ContextSearchOcrCalibrationRun::STATUS_FAILED, $run->fresh()->status);
    }

    public function test_sample_uses_frozen_member_revisions_without_rehashing_every_pdf(): void
    {
        Event::fake([ResourceWasCreated::class]);
        Bus::fake([ProcessOcrCalibrationPage::class]);
        config()->set('context_search.indexing.local_ocr_calibration_dispatch_enabled', true);
        config()->set('context_search.indexing.minimum_free_disk_bytes', 0);
        $user = User::factory()->create();
        $dataset = ContextSearchEvaluationDataset::query()->create([
            'purpose' => 'ocr',
            'status' => ContextSearchEvaluationDataset::STATUS_FROZEN,
            'manifest' => [],
            'manifest_hash' => str_repeat('c', 64),
        ]);
        foreach (range(1, 2) as $number) {
            $pdf = PdfFile::factory()->create(['created_by' => $user->getKey()]);
            ContextSearchEvaluationDatasetMember::query()->create([
                'dataset_id' => $dataset->getKey(),
                'member_type' => ContextSearchEvaluationDatasetMember::TYPE_RESOURCE,
                'member_id' => $pdf->getKey(),
                'source_revision_hash' => str_repeat((string) $number, 64),
            ]);
        }

        $run = app(OcrCalibrationService::class)->start($dataset->getKey(), $user, 15);

        $this->assertSame(2, $run->total_pages);
        $this->assertEqualsCanonicalizing(
            [str_repeat('1', 64), str_repeat('2', 64)],
            $run->pages->pluck('source_revision_hash')->all(),
        );
        Bus::assertDispatchedTimes(ProcessOcrCalibrationPage::class, 2);
    }

    public function test_resume_only_requeues_pending_pages_after_the_calibration_queue_is_empty(): void
    {
        Bus::fake([ProcessOcrCalibrationPage::class]);
        config()->set('context_search.indexing.local_ocr_calibration_dispatch_enabled', true);
        config()->set('context_search.indexing.minimum_free_disk_bytes', 0);
        $run = $this->makeRun(User::factory()->create());
        $run->pages()->create([
            'resource_id' => 1,
            'source_revision_hash' => str_repeat('1', 64),
            'page_number' => 1,
            'split' => ContextSearchOcrCalibrationPage::SPLIT_CALIBRATION,
            'status' => ContextSearchOcrCalibrationPage::STATUS_PENDING,
        ]);
        DB::table('jobs')->insert([
            'queue' => 'context-search-calibration-ocr',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        $this->artisan('context-search:ocr-calibration:resume', ['run' => $run->getKey()])
            ->expectsOutputToContain('muss vor der Wiederaufnahme leer sein')
            ->assertExitCode(1);
        Bus::assertNotDispatched(ProcessOcrCalibrationPage::class);

        DB::table('jobs')->where('queue', 'context-search-calibration-ocr')->delete();
        $this->artisan('context-search:ocr-calibration:resume', ['run' => $run->getKey()])
            ->expectsOutputToContain('1 wartende OCR-Seiten')
            ->assertExitCode(0);
        Bus::assertDispatchedTimes(ProcessOcrCalibrationPage::class, 1);
    }

    public function test_database_queue_retry_after_a_worker_crash_does_not_repeat_completed_ocr(): void
    {
        Event::fake([ResourceWasCreated::class]);
        $user = User::factory()->create();
        $pdf = PdfFile::factory()->create(['created_by' => $user->getKey()]);
        $run = $this->makeRun($user);
        $page = $run->pages()->create([
            'resource_id' => $pdf->getKey(),
            'source_revision_hash' => hash_file('sha256', $pdf->getAbsoluteLocalPath()),
            'page_number' => 1,
            'split' => ContextSearchOcrCalibrationPage::SPLIT_CALIBRATION,
            'status' => ContextSearchOcrCalibrationPage::STATUS_PENDING,
        ]);
        $ocr = new class implements OcrProcessor {
            public int $calls = 0;

            public function extractPage(string $pdfPath, int $pageNumber): OcrResult
            {
                $this->calls++;

                return new OcrResult('test text', 0.9, 'test-engine');
            }
        };
        app()->instance(OcrProcessor::class, $ocr);
        dispatch(new ProcessOcrCalibrationPage($run->getKey(), $page->getKey()));
        $queue = (string) config('context_search.indexing.ocr_calibration_queue');

        $firstDelivery = Queue::connection('context_search')->pop($queue);
        $this->assertNotNull($firstDelivery);
        // Execute the payload, then simulate a crash before Laravel acknowledges the reservation.
        (new ProcessOcrCalibrationPage($run->getKey(), $page->getKey()))
            ->handle($ocr, app(FileHandlingService::class));
        $this->assertSame(1, $ocr->calls);
        $this->assertSame(ContextSearchOcrCalibrationPage::STATUS_PROCESSED, $page->fresh()->status);

        // The worker dies before acknowledging the completed job; the reservation expires.
        DB::table('jobs')->where('queue', $queue)->update(['reserved_at' => time() - 601]);
        $secondDelivery = Queue::connection('context_search')->pop($queue);
        $this->assertNotNull($secondDelivery);
        $secondDelivery->fire();
        $secondDelivery->delete();

        $this->assertSame(1, $ocr->calls);
        $this->assertSame(1, $run->fresh()->processed_pages);
        $this->assertDatabaseCount('jobs', 0);
    }

    private function makeRun(User $user): ContextSearchOcrCalibrationRun
    {
        $dataset = ContextSearchEvaluationDataset::query()->create([
            'purpose' => 'ocr',
            'status' => ContextSearchEvaluationDataset::STATUS_FROZEN,
            'manifest' => [],
            'manifest_hash' => str_repeat('b', 64),
        ]);

        return ContextSearchOcrCalibrationRun::query()->create([
            'dataset_id' => $dataset->getKey(),
            'created_by' => $user->getKey(),
            'status' => ContextSearchOcrCalibrationRun::STATUS_PROCESSING,
            'random_seed' => 1,
            'sample_limit' => 15,
            'total_pages' => 1,
            'ocr_profile' => [],
        ]);
    }
}
