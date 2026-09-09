<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Change-control boundaries
    |--------------------------------------------------------------------------
    |
    | Boost may inspect this local development application, but it must not
    | execute arbitrary PHP or persist project rules on its own.
    |
    */

    'tinker_tool_enabled' => false,

    'rules' => [
        'enabled' => false,
        'scoped_guidelines' => false,
    ],

    'mcp' => [
        'tools' => [
            'exclude' => [
                Laravel\Boost\Mcp\Tools\BrowserLogs::class,
                Laravel\Boost\Mcp\Tools\RecordRule::class,
                Laravel\Boost\Mcp\Tools\Tinker::class,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Browser Log Watcher
    |--------------------------------------------------------------------------
    |
    | Keep Boost from injecting its browser logger into the legacy Vue frontend.
    | Enabling it requires a reviewed repository change.
    |
    */

    'browser_logs_watcher' => false,
];
