<?php

declare(strict_types=1);
?>
<section class="category-section" id="<?= e((string) $category['key']); ?>">
    <h2><?= e((string) $category['title']); ?></h2>
    <ul class="tip-list">
        <?php foreach ($category['items'] as $item): ?>
            <li class="tip-item priority-<?= e((string) $item['priority']); ?>">
                <details class="tip-details">
                    <summary class="tip-summary">
                        <h3><?= e((string) $item['title']); ?><?php if ((string) $item['priority'] === 'high'): ?> <span class="tip-priority-badge"><?= e(t('priority_badge')); ?></span><?php endif; ?></h3>
                        <span class="tip-summary-arrow" aria-hidden="true"></span>
                    </summary>
                    <div class="tip-content">
                        <?= app_render_text_blocks((string) $item['body']); ?>
                    </div>
                </details>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
