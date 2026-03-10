<?php

declare(strict_types=1);
?>
<footer class="site-footer">
    <?php if (isset($season_calendar, $selected_year) && is_array($season_calendar)): ?>
        <div class="shell footer-calendar">
            <?php require APP_ROOT . '/partials/season_calendar.php'; ?>
        </div>
    <?php endif; ?>

    <div class="shell footer-shell">
        <p><?= e(t('footer_message')); ?></p>
    </div>
</footer>
<script src="<?= e(app_url('/assets/js/app.js')); ?>" defer></script>
</body>
</html>
