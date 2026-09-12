<?php

namespace Tests\Feature\UpgradeBaseline;

use App\Models\AudioFile;
use App\Models\File;
use App\Models\VideoFile;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FileMetadataFallbackTest extends TestCase
{
    public function test_missing_file_size_returns_zero_and_is_logged(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Unable to retrieve local file metadata.', [
                'resource_id' => null,
                'metadata' => 'file_size',
            ]);

        $file = new File();
        $file->setAttribute('local_path', config('app.disks.resources').'::missing.bin');

        $this->assertSame(0, $file->filesize);
    }

    public function test_missing_audio_mime_type_returns_empty_string_and_is_logged(): void
    {
        $this->assertMissingMimeTypeIsHandled(new AudioFile());
    }

    public function test_missing_video_mime_type_returns_empty_string_and_is_logged(): void
    {
        $this->assertMissingMimeTypeIsHandled(new VideoFile());
    }

    private function assertMissingMimeTypeIsHandled(File $file): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Unable to retrieve local file metadata.', [
                'resource_id' => null,
                'metadata' => 'mime_type',
            ]);

        $file->setAttribute('local_path', config('app.disks.resources').'::missing.bin');

        $this->assertSame('', $file->mime_type);
    }
}
