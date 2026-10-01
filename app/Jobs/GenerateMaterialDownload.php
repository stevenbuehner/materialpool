<?php

namespace App\Jobs;

use App\Models\Material;
use App\Models\User;
use App\Services\MaterialHandling\MaterialDownloadArchive;
use App\Services\MaterialHandling\MaterialDownloadStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class GenerateMaterialDownload implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        public readonly int $materialId,
        public readonly int $userId,
        public readonly string $token,
    ) {
        $this->onConnection('material_downloads');
        $this->onQueue('material-downloads');
    }

    public function handle(MaterialDownloadArchive $archive, MaterialDownloadStore $store): void {
        $user = User::findOrFail($this->userId);
        $material = Material::findOrFail($this->materialId);
        if (!$user->can('view', $material)) {
            throw new RuntimeException('Material is no longer readable.');
        }
        $status = $store->read($this->token);
        if ($status === NULL || $status['status'] === 'ready' || now()->greaterThanOrEqualTo($status['until'])) {
            return;
        }
        $store->setStatus($this->token, 'building');
        $archive->build($material, $user, $this->token);
    }

    public function failed(?Throwable $exception): void {
        $store = app(MaterialDownloadStore::class);
        if ($store->read($this->token) !== NULL) {
            $store->setStatus($this->token, 'failed');
        }
    }
}
