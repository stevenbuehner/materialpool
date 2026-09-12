<?php

namespace Tests\Feature;

use App\Events\ResourceWasCreated;
use App\Models\AudioFile;
use App\Models\DocumentFile;
use App\Models\File;
use App\Models\ImageFile;
use App\Models\PdfFile;
use App\Models\User;
use App\Models\VideoFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeededResourceFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_factories_create_readable_files_with_matching_media_types(): void
    {
        User::factory()->create();

        $file = File::factory()->create();
        $audio = AudioFile::factory()->create();
        $video = VideoFile::factory()->create();
        $image = ImageFile::factory()->create();
        $document = DocumentFile::factory()->create();
        $pdf = PdfFile::factory()->create();

        foreach ([$file, $audio, $video, $image, $document, $pdf] as $resource) {
            $this->assertTrue(
                Storage::disk(config('app.disks.resources'))->exists($resource->getLocalFilePath())
            );
            $this->assertGreaterThan(0, $resource->filesize);
            $this->assertNotSame('just a fake hash', $resource->content_hash);
        }

        $this->assertStringStartsWith('audio/', $audio->mime_type);
        $this->assertStringStartsWith('video/', $video->mime_type);
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $document->getLocalMimeType()
        );
        $this->assertSame('Testdokument.docx', $document->original_filename);
    }

    public function test_without_local_file_state_does_not_create_an_orphaned_file(): void
    {
        User::factory()->create();
        Event::fake([ResourceWasCreated::class]);

        $disk = Storage::disk(config('app.disks.resources'));
        $filesBefore = $disk->allFiles();

        $pdf = PdfFile::factory()->withoutLocalFile()->create();

        $this->assertFalse($pdf->hasLocalFile());
        $this->assertSame($filesBefore, $disk->allFiles());
        Event::assertDispatched(ResourceWasCreated::class);
    }
}
