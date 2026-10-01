<?php

namespace Tests\Feature;

use App\Jobs\DeleteMaterialDownload;
use App\Jobs\GenerateMaterialDownload;
use App\Events\MaterialWasCreated;
use App\Events\ResourceWasCreated;
use App\Models\Material;
use App\Models\AudioFile;
use App\Models\DocumentFile;
use App\Models\PdfFile;
use App\Models\Text;
use App\Models\User;
use App\ResourceLimitations\PageLimitation;
use App\ResourceLimitations\TimeLimitation;
use App\Services\MaterialHandling\MaterialDownloadStore;
use App\Services\PreviewGeneration\Generators\DocumentPreviewGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;
use ZipArchive;
use setasign\Fpdi\Fpdi;

class MaterialDownloadTest extends TestCase {
    use RefreshDatabase;

    private string $downloadRoot;

    protected function setUp(): void {
        parent::setUp();
        $this->downloadRoot = sys_get_temp_dir().'/material-download-test-'.bin2hex(random_bytes(8));
        config(['material_downloads.root' => $this->downloadRoot]);
        Event::fake([MaterialWasCreated::class, ResourceWasCreated::class]);
    }

    protected function tearDown(): void {
        File::deleteDirectory($this->downloadRoot);
        parent::tearDown();
    }

    public function test_create_queues_archive_and_expiry_and_status_is_owner_only(): void {
        Queue::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $material = Material::factory()->privatelyVisible()->create(['created_by' => $owner->id]);

        $this->getJson(route('api.v1.materials.createPublicZipDownload', $material))->assertUnauthorized();
        Passport::actingAs($owner);
        $response = $this->getJson(route('api.v1.materials.createPublicZipDownload', [
            'material' => $material,
            'expires_in_days' => 7,
        ]))->assertStatus(202)->assertJsonPath('status', 'pending');

        $statusUrl = $response->json('status_url');
        $this->assertMatchesRegularExpression('~/downloads/[a-f0-9]{64}$~', $statusUrl);
        Queue::assertPushed(GenerateMaterialDownload::class, 1);
        Queue::assertPushed(DeleteMaterialDownload::class, function (DeleteMaterialDownload $job): bool {
            return $job->queue === 'default' && $job->delay->isBetween(now()->addDays(7)->subMinute(), now()->addDays(7)->addMinute());
        });
        $this->getJson($statusUrl)->assertOk()->assertJsonPath('link', NULL);
        Passport::actingAs($other);
        $this->getJson(route('api.v1.materials.createPublicZipDownload', $material))->assertNotFound();
        $this->getJson($statusUrl)->assertNotFound();
    }

    public function test_archive_contains_only_readable_resources_and_limited_pdf_pages(): void {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $material = Material::factory()->create(['created_by' => $owner->id]);
        $pdf = PdfFile::factory()->create(['created_by' => $owner->id, 'is_public' => TRUE]);
        $privateText = Text::factory()->create(['created_by' => $other->id, 'is_public' => FALSE]);
        $material->resources()->attach($pdf->id, ['limitation' => (new PageLimitation())->setPages([1])]);
        $material->resources()->attach($privateText->id);

        Queue::fake();
        Passport::actingAs($owner);
        $response = $this->getJson(route('api.v1.materials.createPublicZipDownload', $material))->assertStatus(202);
        $token = basename($response->json('status_url'));
        app(GenerateMaterialDownload::class, ['materialId' => $material->id, 'userId' => $owner->id, 'token' => $token])
            ->handle(app(\App\Services\MaterialHandling\MaterialDownloadArchive::class), app(MaterialDownloadStore::class));

        $status = $this->getJson($response->json('status_url'))->assertOk()->assertJsonPath('status', 'ready');
        $this->assertSame('/material-downloads/'.$token.'.zip', $status->json('link'));
        $zip = new ZipArchive();
        $this->assertTrue($zip->open(app(MaterialDownloadStore::class)->readyPath($token)));
        $this->assertSame(1, $zip->numFiles);
        $this->assertStringEndsWith('.pdf', $zip->getNameIndex(0));
        $pdfContents = $zip->getFromIndex(0);
        $this->assertStringStartsWith('%PDF', $pdfContents);
        $extractedPath = $this->downloadRoot.'/extracted.pdf';
        File::put($extractedPath, $pdfContents);
        $this->assertSame(1, (new Fpdi())->setSourceFile($extractedPath));
        $zip->close();

        (new DeleteMaterialDownload($token))->handle(app(MaterialDownloadStore::class));
        $this->assertFileDoesNotExist(app(MaterialDownloadStore::class)->readyPath($token));
    }

