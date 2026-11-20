<?php
/**
 * Generic list page (topics, news, assignments, practical, laboratory, materials).
 * Phase 1 shows the empty state; items are rendered here once the content phase supplies them.
 *
 * @var string $pageKey
 */
?>
<?= partial('components/banner', [
    'heading' => t('nav.' . $pageKey),
    'text' => t('pages.' . $pageKey . '.description'),
]) ?>
<section class="section">
<div class="container">
<p class="empty-state"><?= e(t('pages.' . $pageKey . '.empty')) ?></p>
</div>
</section>
