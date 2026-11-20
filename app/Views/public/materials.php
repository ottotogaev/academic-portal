<?php
/**
 * @var string $pageKey
 * @var list<array{name:string,items:list<array<string,mixed>>}> $groups
 */
?>
<?= partial('components/banner', [
    'heading' => t('nav.' . $pageKey),
    'text' => t('pages.' . $pageKey . '.description'),
]) ?>
<section class="section">
<div class="container">
<?php if ($groups === []): ?>
<p class="empty-state"><?= e(t('pages.materials.empty')) ?></p>
<?php else: ?>
<?php foreach ($groups as $group): ?>
<?php if ($group['name'] !== ''): ?>
<h2 class="group-title"><?= e($group['name']) ?></h2>
<?php endif; ?>
<div class="cards">
<?php foreach ($group['items'] as $item): ?>
<a class="card" href="<?= e(url('materials/' . (int) $item['id'] . '/download')) ?>">
<span class="card__top">
<span class="card__num"><?= e(strtoupper((string) pathinfo((string) $item['original_filename'], PATHINFO_EXTENSION))) ?></span>
<span class="card__meta"><?= e(format_bytes((int) $item['file_size'])) ?></span>
</span>
<span>
<span class="card__title"><?= e((string) $item['title']) ?></span>
<?php if ((string) $item['description'] !== ''): ?>
<span class="card__text"><?= e((string) $item['description']) ?></span>
<?php endif; ?>
<span class="card__text card__action"><?= e(t('pages.materials.download')) ?> ↓</span>
</span>
</a>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</section>
