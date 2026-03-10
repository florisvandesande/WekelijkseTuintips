<?php

declare(strict_types=1);

$weather_data = $weather_data ?? ['days' => [], 'location' => '', 'error' => null, 'buienradar_url' => 'https://www.buienradar.nl/'];
$geolocation_enabled = (bool) app_array_get(app_config(), 'weather.geolocation_enabled', false);
?>
<section class="weather-block" aria-label="<?= e(t('weather_title')); ?>">
    <div class="weather-head">
        <h2 class="weather-title"><?= e(t('weather_title')); ?></h2>
        <p class="weather-location" id="weather-location"><?= e(t('weather_location')); ?>: <?= e((string) ($weather_data['location'] ?? '')); ?></p>
    </div>

    <?php if (!empty($weather_data['error'])): ?>
        <p class="weather-error"><?= e((string) $weather_data['error']); ?></p>
    <?php endif; ?>

    <ul
        class="weather-list"
        id="weather-list"
        data-api-url="<?= e(app_url('/api/weather.php')); ?>"
        data-geolocation-enabled="<?= $geolocation_enabled ? '1' : '0'; ?>"
    >
        <?php foreach (($weather_data['days'] ?? []) as $day): ?>
            <li class="weather-item">
                <span class="weather-day"><?= e((string) $day['day_label']); ?></span>
                <span class="weather-temp"><?= e((string) round((float) $day['max_temp'])); ?>° / <?= e((string) round((float) $day['min_temp'])); ?>°</span>
                <span class="weather-desc"><?= e((string) $day['weather_text']); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <p class="weather-link-row">
        <a class="weather-link" id="buienradar-link" href="<?= e((string) ($weather_data['buienradar_url'] ?? 'https://www.buienradar.nl/')); ?>" target="_blank" rel="noopener noreferrer">
            <?= e(t('weather_buienradar')); ?>
        </a>
    </p>
</section>
