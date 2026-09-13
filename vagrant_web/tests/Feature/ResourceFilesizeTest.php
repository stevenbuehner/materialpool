<?php

namespace Tests\Feature;

use App\Events\ResourceWasChanged;
use App\Events\ResourceWasCreated;
use App\Models\Book;
use App\Models\File;
use App\Models\Text;
use App\Models\Url;
use App\Models\User;
use App\Services\Processors\ResourceFilesizeProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceFilesizeTest extends TestCase {
	use RefreshDatabase;

	public function test_local_file_size_is_persisted_and_reads_do_not_access_storage(): void {
		$file = $this->file('testing/payload.bin', 'persisted payload');
		event(new ResourceWasCreated($file));

		$storedFilesize = strlen('persisted payload');
		$this->assertSame($storedFilesize, $file->filesize);
		$this->assertSame($storedFilesize, $file->fresh()->filesize);

		Storage::disk(config('app.disks.resources'))->delete('testing/payload.bin');
		Log::shouldReceive('warning')->never();

		$this->assertSame($storedFilesize, $file->fresh()->toArray()['filesize']);
	}

	public function test_empty_local_file_has_a_valid_zero_size(): void {
		$file = $this->file('testing/empty.bin', '');

		resolve(ResourceFilesizeProcessor::class)->updateResourceFilesize($file);

		$this->assertSame(0, $file->fresh()->filesize);
	}

	public function test_multibyte_text_size_is_updated_without_touching_updated_at(): void {
		$text = new Text();
		$text->created_by = User::factory()->create()->id;
		$text->setContent('Grüße 🌍');
		$text->save();
		event(new ResourceWasCreated($text));

		$this->assertSame(strlen('Grüße 🌍'), $text->fresh()->filesize);

		$text = $text->fresh();
		$text->setContent('Geänderter Inhalt');
		$text->save();
		$updatedAt = $text->updated_at->toISOString();
		event(new ResourceWasChanged($text));

		$text = $text->fresh();
		$this->assertSame(strlen('Geänderter Inhalt'), $text->filesize);
		$this->assertSame($updatedAt, $text->updated_at->toISOString());
	}

	public function test_remote_and_non_payload_resources_have_an_explicit_null_size(): void {
		$user = User::factory()->create();
		$resources = [new File(), new Url(), new Book()];

		foreach ($resources as $resource) {
			$resource->created_by = $user->id;
			$resource->remote_path = 'https://example.com/resource';
			$resource->save();
			event(new ResourceWasCreated($resource));

			$this->assertArrayHasKey('filesize', $resource->toArray());
			$this->assertNull($resource->toArray()['filesize']);
			$this->assertNull($resource->fresh()->filesize);
		}
	}

	public function test_missing_local_file_is_stored_as_null_and_logged_during_update_only(): void {
		$file = $this->file('testing/missing.bin', NULL);
		Log::shouldReceive('warning')
			->once()
			->with('Unable to retrieve local file metadata.', [
				'resource_id' => $file->id,
				'metadata'    => 'file_size',
			]);

		resolve(ResourceFilesizeProcessor::class)->updateResourceFilesize($file);

		$this->assertNull($file->fresh()->filesize);
		$this->assertNull($file->fresh()->toArray()['filesize']);
	}

	private function file(string $path, ?string $contents): File {
		if ($contents !== NULL) {
			Storage::disk(config('app.disks.resources'))->put($path, $contents);
		}

		$file = new File();
		$file->created_by = User::factory()->create()->id;
		$file->setLocalStorageAndPath(config('app.disks.resources'), $path);
		$file->save();

		return $file;
	}
}
