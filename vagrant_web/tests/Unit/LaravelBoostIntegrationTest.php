<?php

namespace Tests\Unit;

use Laravel\Boost\Mcp\Tools\BrowserLogs;
use Laravel\Boost\Mcp\Tools\DatabaseQuery;
use Laravel\Boost\Mcp\Tools\RecordRule;
use Laravel\Boost\Mcp\Tools\Tinker;
use Tests\TestCase;

class LaravelBoostIntegrationTest extends TestCase
{
    public function test_boost_is_a_development_only_dependency(): void
    {
        $composer = json_decode(
            file_get_contents(base_path('composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertArrayNotHasKey('laravel/boost', $composer['require']);
        $expectedConstraint = app()->version() === '12.0.0' ? '1.0.21' : '^2.5';

        $this->assertSame($expectedConstraint, $composer['require-dev']['laravel/boost']);
    }

    public function test_codex_starts_boost_through_the_project_sail_runtime(): void
    {
        $configuration = file_get_contents(base_path('.codex/config.toml'));

        $this->assertStringContainsString('[mcp_servers.laravel-boost]', $configuration);
        $this->assertStringContainsString('command = "vendor/bin/sail"', $configuration);
        $this->assertStringContainsString('args = ["artisan", "boost:mcp"]', $configuration);
    }

    public function test_mutating_and_frontend_instrumentation_tools_remain_disabled(): void
    {
        $this->assertFalse(config('boost.browser_logs_watcher'));
        $this->assertFalse(config('boost.tinker_tool_enabled'));
        $this->assertFalse(config('boost.rules.enabled'));

        $excludedTools = config('boost.mcp.tools.exclude');

        $this->assertContains(BrowserLogs::class, $excludedTools);
        $this->assertContains(RecordRule::class, $excludedTools);
        $this->assertContains(Tinker::class, $excludedTools);

        if (app()->version() === '12.0.0') {
            $this->assertContains(DatabaseQuery::class, $excludedTools);
            $this->assertContains('Laravel\\Boost\\Mcp\\Tools\\GetConfig', $excludedTools);
            $this->assertContains('Laravel\\Boost\\Mcp\\Tools\\ListAvailableEnvVars', $excludedTools);
            $this->assertContains('Laravel\\Boost\\Mcp\\Tools\\ReportFeedback', $excludedTools);
        }
    }
}
