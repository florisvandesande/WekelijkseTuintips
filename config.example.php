<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => "Floris' tuintips",
        'base_url' => 'https://voorbeeld.nl',
        'base_path' => '/tuintips',
        'timezone' => 'Europe/Amsterdam',
        'ui_language' => 'nl',
        'supported_locales' => ['nl'],
        'allow_ui_language_switch' => false,
    ],
    'logging' => [
        'enabled' => true,
        'file' => 'data/logs/app.log',
    ],
    'weather' => [
        'enabled' => true,
        'provider_url' => 'https://api.open-meteo.com/v1/forecast',
        'request_timeout_seconds' => 8,
        'cache_ttl_seconds' => 1800,
        'forecast_days' => 4,
        'geolocation_enabled' => true,
        'fallback_location' => [
            'label' => 'Utrecht',
            'latitude' => 52.0907,
            'longitude' => 5.1214,
        ],
    ],
    'debug' => [
        'show_validator_in_browser' => false,
    ],
];
