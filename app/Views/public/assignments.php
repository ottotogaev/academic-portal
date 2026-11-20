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
<?php foreach ($items as $i => $item): ?>
<article class="card">
<span class="card__top">
<span class="card__num"><?= sprintf('%02d', $i + 1) ?></span>
<?php if (!empty($item['deadline'])): ?>
<span class="card__meta"><?= e(t('pages.assignments.deadline')) ?>: <?= e(format_date((string) $item['deadline'], 'd.m.Y H:i')) ?></span>
<?php endif; ?>
</span>
<div>
<h2 class="card__title"><?= e((string) $item['title']) ?></h2>
<div class="card__text prose"><?= text_html((string) $item['description']) ?></div>
</div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>
