<?php

declare(strict_types=1);

/**
 * Write a log line when file logging is enabled.
 */
function app_log(string $level, string $message, array $context = []): void
{
    $config = app_config();

    if (($config['logging']['enabled'] ?? false) !== true) {
        return;
    }

    $log_file = APP_ROOT . '/' . ltrim((string) $config['logging']['file'], '/');
    $log_directory = dirname($log_file);

    if (!is_dir($log_directory)) {
        @mkdir($log_directory, 0775, true);
    }

    $origin = app_log_origin();
    $timestamp = (new DateTimeImmutable('now', new DateTimeZone((string) $config['app']['timezone'])))->format('Y-m-d H:i:s');
    $context_json = $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';

    $line = sprintf(
        "[%s] [%s] %s (%s:%d)%s\n",
        $timestamp,
        strtoupper($level),
        $message,
        $origin['file'],
        $origin['line'],
        $context_json === '' ? '' : ' context=' . $context_json
    );

    @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Resolve the source file and line that called the logger.
 */
function app_log_origin(): array
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

    foreach ($trace as $frame) {
        $file = (string) ($frame['file'] ?? 'unknown');

        if ($file === __FILE__) {
            continue;
        }

        return [
            'file' => basename($file),
            'line' => (int) ($frame['line'] ?? 0),
        ];
    }

    return ['file' => 'unknown', 'line' => 0];
}
