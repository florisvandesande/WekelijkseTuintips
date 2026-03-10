<?php

declare(strict_types=1);

/**
 * Resolve route from request URI.
 */
function app_resolve_route(string $request_uri): array
{
    $path = parse_url($request_uri, PHP_URL_PATH) ?: '/';
    $normalized_path = '/' . trim($path, '/');

    if ($normalized_path === '//') {
        $normalized_path = '/';
    }

    $base_path = '/' . trim((string) app_array_get(app_config(), 'app.base_path', ''), '/');
    if ($base_path === '//') {
        $base_path = '/';
    }

    $relative_path = $normalized_path;

    if ($base_path !== '/' && str_starts_with($normalized_path, $base_path . '/')) {
        $relative_path = substr($normalized_path, strlen($base_path));
        $relative_path = '/' . ltrim((string) $relative_path, '/');
    } elseif ($base_path !== '/' && $normalized_path === $base_path) {
        $relative_path = '/';
    }

    if ($normalized_path === '/' || $relative_path === '/') {
        return ['type' => 'current'];
    }

    if ($relative_path === '/current') {
        return ['type' => 'current'];
    }

    if (preg_match('#^/(?P<year>\d{4})/week/(?P<week>\d{1,2})$#', $relative_path, $matches) === 1) {
        return [
            'type' => 'week',
            'year' => (int) $matches['year'],
            'week' => (int) $matches['week'],
        ];
    }

    return ['type' => 'not_found', 'path' => $relative_path];
}