    public function test_document_pages_and_audio_time_ranges_are_exported_as_reduced_files(): void {
        if (php_uname('m') !== 'x86_64') {
            $this->markTestSkipped('Das versionierte FFmpeg-Binary benötigt Linux x86_64.');
        }
        $owner = User::factory()->create();
        $material = Material::factory()->create(['created_by' => $owner->id]);
        $document = DocumentFile::factory()->fromTestFile('Document.docx')->create(['created_by' => $owner->id, 'is_public' => TRUE]);
        $audio = AudioFile::factory()->create(['created_by' => $owner->id, 'is_public' => TRUE]);
        $time = new TimeLimitation();
        $time->setStart(0);
        $time->setEnd(1);
        $material->resources()->attach($document->id, ['limitation' => (new PageLimitation())->setPages([1])]);
        $material->resources()->attach($audio->id, ['limitation' => $time]);

        $documentPreview = \Mockery::mock(DocumentPreviewGenerator::class);
        $documentPreview->shouldReceive('getTemporaryPdfFromDocument')
            ->once()->andReturn(Storage::disk(config('app.disks.testfiles'))->path('PDF.pdf'));
        app()->instance(DocumentPreviewGenerator::class, $documentPreview);

        Queue::fake();
        Passport::actingAs($owner);
        $response = $this->getJson(route('api.v1.materials.createPublicZipDownload', $material))->assertStatus(202);
        $token = basename($response->json('status_url'));
        (new GenerateMaterialDownload($material->id, $owner->id, $token))
            ->handle(app(\App\Services\MaterialHandling\MaterialDownloadArchive::class), app(MaterialDownloadStore::class));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open(app(MaterialDownloadStore::class)->readyPath($token)));
        $this->assertSame(2, $zip->numFiles);
        $names = [$zip->getNameIndex(0), $zip->getNameIndex(1)];
        $this->assertCount(1, array_filter($names, fn (string $name): bool => str_ends_with($name, '.pdf')));
        $this->assertCount(1, array_filter($names, fn (string $name): bool => str_ends_with($name, '.mp3')));
        $zip->close();
    }

    public function test_invalid_retention_is_rejected_without_dispatching_jobs(): void {
        Queue::fake();
        $owner = User::factory()->create();
        $material = Material::factory()->create(['created_by' => $owner->id]);
        Passport::actingAs($owner);

        $this->getJson(route('api.v1.materials.createPublicZipDownload', [
            'material' => $material,
            'expires_in_days' => 0,
        ]))->assertUnprocessable();
        Queue::assertNothingPushed();
    }

    public function test_missing_source_does_not_publish_an_archive(): void {
        $owner = User::factory()->create();
        $material = Material::factory()->create(['created_by' => $owner->id]);
        $missing = PdfFile::factory()->withMissingLocalFile('missing.pdf')->create([
            'created_by' => $owner->id,
            'is_public' => TRUE,
        ]);
        $material->resources()->attach($missing->id);
        Queue::fake();
        Passport::actingAs($owner);
        $response = $this->getJson(route('api.v1.materials.createPublicZipDownload', $material))->assertStatus(202);
        $token = basename($response->json('status_url'));
        $job = new GenerateMaterialDownload($material->id, $owner->id, $token);

        try {
            $job->handle(app(\App\Services\MaterialHandling\MaterialDownloadArchive::class), app(MaterialDownloadStore::class));
            $this->fail('Missing source should fail archive creation.');
        } catch (\RuntimeException $exception) {
            $job->failed($exception);
        }

        $this->getJson($response->json('status_url'))->assertOk()
            ->assertJsonPath('status', 'failed')->assertJsonPath('link', NULL);
        $this->assertFileDoesNotExist(app(MaterialDownloadStore::class)->readyPath($token));
    }

    public function test_expired_download_is_removed_even_if_its_delete_job_did_not_run(): void {
        $store = app(MaterialDownloadStore::class);
        $token = bin2hex(random_bytes(32));
        $store->create($token, 1, 1, now()->subDay()->toIso8601String());
        File::ensureDirectoryExists($this->downloadRoot.'/ready');
        File::put($store->readyPath($token), 'temporary archive');

        $store->deleteExpired();

        $this->assertNull($store->read($token));
        $this->assertFileDoesNotExist($store->readyPath($token));
    }
}
