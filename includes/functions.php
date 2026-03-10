<?php

declare(strict_types=1);

/**
 * Escape text for safe HTML output.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Build an absolute path inside the project root.
 */
function app_path(string $relative_path): string
{
    return APP_ROOT . '/' . ltrim($relative_path, '/');
}

/**
 * Return true when current execution context is CLI.
 */
function app_is_cli(): bool
{
    return PHP_SAPI === 'cli';
}

/**
 * Build application URL based on configured base path.
 */
function app_url(string $path = '/', array $query = []): string
{
    $base_path = rtrim((string) app_array_get(app_config(), 'app.base_path', ''), '/');
    $normalized_path = '/' . ltrim($path, '/');

    if ($normalized_path === '/index.php') {
        $normalized_path = '/';
    }

    $url = ($base_path === '' ? '' : $base_path) . ($normalized_path === '/' ? '' : $normalized_path);

    if ($url === '') {
        $url = '/';
    }

    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

/**
 * Redirect and stop execution.
 */
function app_redirect(string $url, int $status_code = 302): never
{
    header('Location: ' . $url, true, $status_code);
    exit;
}

/**
 * Convert a translation map or string to a single display value.
 */
function app_translation_value(array|string|null $value, string $language = 'nl'): string
{
    if (is_string($value)) {
        return trim($value);
    }

    if (!is_array($value) || $value === []) {
        return '';
    }

    $preferred = trim((string) ($value[$language] ?? ''));

    if ($preferred !== '') {
        return $preferred;
    }

    $dutch = trim((string) ($value['nl'] ?? ''));

    if ($dutch !== '') {
        return $dutch;
    }

    foreach ($value as $candidate) {
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }
    }

    return '';
}

/**
 * Render user-authored body text safely without allowing raw HTML.
 */
function app_render_text_blocks(string $text): string
{
    $trimmed = trim($text);

    if ($trimmed === '') {
        return '';
    }

    $paragraphs = preg_split('/\R{2,}/u', $trimmed) ?: [];
    $html_parts = [];

    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);

        if ($paragraph === '') {
            continue;
        }

        $html_parts[] = '<p>' . nl2br(e($paragraph)) . '</p>';
    }

    return implode("\n", $html_parts);
}

/**
 * Validate and sanitize optional latitude and longitude request values.
 */
function app_sanitize_coordinates(mixed $latitude, mixed $longitude): ?array
{
    if ($latitude === null || $longitude === null) {
        return null;
    }

    if (!is_numeric((string) $latitude) || !is_numeric((string) $longitude)) {
        return null;
    }

    $lat = (float) $latitude;
    $lon = (float) $longitude;

    if ($lat < -90.0 || $lat > 90.0 || $lon < -180.0 || $lon > 180.0) {
        return null;
    }

    return ['lat' => $lat, 'lon' => $lon];
}

/**
 * Return URL path for a specific ISO week page.
 */
function app_week_url(int $year, int $week): string
{
    return app_url(sprintf('/%d/week/%d', $year, $week));
}
