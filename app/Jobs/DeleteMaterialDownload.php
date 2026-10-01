<?php

namespace App\Jobs;

use App\Services\MaterialHandling\MaterialDownloadStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteMaterialDownload implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $token) {
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function handle(MaterialDownloadStore $store): void {
        $store->delete($this->token);
    }
}
