<?php

declare(strict_types=1);

return [

    'url' => env('QDRANT_URL', 'http://localhost:6333'),

    'api_key' => env('QDRANT_API_KEY'),

    'timeout' => [
        'connect' => (int) env('QDRANT_CONNECT_TIMEOUT', 10),
        'request' => (int) env('QDRANT_REQUEST_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Configuration
    |--------------------------------------------------------------------------
    |
    | Provider: ollama, openai, none
    | Model: provider-specific model name (e.g. bge-large, text-embedding-3-large)
    |
    */

    'embeddings' => [
        'provider' => env('EMBEDDING_PROVIDER', 'ollama'),
        'model' => env('EMBEDDING_MODEL', 'bge-large'),
        'url' => env('EMBEDDING_URL'),
        'api_key' => env('EMBEDDING_API_KEY'),
        'dimensions' => env('EMBEDDING_DIMENSIONS') ? (int) env('EMBEDDING_DIMENSIONS') : null,
    ],

];
