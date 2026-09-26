<?php

namespace Tests\Unit\Services\PreviewGeneration\Generators;

use App\Models\AudioFile;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Generators\AudioPreviewGenerator;
use App\Services\ResourceHandling\Exceptions\LocalFileDoesNotExistException;
use App\Services\ResourceHandling\FileHandlingService;
use Intervention\Image\ImageManager;
use Intervention\Image\Size;
use Tests\TestCase;

class AudioPreviewGeneratorTest extends TestCase
{
    public function test_returns_no_preview_when_the_original_file_cannot_be_read(): void
    {
        $fileHandlingService = $this->mock(FileHandlingService::class);
        $fileHandlingService->shouldReceive('makeLocalCopy')
            ->once()
            ->andThrow(new LocalFileDoesNotExistException());
        $fileHandlingService->shouldNotReceive('cleanupLocalCopy');

        $generator = new AudioPreviewGenerator(
            $this->mock(ImageManager::class),
            $fileHandlingService
        );

        $this->expectException(NotPreviewAbleException::class);

        $generator->getImagePreview(new AudioFile(), new Size(640, 640));
    }
}
