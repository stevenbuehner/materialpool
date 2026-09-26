<?php

namespace Tests\Feature;

use App\Services\ContextSearch\ContextSearchPageArtifactStore;
use App\Services\ContextSearch\Extraction\ExtractedPage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ContextSearchPageArtifactStoreTest extends TestCase
{
    public function test_missing_or_corrupt_artifact_is_a_cache_miss_and_can_be_regenerated(): void
    {
        Storage::fake('local');
        $store = new ContextSearchPageArtifactStore();
        $runId = '11111111-1111-4111-8111-111111111111';
        $revision = str_repeat('a', 64);
        $page = new ExtractedPage(2, 'Geprüfter Seitentext.', 'ocr', 0.92, 'tesseract-5');

        $first = $store->write($runId, 7, $revision, $page);
        $this->assertSame('Geprüfter Seitentext.', $store->read($first['path'], $first['hash'], 2)?->text);

        Storage::disk('local')->delete($first['path']);
        $this->assertNull($store->read($first['path'], $first['hash'], 2));

        $second = $store->write($runId, 7, $revision, $page);
        $this->assertSame($first, $second);
        $this->assertSame('Geprüfter Seitentext.', $store->read($second['path'], $second['hash'], 2)?->text);

        Storage::disk('local')->put($second['path'], 'beschädigt');
        $this->assertNull($store->read($second['path'], $second['hash'], 2));
    }

    public function test_rejects_invalid_artifact_paths_and_page_identity(): void
    {
        Storage::fake('local');
        $store = new ContextSearchPageArtifactStore();

        $this->assertNull($store->read('../resources/private.pdf', str_repeat('a', 64), 1));
        $this->assertNull($store->read('11111111-1111-4111-8111-111111111111/7/'.str_repeat('a', 64).'/page-2.json', str_repeat('b', 64), 1));
    }
}
