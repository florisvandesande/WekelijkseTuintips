<?php

declare(strict_types=1);

/**
 * Resolve active UI language.
 * The app is intentionally Dutch-only for user-facing text.
 */
function app_ui_language(): string
{
    return 'nl';
}

/**
 * Load locale messages from data/locales/{language}.php.
 */
function app_locale_messages(?string $language = null): array
{
    static $cached_messages = null;

    if (is_array($cached_messages)) {
        return $cached_messages;
    }

    $dutch_file = app_path('data/locales/nl.php');
    $messages = [];

    if (is_file($dutch_file)) {
        $loaded = require $dutch_file;
        if (is_array($loaded)) {
            $messages = $loaded;
        }
    }

    $cached_messages = $messages;

    return $cached_messages;
}

/**
 * Translate a message key.
 */
function t(string $key, array $replacements = [], ?string $language = null): string
{
    $messages = app_locale_messages($language);
    $message = (string) ($messages[$key] ?? $key);

    foreach ($replacements as $replacement_key => $replacement_value) {
        $message = str_replace('{' . $replacement_key . '}', (string) $replacement_value, $message);
    }

    return $message;
}
