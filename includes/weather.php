<?php

declare(strict_types=1);

/**
 * Return weather forecast for header (today + next days) using fallback or provided coordinates.
 */
function weather_get_header_forecast(?float $latitude = null, ?float $longitude = null, ?string $location_label = null): array
{
    $weather_config = (array) app_array_get(app_config(), 'weather', []);

    if (($weather_config['enabled'] ?? false) !== true) {
        return [
            'enabled' => false,
            'location' => '',
            'days' => [],
            'buienradar_url' => 'https://www.buienradar.nl/',
            'error' => t('weather_unavailable'),
        ];
    }

    if ($latitude === null || $longitude === null) {
        $latitude = (float) app_array_get($weather_config, 'fallback_location.latitude', 52.0907);
        $longitude = (float) app_array_get($weather_config, 'fallback_location.longitude', 5.1214);
    }

    if ($location_label === null || trim($location_label) === '') {
        $location_label = (string) app_array_get($weather_config, 'fallback_location.label', 'Onbekende locatie');
    }

    $cache_key = sprintf('weather_%s_%s', number_format($latitude, 4, '_', ''), number_format($longitude, 4, '_', ''));
    $cache_file = app_path('data/cache/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cache_key) . '.json');
    $cache_ttl = (int) ($weather_config['cache_ttl_seconds'] ?? 1800);

    if (is_file($cache_file) && (time() - filemtime($cache_file)) <= $cache_ttl) {
        $cached = file_get_contents($cache_file);

        if (is_string($cached)) {
            $decoded = json_decode($cached, true);

            if (is_array($decoded)) {
                $decoded['days'] = weather_enrich_forecast_days(is_array($decoded['days'] ?? null) ? $decoded['days'] : []);
                $decoded['from_cache'] = true;
                return $decoded;
            }
        }
    }

    $request_url = weather_build_provider_url($latitude, $longitude, $weather_config);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => (int) ($weather_config['request_timeout_seconds'] ?? 8),
        ],
    ]);

    $response = @file_get_contents($request_url, false, $context);

    if (!is_string($response)) {
        app_log('error', 'Weather API request failed.', ['url' => $request_url]);
        return [
            'enabled' => true,
            'location' => $location_label,
            'days' => [],
            'buienradar_url' => weather_buienradar_url($latitude, $longitude),
            'error' => t('weather_unavailable'),
        ];
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded) || !isset($decoded['daily']) || !is_array($decoded['daily'])) {
        app_log('error', 'Weather API returned invalid payload.', ['payload' => $response]);
        return [
            'enabled' => true,
            'location' => $location_label,
            'days' => [],
            'buienradar_url' => weather_buienradar_url($latitude, $longitude),
            'error' => t('weather_unavailable'),
        ];
    }

    $forecast = weather_normalize_daily_forecast($decoded['daily']);

    $result = [
        'enabled' => true,
        'location' => $location_label,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'days' => weather_enrich_forecast_days($forecast),
        'buienradar_url' => weather_buienradar_url($latitude, $longitude),
        'error' => null,
    ];

    @file_put_contents($cache_file, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $result;
}

/**
 * Build weather provider URL with required parameters.
 */
function weather_build_provider_url(float $latitude, float $longitude, array $weather_config): string
{
    $base_url = (string) ($weather_config['provider_url'] ?? 'https://api.open-meteo.com/v1/forecast');
    $forecast_days = (int) ($weather_config['forecast_days'] ?? 4);

    $query = [
        'latitude' => $latitude,
        'longitude' => $longitude,
        'daily' => 'weathercode,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
        'timezone' => (string) app_array_get(app_config(), 'app.timezone', 'Europe/Amsterdam'),
        'forecast_days' => max(1, min(7, $forecast_days)),
    ];

    return $base_url . '?' . http_build_query($query);
}

/**
 * Normalize provider daily payload to a stable structure for templates and API.
 */
