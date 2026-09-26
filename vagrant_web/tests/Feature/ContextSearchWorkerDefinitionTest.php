<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ContextSearchWorkerDefinitionTest extends TestCase
{
    public function test_context_workers_are_prepared_but_cannot_start_automatically(): void
    {
        $path = base_path('ops/production/materialpool-context-search-workers.conf.example');
        $definitions = parse_ini_file($path, true, INI_SCANNER_RAW);

        $this->assertIsArray($definitions);
        $this->assertSame(['program:materialpool-context-ocr', 'program:materialpool-context-embedding'], array_keys($definitions));

        foreach ($definitions as $definition) {
            $this->assertSame('false', $definition['autostart']);
            $this->assertSame('1', $definition['numprocs']);
            $this->assertSame('540', $definition['stopwaitsecs']);
            $this->assertStringContainsString('queue:work context_search', $definition['command']);
            $this->assertStringContainsString('--timeout=480', $definition['command']);
            $this->assertStringNotContainsString('resource-previews-low', $definition['command']);
        }

        $this->assertStringContainsString('--queue=context-search-calibration-ocr,context-search-extraction', $definitions['program:materialpool-context-ocr']['command']);
        $this->assertStringContainsString('--queue=context-search-upsert,context-search-embedding', $definitions['program:materialpool-context-embedding']['command']);
        $this->assertSame(600, (int) config('queue.connections.context_search.retry_after'));
        $this->assertSame(false, config('context_search.indexing.dispatch_enabled'));
    }
}
