<?php

return [
    'driver' => env('SCOUT_DRIVER', 'database'),
    'queue' => [
        'connection' => env('SCOUT_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'queue' => env('SCOUT_QUEUE', 'default'),
    ],
    'after_commit' => true,
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            'products' => [
                'filterableAttributes' => ['country_id', 'city_id', 'category_id', 'currency_id', 'condition', 'status'],
                'searchableAttributes' => ['title', 'description'],
                'sortableAttributes' => ['id', 'created_at'],
            ],
        ],
    ],
];
