<?php

namespace App\Console\Commands;

use App\Services\ContextSearch\ContextSearchQueueSafety;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CheckContextSearchQueue extends Command
{
    protected $signature = 'context-search:queue:check {--configuration-only : Nur die aufgelöste Konfiguration prüfen}';

    protected $description = 'Prüft lesend die vorbereitete Kontextsuche-Queue und inventarisiert Altaufträge.';

    public function handle(ContextSearchQueueSafety $safety): int
    {
        try {
            $safety->assertConfigured();

            if (! extension_loaded('pcntl')) {
                throw new \RuntimeException('Die PHP-CLI benötigt pcntl für sichere Queue-Job-Timeouts.');
            }
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Die getrennte Kontextsuche-Queue ist konfiguriert; die normale Queue bleibt unverändert.');

        if (! $this->option('configuration-only')) {
            try {
                $legacy = DB::table('jobs')->where('queue', 'context-search-indexing');
                $waiting = (clone $legacy)->whereNull('reserved_at')->count();
                $reserved = (clone $legacy)->whereNotNull('reserved_at')->count();
                $failed = DB::table('failed_jobs')->where('queue', 'context-search-indexing')->count();
            } catch (Throwable $exception) {
                $this->components->error('Das Altauftragsinventar konnte nicht gelesen werden; der Cutover bleibt gesperrt.');

                return self::FAILURE;
            }

            $this->line("Alte Kontextsuche-Queue: {$waiting} wartend, {$reserved} reserviert, {$failed} fehlgeschlagen.");

            if ($waiting > 0 || $reserved > 0 || $failed > 0) {
                $this->components->warn('Altaufträge vor dem Cutover einzeln fachlich prüfen; nichts pauschal löschen.');
            }
        }

        if (config('context_search.indexing.dispatch_enabled') !== true) {
            $this->components->warn('Neue Kontextsuche-Läufe bleiben bis Schritt 3 gesperrt. Keinen Kontextsuche-Worker starten.');
        }

        return self::SUCCESS;
    }
}