function weather_normalize_daily_forecast(array $daily): array
{
    $dates = $daily['time'] ?? [];
    $max_values = $daily['temperature_2m_max'] ?? [];
    $min_values = $daily['temperature_2m_min'] ?? [];
    $codes = $daily['weathercode'] ?? [];
    $precipitation = $daily['precipitation_probability_max'] ?? [];

    $count = min(count($dates), count($max_values), count($min_values), count($codes));
    $result = [];

    for ($index = 0; $index < $count; $index++) {
        $date_string = (string) $dates[$index];
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $date_string) ?: new DateTimeImmutable('now');

        $result[] = [
            'date' => $date_string,
            'day_label' => app_format_date_dutch($date, false),
            'weekday' => app_weekday_dutch($date),
            'max_temp' => (float) $max_values[$index],
            'min_temp' => (float) $min_values[$index],
            'weather_code' => (int) $codes[$index],
            'weather_text' => weather_code_to_text((int) $codes[$index]),
            'weather_icon' => weather_code_to_icon((int) $codes[$index]),
            'precipitation_probability' => isset($precipitation[$index]) ? (int) $precipitation[$index] : null,
        ];
    }

    return $result;
}

/**
 * Map Open-Meteo weather code to Dutch text.
 */
function weather_code_to_text(int $code): string
{
    return match (true) {
        $code === 0 => 'Helder',
        in_array($code, [1, 2], true) => 'Licht bewolkt',
        $code === 3 => 'Bewolkt',
        in_array($code, [45, 48], true) => 'Mistig',
        in_array($code, [51, 53, 55, 56, 57], true) => 'Motregen',
        in_array($code, [61, 63, 65, 66, 67], true) => 'Regen',
        in_array($code, [71, 73, 75, 77], true) => 'Sneeuw',
        in_array($code, [80, 81, 82], true) => 'Buien',
        in_array($code, [85, 86], true) => 'Sneeuwbuien',
        in_array($code, [95, 96, 99], true) => 'Onweer',
        default => 'Wisselvallig',
    };
}

/**
 * Map Open-Meteo weather code to a compact weather icon.
 */
function weather_code_to_icon(int $code): string
{
    return match (true) {
        $code === 0 => '☼',
        in_array($code, [1, 2], true) => '⛅',
        $code === 3 => '☁',
        in_array($code, [45, 48], true) => '≋',
        in_array($code, [51, 53, 55, 56, 57], true) => '⛆',
        in_array($code, [61, 63, 65, 66, 67], true) => '☂',
        in_array($code, [71, 73, 75, 77], true) => '❄',
        in_array($code, [80, 81, 82], true) => '⛆',
        in_array($code, [85, 86], true) => '❄',
        in_array($code, [95, 96, 99], true) => '⚡',
        default => '☁',
    };
}

/**
 * Ensure normalized weather fields exist in cached and live forecast payloads.
 */
function weather_enrich_forecast_days(array $days): array
{
    $result = [];

    foreach ($days as $day) {
        if (!is_array($day)) {
            continue;
        }

        $code = isset($day['weather_code']) ? (int) $day['weather_code'] : 3;

        $date = null;
        if (is_string($day['date'] ?? null) && $day['date'] !== '') {
            $date = DateTimeImmutable::createFromFormat('Y-m-d', (string) $day['date']) ?: null;
        }

        $day['weekday'] = is_string($day['weekday'] ?? null) && $day['weekday'] !== ''
            ? (string) $day['weekday']
            : ($date instanceof DateTimeImmutable ? app_weekday_dutch($date) : '');

        $day['weather_icon'] = is_string($day['weather_icon'] ?? null) && $day['weather_icon'] !== ''
            ? (string) $day['weather_icon']
            : weather_code_to_icon($code);

        $result[] = $day;
    }

    return $result;
}

/**
 * Build Buienradar URL with optional coordinate hint.
 */
function weather_buienradar_url(float $latitude, float $longitude): string
{
    return 'https://www.buienradar.nl/';
}
