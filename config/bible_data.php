<?php

return [
    'suggested_translations' => [
        'scrollmapper:GerElb1905',
        'scrollmapper:GerSch',
    ],
    'scrollmapper_repository' => 'scrollmapper/bible_databases',
    'openbible_url' => 'https://a.openbible.info/data/cross-references.zip',
    'max_translation_bytes' => 32 * 1024 * 1024,
    'max_cross_reference_bytes' => 32 * 1024 * 1024,
    'minimum_retained_fraction' => 0.8,
    'lock_name' => 'materialpool:bible-data-import',
];
