<?php

namespace Tests\Unit\Services\ResourceHandling;

use App\Models\AudioFile;
use App\Services\ResourceHandling\Exceptions\LocalFileDoesNotExistException;
use App\Services\ResourceHandling\FileHandlingService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileHandlingServiceTest extends TestCase
{
    public function test_rejects_a_local_copy_when_the_original_file_has_no_readable_stream(): void
    {
        Storage::fake('resources');
        Storage::fake('local');

        $resource = new AudioFile();
        $resource->setLocalStorageAndPath('resources', 'missing.mp3');

        $this->expectException(LocalFileDoesNotExistException::class);

        resolve(FileHandlingService::class)->makeLocalCopy($resource);
    }
}
