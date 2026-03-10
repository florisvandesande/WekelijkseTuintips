<?php

declare(strict_types=1);

/**
 * Discover category markdown files.
 *
 * @return array<int, string>
 */
function content_discover_category_files(?string $single_file = null): array
{
    if ($single_file !== null && $single_file !== '') {
        $resolved = realpath($single_file);
        return $resolved !== false ? [$resolved] : [];
    }

    $pattern = app_path('content/categories/*.md');
    $files = glob($pattern);

    if (!is_array($files)) {
        return [];
    }

    sort($files, SORT_NATURAL);

    return $files;
}

/**
 * Validate all category files and build normalized category data.
 */
function content_validate_all(bool $strict = false, ?string $single_file = null): array
{
    $files = content_discover_category_files($single_file);
    $issues = [];
    $categories = [];
    $items_checked = 0;

    if ($single_file !== null && $single_file !== '' && $files === []) {
        $issues[] = content_issue(
            'ERROR',
            $single_file,
            null,
            'file',
            'Requested file was not found or is not readable.'
        );

        return [
            'files_checked' => 0,
            'items_checked' => 0,
            'categories' => [],
            'issues' => $issues,
            'errors' => 1,
            'warnings' => 0,
            'passed' => false,
        ];
    }

    $state = [
        'seen_category_keys' => [],
        'seen_item_ids' => [],
    ];

    $current_year = (int) app_now()->format('Y');

    foreach ($files as $file_path) {
        $parsed = content_parse_frontmatter_file($file_path);

        if ($parsed['data'] === null) {
            $issues[] = content_issue(
                'ERROR',
                $file_path,
                null,
                'frontmatter',
                $parsed['error_message'] ?? 'Unknown parse error.'
            );
            continue;
        }

        $normalized = content_normalize_category(
            $parsed['data'],
            $file_path,
            $state,
            $current_year,
            ['nl']
        );

        $issues = array_merge($issues, $normalized['issues']);

        if ($normalized['category'] !== null) {
            $categories[] = $normalized['category'];
            $items_checked += count($normalized['category']['items']);
        }
    }

    $error_count = 0;
    $warning_count = 0;

    foreach ($issues as $issue) {
        if ($issue['severity'] === 'ERROR') {
            $error_count++;
        } else {
            $warning_count++;
        }
    }

    $passed = $error_count === 0 && (!$strict || $warning_count === 0);

    return [
        'files_checked' => count($files),
        'items_checked' => $items_checked,
        'categories' => $categories,
        'issues' => $issues,
        'errors' => $error_count,
        'warnings' => $warning_count,
        'passed' => $passed,
    ];
}

/**
 * Parse frontmatter from one markdown file.
 */
function content_parse_frontmatter_file(string $file_path): array
{
    if (!is_readable($file_path)) {
        return [
            'data' => null,
            'error_message' => 'File is not readable.',
        ];
    }

    $raw_content = file_get_contents($file_path);

    if (!is_string($raw_content)) {
        return [
            'data' => null,
            'error_message' => 'Could not read file contents.',
        ];
    }

    if (!preg_match('/^---\R(?P<frontmatter>.*?)\R---(?:\R|$)/s', $raw_content, $matches)) {
        return [
            'data' => null,
            'error_message' => 'Frontmatter block missing. Start and end frontmatter with --- lines.',
        ];
    }

    $parser = new SimpleYamlParser();

    try {
        $parsed = $parser->parse((string) $matches['frontmatter']);
    } catch (SimpleYamlParseException $exception) {
        return [
            'data' => null,
            'error_message' => sprintf('Invalid YAML at frontmatter line %d: %s', $exception->line_number, $exception->getMessage()),
        ];
    }

    if (!is_array($parsed)) {
        return [
            'data' => null,
            'error_message' => 'Frontmatter root must be a mapping (key/value structure).',
        ];
    }

    return [
        'data' => $parsed,
    ];
}

/**
 * Normalize and validate one category document.
 */
