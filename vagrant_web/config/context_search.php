<?php

return [

    'enabled' => env('CONTEXT_SEARCH_ENABLED', false),

    'embedding' => [
        'provider' => 'ollama',
        'model' => env('CONTEXT_SEARCH_EMBEDDING_MODEL', 'embeddinggemma:300m-qat-q8_0'),
        'dimensions' => (int) env('CONTEXT_SEARCH_EMBEDDING_DIMENSIONS', 768),
        'timeout' => (int) env('CONTEXT_SEARCH_EMBEDDING_TIMEOUT', 30),
    ],

    'chunking' => [
        'version' => 'paragraph-sentence-v1',
        'target_characters' => (int) env('CONTEXT_SEARCH_CHUNK_TARGET_CHARACTERS', 1400),
        'overlap_characters' => (int) env('CONTEXT_SEARCH_CHUNK_OVERLAP_CHARACTERS', 200),
    ],

];
