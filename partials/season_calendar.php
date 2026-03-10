<?php

declare(strict_types=1);
?>
<section class="season-calendar" aria-labelledby="season-calendar-title">
    <h2 id="season-calendar-title"><?= e(t('season_calendar_title', ['year' => (string) $selected_year])); ?></h2>

    <div class="season-grid">
        <?php foreach ($season_calendar as $season_label => $weeks): ?>
            <article class="season-card">
                <h3><?= e(ucfirst($season_label)); ?></h3>
                <ul class="season-week-list">
                    <?php foreach ($weeks as $week): ?>
                        <li>
                            <a
                                class="season-week-link<?= $week['is_active'] ? ' is-active' : ''; ?>"
                                href="<?= e((string) $week['url']); ?>"
                                aria-current="<?= $week['is_active'] ? 'page' : 'false'; ?>"
                            >
                                <?= e('W' . (string) ((int) $week['week'])); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>
        <?php endforeach; ?>
    </div>
</section>
