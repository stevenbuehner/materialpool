<?php

namespace Tests\Feature\Admin;

use App\Services\Bibles\Import\OpenBibleData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class InstallationStatusTest extends TestCase
{
    use RefreshDatabase;

    public static function insecureHttpsConfigurations(): array
    {
        return [
            'HTTPS-URL' => ['https://pool.example.test', null],
            'Trusted Proxy' => ['http://pool.example.test', '192.0.2.1/32'],
        ];
    }

    #[DataProvider('insecureHttpsConfigurations')]
    public function test_status_warns_in_red_when_secure_session_cookies_are_disabled(string $url, ?string $proxies): void
    {
        config([
            'app.url' => $url,
            'trustedproxy.proxies' => $proxies,
            'session.secure' => false,
            'backup.enabled' => false,
            'context_search.enabled' => false,
        ]);
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);

        $exitCode = Artisan::call('materialpool:status', ['--latest-version' => '1.2.3'], $output);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString("\033[31m! SESSION_SECURE_COOKIE=false:", $output->fetch());
    }

    public function test_status_does_not_warn_when_secure_cookies_are_enabled_or_http_has_no_proxy(): void
    {
        config([
            'app.url' => 'https://pool.example.test',
            'trustedproxy.proxies' => '192.0.2.1/32',
            'session.secure' => true,
            'backup.enabled' => false,
            'context_search.enabled' => false,
        ]);

        $this->assertSame(0, Artisan::call('materialpool:status', ['--latest-version' => '1.2.3']));
        $this->assertStringNotContainsString('SESSION_SECURE_COOKIE=false', Artisan::output());

        config(['app.url' => 'http://pool.example.test', 'trustedproxy.proxies' => null, 'session.secure' => false]);

        $this->assertSame(0, Artisan::call('materialpool:status', ['--latest-version' => '1.2.3']));
        $this->assertStringNotContainsString('SESSION_SECURE_COOKIE=false', Artisan::output());
    }

    public function test_status_shows_installed_data_and_current_version_without_exposing_qdrant_key(): void
    {
        File::shouldReceive('exists')->once()->with(base_path('release.json'))->andReturn(true);
        File::shouldReceive('get')->once()->with(base_path('release.json'))->andReturn('{"version":"1.2.3"}');
        DB::table('materials')->insert(['title' => 'Testmaterial', 'from_bot' => false, 'created_by' => 1, 'modified_by' => 1]);
        DB::table('resources')->insert(['created_by' => 1, 'options' => '{}', 'is_public' => true, 'type' => 'res']);
        DB::table('keywords')->insert(['title' => 'Test', 'lc_title' => 'test', 'type' => 'key', '_lft' => 1, '_rgt' => 2]);
        DB::table('bibleverses')->insert(['from' => 1001001, 'to' => 1001001]);
        DB::table('bible_data_imports')->insert([
            'dataset_id' => OpenBibleData::ID, 'kind' => 'cross-references', 'title' => 'OpenBible',
            'source_url' => 'https://example.test', 'normalization_version' => 1, 'row_count' => 123,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('bible_data_imports')->insert([
            'dataset_id' => 'scrollmapper:demo', 'kind' => 'translation', 'title' => 'Demo-Übersetzung',
            'source_url' => 'https://example.test', 'normalization_version' => 1, 'row_count' => 12,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        config([
            'app.name' => 'Materialpool', 'app.url' => 'https://pool.example.test',
            'trustedproxy.proxies' => '192.0.2.1/32', 'context_search.enabled' => true,
            'context_search.qdrant.url' => 'http://qdrant.test:6333',
            'context_search.qdrant.api_key' => 'geheim', 'backup.enabled' => false,
        ]);
        Http::preventStrayRequests();

        $exitCode = Artisan::call('materialpool:status', ['--latest-version' => '1.2.3']);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Materialpool 1.2.3', $output);
        $this->assertStringContainsString('Update-Status: aktuell', $output);
        $this->assertStringContainsString('URL: https://pool.example.test', $output);
        $this->assertStringContainsString('Trusted Proxy: 192.0.2.1/32', $output);
        $this->assertStringContainsString('URL: http://qdrant.test:6333', $output);
        $this->assertStringContainsString('API-Key konfiguriert: ja', $output);
        $this->assertStringNotContainsString('geheim', $output);
        $this->assertStringContainsString('Datenbank: ok', $output);
        $this->assertStringContainsString('Materialien: 1', $output);
        $this->assertStringContainsString('Ressourcen: 1', $output);
        $this->assertMatchesRegularExpression('/Keywords: [1-9][0-9]*/', $output);
        $this->assertMatchesRegularExpression('/Bibelstellen: [1-9][0-9]*/', $output);
        $this->assertStringContainsString('Querverweise: installiert (123)', $output);
        $this->assertStringContainsString('Demo-Übersetzung', $output);
        $this->assertStringContainsString('Nicht konfiguriert', $output);
        Http::assertNothingSent();
    }

    public function test_status_reports_available_update_and_disabled_optional_services(): void
    {
        File::shouldReceive('exists')->once()->with(base_path('release.json'))->andReturn(true);
        File::shouldReceive('get')->once()->with(base_path('release.json'))->andReturn('{"version":"1.2.3"}');
        config(['trustedproxy.proxies' => null, 'context_search.enabled' => false, 'backup.enabled' => false]);
        Http::preventStrayRequests();
        Http::fake(['api.github.com/repos/stevenbuehner/materialpool/releases/latest' => Http::response([
            'tag_name' => '9.9.9', 'draft' => false, 'prerelease' => false,
        ])]);

        $exitCode = Artisan::call('materialpool:status');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Update verfügbar: 9.9.9', $output);
        $this->assertStringNotContainsString('Trusted Proxy:', $output);
        $this->assertStringContainsString('Kontextsuche in der Konfiguration deaktiviert', $output);
    }

    public function test_configured_backup_uses_package_list_command(): void
    {
        config([
            'backup.enabled' => true,
            'backup.monitor_backups' => [[
                'name' => config('app.name'), 'disks' => ['backup'], 'health_checks' => [],
            ]],
        ]);
        Storage::fake('backup');
        Http::preventStrayRequests();

        $exitCode = Artisan::call('materialpool:status', ['--latest-version' => '1.2.3']);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Backup', $output);
        $this->assertStringContainsString('Newest backup', $output);
        $this->assertStringNotContainsString('Nicht konfiguriert', $output);
        Http::assertNothingSent();
    }

    public function test_status_keeps_local_sections_when_release_lookup_fails(): void
    {
        config(['backup.enabled' => false, 'context_search.enabled' => false]);
        Http::preventStrayRequests();
        Http::fake(['api.github.com/repos/stevenbuehner/materialpool/releases/latest' => Http::response([], 503)]);

        $exitCode = Artisan::call('materialpool:status');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Update-Status: nicht abrufbar', $output);
        $this->assertStringContainsString('Datenbank: ok', $output);
        $this->assertStringContainsString('Backup', $output);
    }
}
