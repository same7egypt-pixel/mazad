<?php

$configuredOrigins = array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))));
$trialOriginsValue = (string) env(
    'TRIAL_PUBLIC_FRONTEND_ORIGINS',
    'https://mazad-marketplace.netlify.app,https://mazad-marketplace-web.onrender.com',
);
$trialPublicOrigins = array_values(array_filter(array_map('trim', explode(',', $trialOriginsValue))));
$allowedOrigins = array_values(array_unique([...$configuredOrigins, ...$trialPublicOrigins]));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
