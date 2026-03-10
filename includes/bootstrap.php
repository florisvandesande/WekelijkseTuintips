<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/logger.php';
require_once APP_ROOT . '/includes/i18n.php';
require_once APP_ROOT . '/includes/date_helper.php';
require_once APP_ROOT . '/includes/router.php';
require_once APP_ROOT . '/includes/simple_yaml.php';
require_once APP_ROOT . '/includes/content.php';
require_once APP_ROOT . '/includes/weather.php';

/**
 * Initialize runtime configuration for this request.
 */
function app_bootstrap(): void
{
    date_default_timezone_set((string) app_array_get(app_config(), 'app.timezone', 'Europe/Amsterdam'));
}

app_bootstrap();
