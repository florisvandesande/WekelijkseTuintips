<?php

declare(strict_types=1);

/**
 * Load and validate application configuration from config.php.
 */
function app_config(): array
{
    static $cached = null;

    if (is_array($cached)) {
        return $cached;
    }

    $config_path = APP_ROOT . '/config.php';

    if (!is_file($config_path)) {
        app_config_fail(
            "Configuratiebestand ontbreekt. Kopieer config.example.php naar config.php en vul de waarden in."
        );
    }

    $loaded = require $config_path;

    if (!is_array($loaded)) {
        app_config_fail('Configuratiebestand is ongeldig. Verwacht een PHP-array in config.php.');
    }

    $validation_errors = app_validate_config($loaded);

    if ($validation_errors !== []) {
        $message = "Configuratie is ongeldig:\n- " . implode("\n- ", $validation_errors);
        app_config_fail($message);
    }

    $cached = $loaded;

    return $cached;
}

/**
 * Validate required config keys and value types.
 */
function app_validate_config(array $config): array
{
    $errors = [];

    $required_string_paths = [
        'app.name',
        'app.base_url',
        'app.base_path',
        'app.timezone',
        'app.ui_language',
        'logging.file',
        'weather.provider_url',
        'weather.fallback_location.label',
    ];

    foreach ($required_string_paths as $path) {
        $value = app_array_get($config, $path);

        if (!is_string($value) || trim($value) === '') {
            $errors[] = sprintf("%s must be a non-empty string.", $path);
        }
    }

    $required_bool_paths = [
        'app.allow_ui_language_switch',
        'logging.enabled',
        'weather.enabled',
        'weather.geolocation_enabled',
        'debug.show_validator_in_browser',
    ];

    foreach ($required_bool_paths as $path) {
        $value = app_array_get($config, $path);

        if (!is_bool($value)) {
            $errors[] = sprintf('%s must be a boolean.', $path);
        }
    }

    $required_int_paths = [
        'weather.request_timeout_seconds',
        'weather.cache_ttl_seconds',
        'weather.forecast_days',
    ];

    foreach ($required_int_paths as $path) {
        $value = app_array_get($config, $path);

        if (!is_int($value) || $value < 1) {
            $errors[] = sprintf('%s must be an integer greater than or equal to 1.', $path);
        }
    }

    $latitude = app_array_get($config, 'weather.fallback_location.latitude');
    $longitude = app_array_get($config, 'weather.fallback_location.longitude');

    if (!is_float($latitude) && !is_int($latitude)) {
        $errors[] = 'weather.fallback_location.latitude must be a number.';
    }

    if (!is_float($longitude) && !is_int($longitude)) {
        $errors[] = 'weather.fallback_location.longitude must be a number.';
    }

    if (is_array($config['app']['supported_locales'] ?? null) === false || ($config['app']['supported_locales'] ?? []) === []) {
        $errors[] = 'app.supported_locales must be a non-empty array.';
    }

    return $errors;
}

/**
 * Stop execution with a beginner-friendly configuration error.
 */
function app_config_fail(string $message): never
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Configuration error:\n" . $message . "\n");
        exit(1);
    }

    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Configuratiefout:\n" . $message . "\n";
    exit;
}

/**
 * Read a nested value from an array using dot notation.
 */
function app_array_get(array $source, string $path, mixed $default = null): mixed
{
    $segments = explode('.', $path);
    $value = $source;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}
