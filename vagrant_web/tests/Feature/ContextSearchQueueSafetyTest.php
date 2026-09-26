<?php

namespace Tests\Feature;

use App\Jobs\ContextSearch\ProcessOcrCalibrationPage;
use App\Models\User;
use App\Services\ContextSearch\ContextSearchQueueSafety;
use App\Services\ContextSearch\OcrCalibrationService;
use Illuminate\Support\Facades\Bus;
use RuntimeException;
use Tests\TestCase;

final class ContextSearchQueueSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('context_search.indexing.connection', 'context_search');
        config()->set('context_search.indexing.queue', 'context-search-extraction');
        config()->set('context_search.indexing.ocr_calibration_queue', 'context-search-calibration-ocr');
        config()->set('queue.connections.context_search.retry_after', 600);
    }

    public function test_prepared_connection_keeps_the_default_queue_unchanged_and_routes_ocr_separately(): void
    {
        config()->set('queue.connections.database.retry_after', 150);
        $job = new ProcessOcrCalibrationPage('run', 1);

        $this->assertSame(150, (int) config('queue.connections.database.retry_after'));
        $this->assertSame('context_search', $job->connection);
        $this->assertSame('context-search-calibration-ocr', $job->queue);
        $this->assertSame(600, (int) config('queue.connections.context_search.retry_after'));
        app(ContextSearchQueueSafety::class)->assertConfigured();
    }

    public function test_rejects_the_historical_queue_name(): void
    {
        config()->set('context_search.indexing.queue', 'context-search-indexing');

        $this->expectException(RuntimeException::class);
        app(ContextSearchQueueSafety::class)->assertConfigured();
    }

    public function test_rejects_an_unsafe_reservation_period(): void
    {
        config()->set('queue.connections.context_search.retry_after', 500);

        $this->expectException(RuntimeException::class);
        app(ContextSearchQueueSafety::class)->assertConfigured();
    }

    public function test_rejects_dispatch_on_the_default_connection(): void
    {
        config()->set('context_search.indexing.connection', 'database');

        $this->expectException(RuntimeException::class);
        app(ContextSearchQueueSafety::class)->assertConfigured();
    }

    public function test_blocks_ocr_calibration_before_creating_a_run_or_dispatching_jobs(): void
    {
        Bus::fake([ProcessOcrCalibrationPage::class]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bleiben bis zur Abnahme');

        try {
            app(OcrCalibrationService::class)->start('unused', new User, 15);
        } finally {
            Bus::assertNotDispatched(ProcessOcrCalibrationPage::class);
        }
    }

    public function test_configuration_check_refuses_an_unsafe_retry_after(): void
    {
        config()->set('queue.connections.context_search.retry_after', 500);

        $this->artisan('context-search:queue:check', ['--configuration-only' => true])
            ->expectsOutputToContain('Reservierungsfrist')
            ->assertExitCode(1);
    }
}
