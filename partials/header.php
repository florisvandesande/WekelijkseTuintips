<?php

declare(strict_types=1);

$meta_title = $meta_title ?? t('site_title');
$meta_description = $meta_description ?? t('site_description');
$home_week = app_current_iso_year_week();
$weather_data = is_array($weather_data ?? null) ? $weather_data : null;
$weather_days = is_array($weather_data['days'] ?? null) ? $weather_data['days'] : [];
$weather_today = $weather_days[0] ?? null;
$weather_next_days = array_slice($weather_days, 1, 3);
$weather_error = is_string($weather_data['error'] ?? null) ? (string) $weather_data['error'] : '';
$weather_buienradar_url = is_string($weather_data['buienradar_url'] ?? null) ? (string) $weather_data['buienradar_url'] : 'https://www.buienradar.nl/';
$geolocation_enabled = (bool) app_array_get(app_config(), 'weather.geolocation_enabled', false);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= e($meta_title); ?></title>
    <meta name="description" content="<?= e($meta_description); ?>">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#f7f4ed">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#1f1a14">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= e(t('site_title')); ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#f7f4ed">
    <link rel="manifest" href="<?= e(app_url('/assets/site.webmanifest')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(app_url('/assets/images/icons/apple-touch-icon.png')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(app_url('/assets/images/icons/favicon-32x32.png')); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= e(app_url('/assets/images/icons/favicon-16x16.png')); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= e(app_url('/assets/images/icons/app-icon-192.png')); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= e(app_url('/assets/images/icons/app-icon-512.png')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_url('/assets/css/app.css')); ?>">
</head>
<body>
<header class="site-header">
    <div class="shell header-shell">
        <a class="site-title" href="<?= e(app_week_url((int) $home_week['year'], (int) $home_week['week'])); ?>">
            <?= e(t('site_title')); ?>
        </a>

        <?php if ($weather_today !== null || $weather_error !== ''): ?>
            <section
                class="header-weather"
                id="header-weather"
                aria-label="<?= e(t('weather_title')); ?>"
                data-api-url="<?= e(app_url('/api/weather.php')); ?>"
                data-geolocation-enabled="<?= $geolocation_enabled ? '1' : '0'; ?>"
            >
                <div class="header-weather-row">
                    <?php if ($weather_today !== null): ?>
                        <a
                            class="header-weather-today header-weather-today-link"
                            id="header-weather-today-link"
                            href="<?= e($weather_buienradar_url); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="<?= e(t('weather_buienradar')); ?>"
                        >
                            <p class="header-weather-today-label"><?= e(t('weather_today')); ?></p>
                            <p class="header-weather-today-main">
                                <span id="header-weather-today-icon"><?= e((string) ($weather_today['weather_icon'] ?? '☁')); ?></span>
                                <span id="header-weather-today-temp"><?= e((string) round((float) ($weather_today['max_temp'] ?? 0))); ?>℃</span>
                            </p>
                        </a>
                    <?php endif; ?>

                    <ul class="header-weather-next" id="header-weather-next">
                        <?php foreach ($weather_next_days as $day): ?>
                            <?php
                            $weekday = is_string($day['weekday'] ?? null) ? trim((string) $day['weekday']) : '';

                            if ($weekday === '' && is_string($day['date'] ?? null)) {
                                $date = DateTimeImmutable::createFromFormat('Y-m-d', (string) $day['date']);
                                if ($date instanceof DateTimeImmutable) {
                                    $weekday = app_weekday_dutch($date);
                                }
                            }
                            ?>
                            <li class="header-weather-next-item">
                                <span class="header-weather-next-day"><?= e($weekday); ?></span>
                                <span class="header-weather-next-main">
                                    <span><?= e((string) ($day['weather_icon'] ?? '☁')); ?></span>
                                    <span><?= e((string) round((float) ($day['max_temp'] ?? 0))); ?>℃</span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php if ($weather_error !== ''): ?>
                    <p class="header-weather-error"><?= e($weather_error); ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</header>
