<?php

declare(strict_types=1);

/**
 * Return weather forecast for header (today + next days) using fallback or provided coordinates.
 */
function weather_get_header_forecast(?float $latitude = null, ?float $longitude = null, ?string $location_label = null): array
{
    $weather_config = (array) app_array_get(app_config(), 'weather', []);
    $has_explicit_coordinates = $latitude !== null && $longitude !== null;

    if (($weather_config['enabled'] ?? false) !== true) {
        return [
            'enabled' => false,
            'location' => '',
            'days' => [],
            'buienradar_url' => 'https://www.buienradar.nl/',
            'error' => t('weather_unavailable'),
        ];
    }

    if (!$has_explicit_coordinates) {
        $latitude = (float) app_array_get($weather_config, 'fallback_location.latitude', 52.0907);
        $longitude = (float) app_array_get($weather_config, 'fallback_location.longitude', 5.1214);
    }

    if ($location_label === null || trim($location_label) === '') {
        $location_label = (string) app_array_get($weather_config, 'fallback_location.label', 'Onbekende locatie');
    }

    $coordinate_source = $has_explicit_coordinates ? 'user' : 'fallback';
    $cache_key = sprintf(
        'weather_%s_%s_%s',
        number_format($latitude, 4, '_', ''),
        number_format($longitude, 4, '_', ''),
        $coordinate_source
    );
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
            'buienradar_url' => weather_buienradar_url($latitude, $longitude, $has_explicit_coordinates),
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
            'buienradar_url' => weather_buienradar_url($latitude, $longitude, $has_explicit_coordinates),
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
        'buienradar_url' => weather_buienradar_url($latitude, $longitude, $has_explicit_coordinates),
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
            'weather_icon_slug' => weather_code_to_icon_slug((int) $codes[$index]),
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
 * Return supported ErikFlowers weather icon slugs.
 */
function weather_supported_icon_slugs(): array
{
    static $slugs = [
        'wi-day-sunny',
        'wi-day-cloudy',
        'wi-cloudy',
        'wi-fog',
        'wi-sprinkle',
        'wi-rain',
        'wi-snow',
        'wi-showers',
        'wi-thunderstorm',
    ];

    return $slugs;
}

/**
 * Normalize a weather icon slug to the supported local icon set.
 */
function weather_normalize_icon_slug(mixed $icon_slug): string
{
    if (is_string($icon_slug) && in_array($icon_slug, weather_supported_icon_slugs(), true)) {
        return $icon_slug;
    }

    return 'wi-cloudy';
}

/**
 * Map Open-Meteo weather code to a local ErikFlowers icon slug.
 */
function weather_code_to_icon_slug(int $code): string
{
    return match (true) {
        $code === 0 => 'wi-day-sunny',
        in_array($code, [1, 2], true) => 'wi-day-cloudy',
        $code === 3 => 'wi-cloudy',
        in_array($code, [45, 48], true) => 'wi-fog',
        in_array($code, [51, 53, 55, 56, 57], true) => 'wi-sprinkle',
        in_array($code, [61, 63, 65, 66, 67], true) => 'wi-rain',
        in_array($code, [71, 73, 75, 77, 85, 86], true) => 'wi-snow',
        in_array($code, [80, 81, 82], true) => 'wi-showers',
        in_array($code, [95, 96, 99], true) => 'wi-thunderstorm',
        default => 'wi-cloudy',
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

        $day['weather_icon_slug'] = is_string($day['weather_icon_slug'] ?? null) && $day['weather_icon_slug'] !== ''
            ? weather_normalize_icon_slug($day['weather_icon_slug'])
            : weather_code_to_icon_slug($code);

        $result[] = $day;
    }

    return $result;
}

/**
 * Build Buienradar URL for explicit user coordinates.
 */
function weather_buienradar_url(float $latitude, float $longitude, bool $has_explicit_coordinates = false): string
{
    if (!$has_explicit_coordinates) {
        return 'https://www.buienradar.nl/';
    }

    $location = weather_buienradar_location_from_coordinates($latitude, $longitude);

    if ($location === null) {
        return 'https://www.buienradar.nl/';
    }

    $place_url = weather_buienradar_place_url($location);

    return $place_url ?? 'https://www.buienradar.nl/';
}

/**
 * Resolve Buienradar place metadata from latitude/longitude.
 */
function weather_buienradar_location_from_coordinates(float $latitude, float $longitude): ?array
{
    $weather_config = (array) app_array_get(app_config(), 'weather', []);
    $timeout_seconds = (int) ($weather_config['request_timeout_seconds'] ?? 8);
    $timeout_seconds = max(2, min(10, $timeout_seconds));

    $query = [
        'lat' => number_format($latitude, 2, '.', ''),
        'lon' => number_format($longitude, 2, '.', ''),
    ];

    $request_url = 'https://location.buienradar.nl/1.1/location/geo?' . http_build_query($query);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout_seconds,
        ],
    ]);

    $response = @file_get_contents($request_url, false, $context);

    if (!is_string($response)) {
        app_log('error', 'Buienradar location request failed.', ['url' => $request_url]);
        return null;
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        app_log('error', 'Buienradar location payload is not valid JSON.', ['payload' => $response]);
        return null;
    }

    if (isset($decoded[0]) && is_array($decoded[0])) {
        $decoded = $decoded[0];
    }

    $id = $decoded['id'] ?? null;
    $country_code = strtoupper(trim((string) ($decoded['countrycode'] ?? '')));
    $place_name = trim((string) ($decoded['asciiname'] ?? ''));

    if ($place_name === '') {
        $place_name = trim((string) ($decoded['name'] ?? ''));
    }

    if (
        (!is_int($id) && !ctype_digit((string) $id))
        || (int) $id <= 0
        || !preg_match('/^[A-Z]{2}$/', $country_code)
        || $place_name === ''
    ) {
        app_log('debug', 'Buienradar location payload missing required fields.', ['payload' => $decoded]);
        return null;
    }

    return [
        'id' => (int) $id,
        'country_code' => $country_code,
        'place_name' => $place_name,
    ];
}

/**
 * Build Buienradar place URL from normalized location metadata.
 */
function weather_buienradar_place_url(array $location): ?string
{
    $id = (int) ($location['id'] ?? 0);
    $country_code = strtoupper(trim((string) ($location['country_code'] ?? '')));
    $place_name = trim((string) ($location['place_name'] ?? ''));
    $place_slug = weather_buienradar_slug($place_name);

    if ($id <= 0 || !preg_match('/^[A-Z]{2}$/', $country_code) || $place_slug === '') {
        return null;
    }

    return sprintf(
        'https://www.buienradar.nl/weer/%s/%s/%d',
        rawurlencode($place_slug),
        $country_code,
        $id
    );
}

/**
 * Convert a place name to a URL-safe slug.
 */
function weather_buienradar_slug(string $value): string
{
    $slug = trim($value);

    if ($slug === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        if (is_string($ascii) && $ascii !== '') {
            $slug = $ascii;
        }
    }

    $slug = strtolower($slug);
    $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    if ($slug === '') {
        return '';
    }

    return $slug;
}