function content_normalize_category(
    array $raw,
    string $file_path,
    array &$state,
    int $current_year,
    array $required_locales
): array {
    $issues = [];
    $category = null;

    $category_key = $raw['category_key'] ?? null;

    if (!is_string($category_key) || trim($category_key) === '') {
        $issues[] = content_issue('ERROR', $file_path, null, 'category_key', 'category_key must be a non-empty string.');
    } else {
        $category_key = trim($category_key);
        if (isset($state['seen_category_keys'][$category_key])) {
            $issues[] = content_issue(
                'ERROR',
                $file_path,
                null,
                'category_key',
                sprintf('Duplicate category_key "%s" already used in %s.', $category_key, content_relative_path($state['seen_category_keys'][$category_key]))
            );
        } else {
            $state['seen_category_keys'][$category_key] = $file_path;
        }
    }

    $category_title = content_normalize_translation_map($raw['category_title'] ?? null, 'category_title', $file_path, null, $required_locales, $issues);

    $sort_order = $raw['sort_order'] ?? null;
    if (!is_int($sort_order)) {
        $issues[] = content_issue('ERROR', $file_path, null, 'sort_order', 'sort_order must be an integer.');
    }

    $raw_items = $raw['items'] ?? null;

    if (!is_array($raw_items)) {
        $issues[] = content_issue('ERROR', $file_path, null, 'items', 'items must be an array.');
        $raw_items = [];
    }

    if ($raw_items === []) {
        $issues[] = content_issue('WARNING', $file_path, null, 'items', 'Category has no items. This is allowed but no tips will be shown.');
    }

    $normalized_items = [];

    foreach ($raw_items as $index => $item) {
        $field_prefix = sprintf('items[%d]', $index);

        if (!is_array($item)) {
            $issues[] = content_issue('ERROR', $file_path, null, $field_prefix, 'Item must be an object/map.');
            continue;
        }

        $item_id = $item['id'] ?? null;

        if (!is_string($item_id) || trim($item_id) === '') {
            $issues[] = content_issue('ERROR', $file_path, null, $field_prefix . '.id', 'id must be a non-empty string.');
            continue;
        }

        $item_id = trim($item_id);

        if (isset($state['seen_item_ids'][$item_id])) {
            $issues[] = content_issue(
                'ERROR',
                $file_path,
                $item_id,
                $field_prefix . '.id',
                sprintf('Duplicate item id "%s" already used in %s.', $item_id, content_relative_path($state['seen_item_ids'][$item_id]))
            );
            continue;
        }

        $state['seen_item_ids'][$item_id] = $file_path;

        $title = content_normalize_translation_map($item['title'] ?? null, $field_prefix . '.title', $file_path, $item_id, $required_locales, $issues);
        $body = content_normalize_translation_map($item['body'] ?? null, $field_prefix . '.body', $file_path, $item_id, $required_locales, $issues);

        $weeks = $item['weeks'] ?? null;
        if (!is_array($weeks) || $weeks === []) {
            $issues[] = content_issue('ERROR', $file_path, $item_id, $field_prefix . '.weeks', 'weeks must be a non-empty array.');
            continue;
        }

        $normalized_weeks = [];

        foreach ($weeks as $week_index => $week_value) {
            if (!is_int($week_value) || $week_value < 1 || $week_value > 53) {
                $issues[] = content_issue(
                    'ERROR',
                    $file_path,
                    $item_id,
                    sprintf('%s.weeks[%d]', $field_prefix, $week_index),
                    sprintf('Invalid week number %s, expected integer 1..53.', is_scalar($week_value) ? (string) $week_value : gettype($week_value))
                );
                continue;
            }

            $normalized_weeks[] = $week_value;
        }

        $normalized_weeks = array_values(array_unique($normalized_weeks));

        if ($normalized_weeks === []) {
            continue;
        }

        $start_year = $item['start_year'] ?? $current_year;
        if (!is_int($start_year)) {
            $issues[] = content_issue('ERROR', $file_path, $item_id, $field_prefix . '.start_year', 'start_year must be an integer when provided.');
            continue;
        }

        $repeat_every_years = $item['repeat_every_years'] ?? 1;
        if (!is_int($repeat_every_years) || $repeat_every_years < 1) {
            $issues[] = content_issue('ERROR', $file_path, $item_id, $field_prefix . '.repeat_every_years', 'repeat_every_years must be an integer >= 1.');
            continue;
        }

        $priority = strtolower((string) ($item['priority'] ?? 'medium'));
        if (!in_array($priority, ['low', 'medium', 'high'], true)) {
            $issues[] = content_issue('ERROR', $file_path, $item_id, $field_prefix . '.priority', 'priority must be one of: low, medium, high.');
            continue;
        }

        $conditions = content_optional_string_array($item['conditions'] ?? [], $file_path, $item_id, $field_prefix . '.conditions', $issues);
        $tags = content_optional_string_array($item['tags'] ?? [], $file_path, $item_id, $field_prefix . '.tags', $issues);
        $garden_types = content_optional_string_array($item['garden_types'] ?? [], $file_path, $item_id, $field_prefix . '.garden_types', $issues);

        $normalized_items[] = [
            'id' => $item_id,
            'title' => $title,
            'body' => $body,
            'weeks' => $normalized_weeks,
            'start_year' => $start_year,
            'repeat_every_years' => $repeat_every_years,
            'priority' => $priority,
            'conditions' => $conditions,
            'tags' => $tags,
            'garden_types' => $garden_types,
        ];
    }

    $has_errors = false;

    foreach ($issues as $issue) {
        if ($issue['severity'] === 'ERROR') {
            $has_errors = true;
            break;
        }
    }

    if (!$has_errors) {
        $category = [
            'category_key' => (string) $category_key,
            'category_title' => $category_title,
            'sort_order' => (int) $sort_order,
            'items' => $normalized_items,
            'file' => $file_path,
        ];
    }

    return [
        'category' => $category,
        'issues' => $issues,
    ];
}

/**
 * Normalize translation map (or single string fallback) and emit warnings for missing locales.
 */
