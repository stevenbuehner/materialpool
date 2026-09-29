<?php

namespace App\Console\Commands;

use App\Services\Bibles\Import\OpenBibleData;
use Illuminate\Console\Command;
use Throwable;

class PrepareBibleCrossReferences extends Command
{
    protected $signature = 'bible:prepare:cross-references';
    protected $description = 'OpenBible-Cross-References für ein Release aufbereiten';

    public function handle(OpenBibleData $data): int
    {
        try {
            $manifest = $data->prepare();
            $this->info("{$manifest['row_count']} Cross References vorbereitet; SHA-256 {$manifest['sha256']}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Aufbereitung fehlgeschlagen: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
