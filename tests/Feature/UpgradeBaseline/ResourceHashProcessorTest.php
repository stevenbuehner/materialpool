<?php

namespace Tests\Feature\UpgradeBaseline;

use App\Models\File;
use App\Services\Processors\Exceptions\ResourceNotHashable;
use App\Services\Processors\ResourceHashProcessor;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceHashProcessorTest extends TestCase
{
    public function test_missing_local_file_is_reported_as_not_hashable(): void
    {
        $resource = new File();
        $resource->setAttribute(
            'local_path',
            config('app.disks.resources').'::missing.bin'
        );

        $this->expectException(ResourceNotHashable::class);

        app(ResourceHashProcessor::class)->updateResourceHash($resource);
    }

    public function test_local_file_hash_is_updated_from_the_stream(): void
    {
        Storage::disk(config('app.disks.resources'))->put('available.bin', 'seed fixture');

        $resource = new class extends File
        {
            public function save(array $options = []): bool
            {
                return true;
            }
        };
        $resource->setAttribute(
            'local_path',
            config('app.disks.resources').'::available.bin'
        );

        $changed = app(ResourceHashProcessor::class)->updateResourceHash($resource);

        $this->assertTrue($changed);
        $this->assertSame(sha1('seed fixture'), $resource->content_hash);
    }
}
