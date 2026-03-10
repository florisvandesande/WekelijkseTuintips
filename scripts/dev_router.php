<?php

declare(strict_types=1);

$request_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base_path = '';
$config_file = __DIR__ . '/../config.php';

if (is_file($config_file)) {
    $loaded_config = require $config_file;

    if (is_array($loaded_config)) {
        $base_path = trim((string) ($loaded_config['app']['base_path'] ?? ''), '/');
    }
}

$normalized_path = $request_path;

if ($base_path !== '') {
    $prefixed_base = '/' . $base_path;

    if ($normalized_path === $prefixed_base) {
        $normalized_path = '/';
    } elseif (str_starts_with($normalized_path, $prefixed_base . '/')) {
        $normalized_path = '/' . ltrim(substr($normalized_path, strlen($prefixed_base)), '/');
    }
}

$direct_file = __DIR__ . '/../' . ltrim($request_path, '/');

if ($request_path !== '/' && is_file($direct_file)) {
    return false;
}

$normalized_file = __DIR__ . '/../' . ltrim($normalized_path, '/');

if ($normalized_path !== '/' && is_file($normalized_file)) {
    $extension = strtolower(pathinfo($normalized_file, PATHINFO_EXTENSION));
    $mime_types = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'txt' => 'text/plain; charset=utf-8',
    ];

    if (isset($mime_types[$extension])) {
        header('Content-Type: ' . $mime_types[$extension]);
    }

    readfile($normalized_file);
    return true;
}

require __DIR__ . '/../index.php';
