<?php

return [
    'currency_precision' => 2,
    'default_commission_rate' => 10.00,
    'auction' => [
        'ending_soon_minutes' => 15,
        'max_extension_seconds' => 120,
    ],
    'uploads' => [
        'max_image_kilobytes' => 10_240,
        'max_video_kilobytes' => 51_200,
        'allowed_image_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'allowed_video_mimes' => ['video/mp4', 'video/webm'],
    ],
    'roles' => [
        'GLOBAL_SUPER_ADMIN', 'COUNTRY_ADMIN', 'CITY_ADMIN', 'FINANCE_ADMIN',
        'OPERATIONS_ADMIN', 'CONTENT_MODERATOR', 'SUPPORT_AGENT', 'USER',
    ],
];
