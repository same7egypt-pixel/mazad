<?php

return [
    'currency_precision' => 2,
    'default_commission_rate' => 10.00,
    'payment_gateways' => [
        // 'SA' => App\Domain\Payments\Gateways\ExampleGateway::class,
    ],
    'hmac_webhook' => [
        'gateway_name' => env('PAYMENT_HMAC_GATEWAY_NAME', 'generic-hmac'),
        'secret' => env('PAYMENT_HMAC_WEBHOOK_SECRET'),
        'signature_header' => env('PAYMENT_HMAC_SIGNATURE_HEADER', 'x-payment-signature'),
    ],
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
    'first_admin' => [
        'enabled' => env('FIRST_ADMIN_ENABLED', false),
        'name' => env('FIRST_ADMIN_NAME'),
        'email' => env('FIRST_ADMIN_EMAIL'),
        'password' => env('FIRST_ADMIN_PASSWORD'),
    ],
];
