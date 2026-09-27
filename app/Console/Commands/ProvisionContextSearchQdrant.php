<?php

namespace App\Console\Commands;

use App\Services\ContextSearch\Qdrant\QdrantCollectionProvisioner;
use Illuminate\Console\Command;
use Throwable;

final class ProvisionContextSearchQdrant extends Command
{
    protected $signature = 'context-search:qdrant:provision
        {profile : Unveränderlicher Profil-Hash}
        {generation : Technischer Generationsbezeichner}
        {--activate : Den aktiven Alias atomar auf diese Collection umschalten}';

    protected $description = 'Legt eine leere, versionierte Qdrant-Collection für die Kontextsuche an.';

    public function handle(QdrantCollectionProvisioner $provisioner): int
    {
        try {
            $collection = $provisioner->provision(
                (string) $this->argument('profile'),
                (string) $this->argument('generation'),
                (int) config('context_search.embedding.dimensions'),
            );

            if ($this->option('activate')) {
                $provisioner->activate($collection);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Qdrant-Collection {$collection} ist provisioniert.");

        if ($this->option('activate')) {
            $this->components->info('Der aktive Qdrant-Alias wurde umgeschaltet.');
        }

        return self::SUCCESS;
    }
}
