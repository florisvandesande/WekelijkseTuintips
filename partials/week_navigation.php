<?php

declare(strict_types=1);

$selected_url = is_string($selected_url ?? null) && $selected_url !== ''
    ? (string) $selected_url
    : app_week_url((int) $week_context['year'], (int) $week_context['week']);
$selected_week_label = t('week_heading', [
    'week' => (string) ((int) $week_context['week']),
    'year' => (string) ((int) $week_context['year']),
]);
?>
<nav class="week-nav" aria-label="<?= e(t('week_navigation_label')); ?>">
    <a class="week-nav-link" href="<?= e(app_week_url((int) $week_context['previous']['year'], (int) $week_context['previous']['week'])); ?>">
        <?= e(t('week_previous')); ?>
    </a>
    <a class="week-nav-link week-nav-current" href="<?= e($selected_url); ?>" aria-current="page">
        <?= e($selected_week_label); ?>
    </a>
    <a class="week-nav-link" href="<?= e(app_week_url((int) $week_context['next']['year'], (int) $week_context['next']['week'])); ?>">
        <?= e(t('week_next')); ?>
    </a>
</nav>
