<?php

declare(strict_types=1);

define('APP_ROOT', __DIR__);

require_once APP_ROOT . '/includes/bootstrap.php';

$route = app_resolve_route($_SERVER['REQUEST_URI'] ?? '/');
$current_year_week = app_current_iso_year_week();

if ($route['type'] === 'home') {
    $selected_year = (int) $current_year_week['year'];
    $selected_week = (int) $current_year_week['week'];
    $selected_url = app_home_url();

    require APP_ROOT . '/week.php';
    exit;
}

if ($route['type'] === 'current') {
    app_redirect(app_week_url((int) $current_year_week['year'], (int) $current_year_week['week']));
}

if ($route['type'] === 'week') {
    $selected_year = (int) $route['year'];
    $selected_week = (int) $route['week'];

    if (!app_is_valid_year($selected_year) || !app_is_valid_week_for_year($selected_year, $selected_week)) {
        app_log('warning', 'Invalid route parameters for week page.', ['route' => $route]);
        http_response_code(404);
    }

    require APP_ROOT . '/week.php';
    exit;
}

app_log('warning', 'Route not found.', ['route' => $route]);
http_response_code(404);
$meta_title = t('error_not_found_title');
$meta_description = t('error_not_found_message');
require APP_ROOT . '/partials/header.php';
?>
<main class="shell page-main">
    <section class="error-card">
        <h1><?= e(t('error_not_found_title')); ?></h1>
        <p><?= e(t('error_not_found_message')); ?></p>
        <p><a class="button-link" href="<?= e(app_week_url((int) $current_year_week['year'], (int) $current_year_week['week'])); ?>"><?= e(t('week_current')); ?></a></p>
    </section>
</main>
<?php
require APP_ROOT . '/partials/footer.php';
