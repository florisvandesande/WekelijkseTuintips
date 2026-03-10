<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$coordinates = app_sanitize_coordinates($_GET['lat'] ?? null, $_GET['lon'] ?? null);

if ($coordinates === null) {
    http_response_code(400);
    echo json_encode([
        'error' => [
            'code' => 'invalid_coordinates',
            'message' => 'Coordinates must be numeric and within valid latitude/longitude ranges.',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$location_label = isset($_GET['location_label']) ? trim((string) $_GET['location_label']) : t('weather_your_location');
$location_label = $location_label !== '' ? $location_label : t('weather_your_location');

$weather = weather_get_header_forecast($coordinates['lat'], $coordinates['lon'], $location_label);

if (!empty($weather['error'])) {
    http_response_code(502);
    echo json_encode([
        'error' => [
            'code' => 'weather_unavailable',
            'message' => (string) $weather['error'],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'data' => [
        'location' => (string) ($weather['location'] ?? ''),
        'buienradar_url' => (string) ($weather['buienradar_url'] ?? 'https://www.buienradar.nl/'),
        'days' => $weather['days'] ?? [],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
