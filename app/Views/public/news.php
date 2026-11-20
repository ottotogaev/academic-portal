<?php
/**
 * @var string $pageKey
 * @var list<array<string,mixed>> $items
 */
?>
<?= partial('components/banner', [
    'heading' => t('nav.' . $pageKey),
    'text' => t('pages.' . $pageKey . '.description'),
]) ?>
<section class="section">
<div class="container">
<?php if ($items === []): ?>
<p class="empty-state"><?= e(t('pages.' . $pageKey . '.empty')) ?></p>
<?php else: ?>
<div class="cards">
<?php foreach ($items as $item): ?>
<a class="card" href="<?= e(url('news/' . $item['slug'])) ?>">
<span class="card__top"><span class="card__num"><?= e(format_date((string) $item['published_at'])) ?></span></span>
<span>
<span class="card__title"><?= e((string) $item['title']) ?></span>
<?php if ((string) $item['summary'] !== ''): ?>
<span class="card__text"><?= e((string) $item['summary']) ?></span>
<?php endif; ?>
</span>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>