function content_normalize_translation_map(
    mixed $value,
    string $field,
    string $file_path,
    ?string $item_id,
    array $required_locales,
    array &$issues
): array {
    $result = [];

    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed !== '') {
            $result['nl'] = $trimmed;
        }
    } elseif (is_array($value)) {
        foreach ($value as $locale => $translation) {
            if (!is_string($locale)) {
                continue;
            }

            if (!is_string($translation)) {
                continue;
            }

            $trimmed = trim($translation);

            if ($trimmed !== '') {
                $result[strtolower($locale)] = $trimmed;
            }
        }
    }

    if ($result === []) {
        $issues[] = content_issue('ERROR', $file_path, $item_id, $field, 'Translation value must contain at least one non-empty string.');
        return [];
    }

    foreach ($required_locales as $locale) {
        $locale_key = strtolower((string) $locale);

        if (!isset($result[$locale_key])) {
            $issues[] = content_issue(
                'WARNING',
                $file_path,
                $item_id,
                $field . '.' . $locale_key,
                sprintf('Missing translation for locale "%s".', $locale_key)
            );
        }
    }

    return $result;
}

/**
 * Validate optional array fields.
 *
 * @return array<int, string>
 */
function content_optional_string_array(mixed $value, string $file_path, ?string $item_id, string $field, array &$issues): array
{
    if ($value === null) {
        return [];
    }

    if (!is_array($value)) {
        $issues[] = content_issue('ERROR', $file_path, $item_id, $field, 'Expected an array.');
        return [];
    }

    $result = [];

    foreach ($value as $index => $entry) {
        if (!is_string($entry) || trim($entry) === '') {
            $issues[] = content_issue('ERROR', $file_path, $item_id, sprintf('%s[%d]', $field, $index), 'Expected a non-empty string.');
            continue;
        }

        $result[] = trim($entry);
    }

    return $result;
}

/**
 * Build a validation issue payload.
 */
function content_issue(string $severity, string $file_path, ?string $item_id, string $field, string $message): array
{
    return [
        'severity' => $severity,
        'file' => content_relative_path($file_path),
        'item_id' => $item_id,
        'field' => $field,
        'message' => $message,
    ];
}

/**
 * Convert absolute paths to readable repository-relative paths.
 */
function content_relative_path(string $file_path): string
{
    $normalized_root = rtrim(APP_ROOT, '/');

    if (str_starts_with($file_path, $normalized_root . '/')) {
        return substr($file_path, strlen($normalized_root) + 1);
    }

    return $file_path;
}

/**
 * Load categories and filter active items for selected year/week.
 */
function content_load_week_data(int $selected_year, int $selected_week, string $language): array
{
    $validation = content_validate_all(false);

    foreach ($validation['issues'] as $issue) {
        $level = strtolower((string) $issue['severity']);
        app_log($level, 'Content validation issue.', $issue);
    }

    $categories = [];

    foreach ($validation['categories'] as $category) {
        $active_items = [];

        foreach ($category['items'] as $item) {
            if (content_item_is_active_for_week($item, $selected_year, $selected_week)) {
                $active_items[] = $item;
            }
        }

        if ($active_items === []) {
            continue;
        }

        usort($active_items, 'content_compare_items');

        $categories[] = [
            'key' => $category['category_key'],
            'title' => app_translation_value($category['category_title'], $language),
            'sort_order' => $category['sort_order'],
            'items' => array_map(
                static fn(array $item): array => [
                    'id' => $item['id'],
                    'title' => app_translation_value($item['title'], $language),
                    'body' => app_translation_value($item['body'], $language),
                    'priority' => $item['priority'],
                    'conditions' => $item['conditions'],
                    'tags' => $item['tags'],
                    'garden_types' => $item['garden_types'],
                ],
                $active_items
            ),
        ];
    }

    usort(
        $categories,
        static function (array $left, array $right): int {
            $sort_order_comparison = $left['sort_order'] <=> $right['sort_order'];

            if ($sort_order_comparison !== 0) {
                return $sort_order_comparison;
            }

            return strcmp($left['title'], $right['title']);
        }
    );

    return [
        'categories' => $categories,
        'issues' => $validation['issues'],
        'errors' => $validation['errors'],
        'warnings' => $validation['warnings'],
    ];
}

/**
 * Check if an item is active for the selected year and week.
 */
function content_item_is_active_for_week(array $item, int $selected_year, int $selected_week): bool
{
    if (!in_array($selected_week, $item['weeks'], true)) {
        return false;
    }

    if ($selected_year < $item['start_year']) {
        return false;
    }

    $year_difference = $selected_year - $item['start_year'];

    return $year_difference % $item['repeat_every_years'] === 0;
}

/**
 * Sort items by priority first, then title.
 */
function content_compare_items(array $left, array $right): int
{
    $priority_order = ['high' => 0, 'medium' => 1, 'low' => 2];

    $left_priority = $priority_order[$left['priority']] ?? 99;
    $right_priority = $priority_order[$right['priority']] ?? 99;

    $priority_comparison = $left_priority <=> $right_priority;

    if ($priority_comparison !== 0) {
        return $priority_comparison;
    }

    $left_title = app_translation_value($left['title'] ?? [], 'nl');
    $right_title = app_translation_value($right['title'] ?? [], 'nl');

    return strcmp($left_title, $right_title);
}
