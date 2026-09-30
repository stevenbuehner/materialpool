<?php

namespace Tests\Feature;

use App\Support\EnvironmentFile;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

final class ConfigureContextSearchCommandTest extends TestCase
{
    public function test_it_saves_the_discovered_digest_after_verifying_every_server(): void
    {
        $digest = str_repeat('a', 64);
        config()->set('context_search.enabled', false);
        config()->set('context_search.ollama.servers', '');
        config()->set('context_search.qdrant.api_key', '');
        config()->set('context_search.embedding.model', 'embeddinggemma:test');
        config()->set('context_search.embedding.dimensions', 2);
        config()->set('context_search.embedding.options_json', '{}');
        Http::preventStrayRequests();
        Http::fake([
            'qdrant.test/readyz' => Http::response('ok'),
            'qdrant.test/aliases' => Http::response(['result' => ['aliases' => []]]),
            'first.test/api/tags' => Http::response(['models' => [['name' => 'embeddinggemma:test', 'digest' => $digest]]]),
            'second.test/api/tags' => Http::response(['models' => [['name' => 'embeddinggemma:test', 'digest' => $digest]]]),
            'first.test/api/embed' => Http::response(['model' => 'embeddinggemma:test', 'embeddings' => [[0.1, 0.2]]]),
            'second.test/api/embed' => Http::response(['model' => 'embeddinggemma:test', 'embeddings' => [[0.1, 0.2]]]),
        ]);

        $writes = [];
        $environment = Mockery::mock(EnvironmentFile::class);
        $environment->shouldReceive('assertWritable')->andReturnNull();
        $environment->shouldReceive('write')->andReturnUsing(function (array $values) use (&$writes): void {
            $writes[] = $values;
        });
        $this->app->instance(EnvironmentFile::class, $environment);

        $this->artisan('context-search:configure')
            ->expectsConfirmation('Context-Suche aktivieren?', 'yes')
            ->expectsChoice('Aktion', 'Hinzufügen', ['Hinzufügen', 'Bearbeiten', 'Entfernen', 'Fertig'])
            ->expectsQuestion('Servername (klein, eindeutig)', 'first')
            ->expectsQuestion('URL für first', 'http://first.test')
            ->expectsQuestion('Maximale parallele Aufträge für first', '1')
            ->expectsQuestion('API-Key für first (leer: bisherigen behalten/kein Key)', '')
            ->expectsChoice('Aktion', 'Hinzufügen', ['Hinzufügen', 'Bearbeiten', 'Entfernen', 'Fertig'])
            ->expectsQuestion('Servername (klein, eindeutig)', 'second')
            ->expectsQuestion('URL für second', 'http://second.test')
            ->expectsQuestion('Maximale parallele Aufträge für second', '1')
            ->expectsQuestion('API-Key für second (leer: bisherigen behalten/kein Key)', '')
            ->expectsChoice('Aktion', 'Fertig', ['Hinzufügen', 'Bearbeiten', 'Entfernen', 'Fertig'])
            ->expectsQuestion('QDRANT_URL', 'http://qdrant.test')
            ->expectsQuestion('QDRANT_API_KEY', 'example-key')
            ->expectsQuestion('CONTEXT_SEARCH_EMBEDDING_MODEL', 'embeddinggemma:test')
            ->expectsQuestion('CONTEXT_SEARCH_EMBEDDING_DIMENSIONS', '2')
            ->expectsQuestion('CONTEXT_SEARCH_EMBEDDING_OPTIONS_JSON', '{}')
            ->assertExitCode(0);

        $this->assertSame($digest, $writes[array_key_last($writes)]['CONTEXT_SEARCH_EMBEDDING_DIGEST']);
        $this->assertSame('true', $writes[array_key_last($writes)]['CONTEXT_SEARCH_ENABLED']);
    }
}
