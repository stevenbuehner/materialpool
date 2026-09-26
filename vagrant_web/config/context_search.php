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
        'digest' => env('CONTEXT_SEARCH_EMBEDDING_DIGEST'),
        'dimensions' => (int) env('CONTEXT_SEARCH_EMBEDDING_DIMENSIONS', 768),
        'timeout' => (int) env('CONTEXT_SEARCH_EMBEDDING_TIMEOUT', 30),
        'options_json' => env('CONTEXT_SEARCH_EMBEDDING_OPTIONS_JSON', '{}'),
    ],

    'ollama' => [
        'servers' => env('CONTEXT_SEARCH_OLLAMA_SERVERS', 'primary=http://host.docker.internal:11434|1'),
        'api_keys' => env('CONTEXT_SEARCH_OLLAMA_API_KEYS', ''),
        'connect_timeout' => (int) env('CONTEXT_SEARCH_OLLAMA_CONNECT_TIMEOUT', 2),
        'failure_threshold' => (int) env('CONTEXT_SEARCH_OLLAMA_FAILURE_THRESHOLD', 2),
        'circuit_cooldown' => (int) env('CONTEXT_SEARCH_OLLAMA_CIRCUIT_COOLDOWN', 60),
    ],

    'chunking' => [
        'version' => 'paragraph-sentence-v1',
        'target_characters' => (int) env('CONTEXT_SEARCH_CHUNK_TARGET_CHARACTERS', 1400),
        'overlap_characters' => (int) env('CONTEXT_SEARCH_CHUNK_OVERLAP_CHARACTERS', 200),
    ],

    'indexing' => [
        'queue' => env('CONTEXT_SEARCH_INDEXING_QUEUE', 'context-search-indexing'),
        'embedding_batch_size' => (int) env('CONTEXT_SEARCH_EMBEDDING_BATCH_SIZE', 8),
        'pdf_native_text_minimum_characters' => (int) env('CONTEXT_SEARCH_PDF_NATIVE_TEXT_MINIMUM_CHARACTERS', 80),
        'ocr_languages' => env('CONTEXT_SEARCH_OCR_LANGUAGES', 'deu+eng'),
        'ocr_timeout' => (int) env('CONTEXT_SEARCH_OCR_TIMEOUT', 120),
        'ocr_render_dpi' => (int) env('CONTEXT_SEARCH_OCR_RENDER_DPI', 200),
        'ocr_page_segmentation_mode' => (int) env('CONTEXT_SEARCH_OCR_PSM', 3),
        'ocr_engine_version' => env('CONTEXT_SEARCH_OCR_ENGINE_VERSION', 'tesseract-5'),
        'ocr_quality_profile' => env('CONTEXT_SEARCH_OCR_QUALITY_PROFILE', 'tesseract-de-en-v1'),
        'ocr_quality_minimum_mean_confidence' => (float) env('CONTEXT_SEARCH_OCR_MINIMUM_MEAN_CONFIDENCE', 0),
        'ocr_quality_minimum_recognized_words' => (int) env('CONTEXT_SEARCH_OCR_MINIMUM_RECOGNIZED_WORDS', 1),
        'ocr_quality_minimum_alphanumeric_ratio' => (float) env('CONTEXT_SEARCH_OCR_MINIMUM_ALPHANUMERIC_RATIO', 0),
        'ocr_quality_maximum_replacement_character_ratio' => (float) env('CONTEXT_SEARCH_OCR_MAXIMUM_REPLACEMENT_CHARACTER_RATIO', 0),
    ],

    'evaluation' => [
        'import_enabled' => env('CONTEXT_SEARCH_EVALUATION_IMPORT_ENABLED', false),
    ],

];
