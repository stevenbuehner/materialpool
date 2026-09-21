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

	public function fromTestFile(string $fixture, ?string $originalFilename = NULL): static
	{
		return $this->state(function (array $attributes) use ($fixture, $originalFilename): array {
			return [
				'local_path' => $this->localFixturePath(pathinfo($fixture, PATHINFO_EXTENSION)),
				'original_filename' => $originalFilename ?? $fixture,
			];
		})->afterMaking(function (File $resource) use ($fixture): void {
			$contents = Storage::disk(config('app.disks.testfiles'))->read($fixture);
			$resource->getLocalDisk()->write($resource->getLocalFilePath(), $contents);
		});
	}

	public function withMissingLocalFile(string $originalFilename): static
	{
		return $this->state(function (array $attributes) use ($originalFilename): array {
			return [
				'local_path' => $this->localFixturePath(pathinfo($originalFilename, PATHINFO_EXTENSION)),
				'content_hash' => NULL,
				'original_filename' => $originalFilename,
			];
		})->afterMaking(function (File $resource): void {
			$resource->getLocalDisk()->delete($resource->getLocalFilePath());
		});
	}

	public function withoutLocalFile(): static
	{
		return $this->state(fn (array $attributes): array => [
			'local_path' => null,
		]);
	}
}
