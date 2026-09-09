<?php

return [
    // Comma-separated IP addresses or CIDR ranges. Wildcards are rejected by
    // App\Http\Middleware\TrustProxies so clients cannot forge proxy headers.
    'proxies' => env('TRUSTED_PROXIES'),
];
