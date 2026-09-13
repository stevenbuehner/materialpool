<?php

namespace Tests\Feature;

use App\Jobs\UpdateResourceFilesizesBatch;
use App\Models\File;
use App\Models\Resource;
use App\Models\Text;
use App\Models\Url;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceFilesizeBackfillTest extends TestCase {
	use RefreshDatabase;

	public function test_command_queues_only_eligible_resources_in_bounded_batches(): void {
		Queue::fake();
		$user = User::factory()->create();

		for ($index = 0; $index < 5; $index++) {
			$text = new Text();
			$text->created_by = $user->id;
			$text->setContent("Text {$index}");
			$text->save();
		}

		$remote = new Url();
		$remote->created_by = $user->id;
		$remote->setContent('https://example.com/remote');
		$remote->save();

		$localFile = new File();
		$localFile->created_by = $user->id;
		$localFile->setLocalStorageAndPath(config('app.disks.resources'), 'testing/local.bin');
		$localFile->save();

		$remoteFile = new File();
		$remoteFile->created_by = $user->id;
		$remoteFile->remote_path = 'https://example.com/remote.bin';
		$remoteFile->save();

		$this->artisan('resources:backfill-filesizes', ['--chunk' => 2])
			->expectsOutputToContain('6 Ressourcen in 3 Batch-Jobs eingeplant.')
			->assertSuccessful();

		Queue::assertPushed(UpdateResourceFilesizesBatch::class, 3);
		Queue::assertPushedOn('default', UpdateResourceFilesizesBatch::class);
		Queue::assertPushed(UpdateResourceFilesizesBatch::class, function (UpdateResourceFilesizesBatch $job): bool {
			return $job->connection === 'database';
		});
	}

	public function test_batch_job_is_idempotent_and_force_recalculates_existing_values(): void {
		$user = User::factory()->create();
		$path = 'testing/backfill.bin';
		Storage::disk(config('app.disks.resources'))->put($path, 'new payload');

		$file = new File();
		$file->created_by = $user->id;
		$file->setLocalStorageAndPath(config('app.disks.resources'), $path);
		$file->save();
		Resource::query()->whereKey($file->id)->update(['filesize' => 1]);

		(new UpdateResourceFilesizesBatch([$file->id]))->handle(resolve(\App\Services\Processors\ResourceFilesizeProcessor::class));
		$this->assertSame(1, $file->fresh()->filesize);

		(new UpdateResourceFilesizesBatch([$file->id], TRUE))->handle(resolve(\App\Services\Processors\ResourceFilesizeProcessor::class));
		$this->assertSame(strlen('new payload'), $file->fresh()->filesize);
	}

	public function test_command_rejects_unsafe_batch_sizes(): void {
		$this->artisan('resources:backfill-filesizes', ['--chunk' => 0])->assertExitCode(2);
		$this->artisan('resources:backfill-filesizes', ['--chunk' => 1001])->assertExitCode(2);
	}
}
