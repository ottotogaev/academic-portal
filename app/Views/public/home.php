<?php
/**
 * @var list<array<string,mixed>> $latestNews
 * @var list<array<string,mixed>> $latestMaterials
 */
$quick = ['about', 'topics', 'assignments', 'practical', 'laboratory', 'materials'];
$paths = [];
foreach ((array) app()->config()->get('navigation', []) as $item) {
    $paths[$item['key']] = (string) $item['path'];
}
?>
<section class="hero">
<div class="hero__glow hero__glow--a"></div>
<div class="hero__glow hero__glow--b"></div>
<?= partial('components/hero_bg') ?>
<div class="hero__inner">
<h1 class="hero__title"><?= e(t('home.hero_a')) ?> <span class="c-cyan"><?= e(t('home.hero_b')) ?></span> <span class="c-amber"><?= e(t('home.hero_c')) ?></span></h1>

<div class="portrait">
<div class="portrait__ring portrait__ring--outer"><span class="dot dot--amber"></span><span class="dot dot--cyan"></span></div>
<div class="portrait__ring portrait__ring--inner"><span class="dot dot--glow"></span></div>
<img class="portrait__img" src="<?= e(asset('images/professor.jpg')) ?>" alt="<?= e(profile('full_name')) ?>" width="460" height="460">
</div>

<p class="hero__name"><?= e(profile('full_name')) ?></p>
<p class="hero__role"><?= e(profile('position')) ?></p>
<div class="chips">
<span class="chip chip--accent"><?= e(profile('degree')) ?></span>
<span class="chip"><?= e(t('site.university')) ?></span>
</div>
</div>
</section>

<section class="section">
<div class="container">
<div class="section__head">
<h2 class="section__title"><?= e(t('home.quick_title')) ?></h2>
<p class="section__note"><?= e(t('home.quick_note')) ?></p>
</div>
<div class="cards">
<?php foreach ($quick as $i => $key): ?>
<a class="card" href="<?= e(url($paths[$key] ?? '')) ?>">
<span class="card__top">
<span class="card__num"><?= sprintf('%02d', $i + 1) ?></span>
<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
</span>
<span>
<span class="card__title"><?= e(t('nav.' . $key)) ?></span>
<span class="card__text"><?= e(t('home.quick.' . $key)) ?></span>
</span>
</a>
<?php endforeach; ?>
</div>
</div>
</section>

<section class="section">
<div class="container split">
<div class="col">
<div class="col__head">
<h2 class="col__title"><?= e(t('home.news_title')) ?></h2>
<a class="link-accent" href="<?= e(url($paths['news'] ?? '')) ?>"><?= e(t('home.all')) ?></a>
</div>
<?php if ($latestNews === []): ?>
<p class="empty"><?= e(t('home.news_empty')) ?></p>
<?php else: ?>
<ul class="mini-list">
<?php foreach ($latestNews as $item): ?>
<li><a href="<?= e(url('news/' . $item['slug'])) ?>"><span class="mini-list__meta"><?= e(format_date((string) $item['published_at'])) ?></span><span class="mini-list__title"><?= e((string) $item['title']) ?></span></a></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
<div class="col col--amber">
<div class="col__head">
<h2 class="col__title"><?= e(t('home.materials_title')) ?></h2>
<a class="link-accent link-accent--amber" href="<?= e(url($paths['materials'] ?? '')) ?>"><?= e(t('home.all')) ?></a>
</div>
<?php if ($latestMaterials === []): ?>
<p class="empty"><?= e(t('home.materials_empty')) ?></p>
<?php else: ?>
<ul class="mini-list">
<?php foreach ($latestMaterials as $item): ?>
<li><a href="<?= e(url('materials/' . (int) $item['id'] . '/download')) ?>"><span class="mini-list__meta"><?= e(strtoupper((string) pathinfo((string) $item['original_filename'], PATHINFO_EXTENSION))) ?> · <?= e(format_bytes((int) $item['file_size'])) ?></span><span class="mini-list__title"><?= e((string) $item['title']) ?></span></a></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
</div>
</section>
