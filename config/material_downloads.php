<?php

$yaml = @file_get_contents(__DIR__.'/material_downloads.yaml');
$days = is_string($yaml) && preg_match('/^default_retention_days:\s*([1-9][0-9]*)\s*$/m', $yaml, $matches)
    ? (int) $matches[1]
    : 28;

return [
    'default_retention_days' => min($days, 365),
    'root' => storage_path('app/material-downloads'),
];
