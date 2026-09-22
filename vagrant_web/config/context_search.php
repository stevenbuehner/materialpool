<?php

return [

    'enabled' => env('CONTEXT_SEARCH_ENABLED', false),

    'qdrant' => [
        'url' => env('QDRANT_URL', 'http://qdrant:6333'),
        'api_key' => env('QDRANT_API_KEY'),
        'connect_timeout' => (int) env('QDRANT_CONNECT_TIMEOUT', 2),
        'timeout' => (int) env('QDRANT_TIMEOUT', 10),
        'collection_prefix' => env('QDRANT_COLLECTION_PREFIX', 'materialpool_chunks'),
        'active_alias' => env('QDRANT_ACTIVE_ALIAS', 'materialpool_chunks_active'),
        'distance' => env('QDRANT_DISTANCE', 'Cosine'),
        'vectors_on_disk' => env('QDRANT_VECTORS_ON_DISK', true),
        'payload_on_disk' => env('QDRANT_PAYLOAD_ON_DISK', true),
    ],

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
