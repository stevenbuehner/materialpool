<?php

namespace App\Services\ContextSearch;

use RuntimeException;

final class ContextSearchQueueSafety
{
    private const SAFETY_MARGIN_SECONDS = 60;

    public function assertConfigured(): void
    {
        $connectionName = (string) config('context_search.indexing.connection');
        $connection = config("queue.connections.{$connectionName}");

        if ($connectionName === 'database' || ! is_array($connection) || ($connection['driver'] ?? null) !== 'database' || ($connection['table'] ?? null) !== 'jobs') {
            throw new RuntimeException('Die Kontextsuche benötigt ihre eigene Datenbank-Queue-Connection.');
        }

        $queueNames = [
            (string) config('context_search.indexing.queue'),
            (string) config('context_search.indexing.ocr_calibration_queue'),
        ];

        if (count(array_unique($queueNames)) !== count($queueNames)) {
            throw new RuntimeException('Kontextsuche-Indexierung und OCR-Kalibrierung benötigen unterschiedliche Queue-Namen.');
        }

        foreach ($queueNames as $queueName) {
            if (! preg_match('/^context-search-[a-z0-9-]+$/', $queueName) || $queueName === 'context-search-indexing') {
                throw new RuntimeException('Der Kontextsuche-Queue-Name ist ungültig oder kollidiert mit der alten Queue.');
            }
        }

        $maximumTimeout = (int) config('context_search.indexing.maximum_job_timeout');
        $retryAfter = (int) ($connection['retry_after'] ?? 0);

        if ($maximumTimeout < 1 || $retryAfter <= $maximumTimeout + self::SAFETY_MARGIN_SECONDS) {
            throw new RuntimeException('Die Kontextsuche-Reservierungsfrist muss den maximalen Job-Timeout um mehr als 60 Sekunden übersteigen.');
        }
    }

    public function assertDispatchAllowed(): void
    {
        if (config('context_search.indexing.dispatch_enabled') !== true) {
            throw new RuntimeException('Kontextsuche-Jobs bleiben bis zur Abnahme der seitenweisen Verarbeitung gesperrt.');
        }

        $this->assertConfigured();
    }
}
