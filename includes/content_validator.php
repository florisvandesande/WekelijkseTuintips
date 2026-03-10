<?php

declare(strict_types=1);

/**
 * Format validation report in readable text output.
 */
function content_validation_to_text(array $report): string
{
    $lines = [];

    foreach ($report['issues'] as $issue) {
        $lines[] = sprintf('%-7s %s', $issue['severity'], $issue['file']);

        if (!empty($issue['item_id'])) {
            $lines[] = sprintf('        item: %s', $issue['item_id']);
        }

        $lines[] = sprintf('        field: %s', $issue['field']);
        $lines[] = sprintf('        message: %s', $issue['message']);
        $lines[] = '';
    }

    $lines[] = sprintf('Checked %d files, %d items.', (int) $report['files_checked'], (int) $report['items_checked']);
    $lines[] = sprintf('%d errors, %d warnings.', (int) $report['errors'], (int) $report['warnings']);
    $lines[] = $report['passed'] ? 'Validation passed.' : 'Validation failed.';

    return implode(PHP_EOL, $lines) . PHP_EOL;
}

/**
 * Build compact payload for JSON output mode.
 */
function content_validation_to_json(array $report): string
{
    $payload = [
        'summary' => [
            'files_checked' => (int) $report['files_checked'],
            'items_checked' => (int) $report['items_checked'],
            'errors' => (int) $report['errors'],
            'warnings' => (int) $report['warnings'],
            'passed' => (bool) $report['passed'],
        ],
        'issues' => array_values($report['issues']),
    ];

    return (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
