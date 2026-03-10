<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/content_validator.php';

$options = [
    'file' => null,
    'strict' => false,
    'format' => 'text',
];

if (app_is_cli()) {
    $arguments = array_slice($argv ?? [], 1);

    foreach ($arguments as $argument) {
        if ($argument === '--strict') {
            $options['strict'] = true;
            continue;
        }

        if ($argument === '--format=json') {
            $options['format'] = 'json';
            continue;
        }

        if (str_starts_with($argument, '--file=')) {
            $options['file'] = substr($argument, 7);
            continue;
        }
    }
} else {
    $show_in_browser = (bool) app_array_get(app_config(), 'debug.show_validator_in_browser', false);

    if (!$show_in_browser) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Validator is disabled in browser mode. Enable debug.show_validator_in_browser in config.php to use this route.';
        exit;
    }

    $options['strict'] = isset($_GET['strict']) && (string) $_GET['strict'] === '1';
    $options['format'] = isset($_GET['format']) && (string) $_GET['format'] === 'json' ? 'json' : 'text';
    $options['file'] = isset($_GET['file']) ? (string) $_GET['file'] : null;
}

$single_file = null;

if (is_string($options['file']) && trim($options['file']) !== '') {
    $requested = trim((string) $options['file']);
    $single_file = str_starts_with($requested, '/') ? $requested : APP_ROOT . '/' . ltrim($requested, '/');
}

$report = content_validate_all((bool) $options['strict'], $single_file);

if ($options['format'] === 'json') {
    if (!app_is_cli()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    echo content_validation_to_json($report);
} else {
    if (!app_is_cli()) {
        header('Content-Type: text/plain; charset=utf-8');
    }

    echo content_validation_to_text($report);
}

if (app_is_cli()) {
    exit($report['passed'] ? 0 : 1);
}
