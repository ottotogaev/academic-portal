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
<span class="card__top"><span class="card__num"><?= sprintf('%02d', $i + 1) ?></span></span>
<div>
<h2 class="card__title"><?= e((string) $item['title']) ?></h2>
<?php if ((string) $item['description'] !== ''): ?>
<div class="card__text prose"><?= text_html((string) $item['description']) ?></div>
<?php endif; ?>
</div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>
