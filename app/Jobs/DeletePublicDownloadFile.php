<?php

namespace App\Jobs;

use DateInterval;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeletePublicDownloadFile implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected string $filePath;

	/**
	 * CheckLonelyResource constructor.
	 *
	 * @param String $filePath
	 * @param DateTimeInterface|DateInterval|int|null $delay
	 */
	public function __construct(string $filePath, $delay) {

		$this->filePath = $filePath;
		$this->onConnection('database');
		$this->delay($delay);

	}

	/**
	 * Execute the job.
	 *
	 */
	public function handle() {

		$path = public_path($this->filePath);

		if (file_exists($path)) {
			unlink($path);
		}

	}
}
