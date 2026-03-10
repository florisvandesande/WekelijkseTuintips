<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
    require_once APP_ROOT . '/includes/bootstrap.php';
}

$current_year_week = app_current_iso_year_week();

$selected_year = isset($selected_year) ? (int) $selected_year : (isset($_GET['year']) ? (int) $_GET['year'] : (int) $current_year_week['year']);
$selected_week = isset($selected_week) ? (int) $selected_week : (isset($_GET['week']) ? (int) $_GET['week'] : (int) $current_year_week['week']);
$selected_language = app_ui_language();

if (!app_is_valid_year($selected_year) || !app_is_valid_week_for_year($selected_year, $selected_week)) {
    http_response_code(404);
    $meta_title = t('error_invalid_week_title');
    $meta_description = t('error_invalid_week_message');
    require APP_ROOT . '/partials/header.php';
    ?>
    <main class="shell page-main">
        <section class="error-card">
            <h1><?= e(t('error_invalid_week_title')); ?></h1>
            <p><?= e(t('error_invalid_week_message')); ?></p>
            <p><a class="button-link" href="<?= e(app_week_url((int) $current_year_week['year'], (int) $current_year_week['week'])); ?>"><?= e(t('week_current')); ?></a></p>
        </section>
    </main>
    <?php
    require APP_ROOT . '/partials/footer.php';
    return;
}

$week_context = app_week_context($selected_year, $selected_week);
$season_intro = app_season_intro($selected_year, $selected_week);
$season_calendar = app_season_calendar($selected_year, $selected_week);
$content_data = content_load_week_data($selected_year, $selected_week, $selected_language);
$weather_data = weather_get_header_forecast();

$meta_title = t('meta_week_title', ['week' => (string) $selected_week, 'year' => (string) $selected_year]);
$meta_description = t('meta_week_description', ['week' => (string) $selected_week]);

require APP_ROOT . '/partials/header.php';
?>
<main class="shell page-main">
    <section class="week-top">
        <?php require APP_ROOT . '/partials/week_navigation.php'; ?>

        <section class="week-hero">
            <h1><?= e((string) $week_context['date_range_label']); ?></h1>
            <p class="week-intro"><?= e($season_intro); ?></p>
        </section>
    </section>

    <?php if ($content_data['categories'] === []): ?>
        <?php require APP_ROOT . '/partials/empty_state.php'; ?>
    <?php else: ?>
        <?php foreach ($content_data['categories'] as $category): ?>
            <?php require APP_ROOT . '/partials/category_section.php'; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
<?php
require APP_ROOT . '/partials/footer.php';
