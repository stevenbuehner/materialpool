<?php

namespace Database\Factories\Concerns;

use App\Models\File;
use Illuminate\Support\Facades\Storage;

trait CreatesLocalFileFixture
{
	protected function localFixturePath(string $extension): string
	{
		return config('app.disks.resources').'::'.uniqid('testing/').'.'.$extension;
	}

	protected function withLocalFileFixture(string $fixture): static
	{
		return $this->afterMaking(function (File $resource) use ($fixture): void {
			if (!$resource->hasLocalFile()) {
				return;
			}

			$contents = Storage::disk(config('app.disks.testfiles'))->read($fixture);
			$resource->getLocalDisk()->write($resource->getLocalFilePath(), $contents);
		});
	}

	public function withoutLocalFile(): static
	{
		return $this->state(fn (array $attributes): array => [
			'local_path' => null,
		]);
	}
}
