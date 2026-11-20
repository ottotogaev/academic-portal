<?php
/**
 * @var string $pageKey  navigation key of the current page
 * @var string $pagePath path of the current page without the locale prefix
 */
$nav = (array) app()->config()->get('navigation', []);
$locales = (array) app()->config()->get('app.locales', []);
$current = locale();
?>
<header class="site-header">
<div class="container">
<div class="site-header__top">
<a class="brand" href="<?= e(url()) ?>">
<svg class="brand__mark" width="40" height="44" viewBox="-20 -22 40 44" aria-hidden="true" focusable="false"><polygon points="18,0 9,15.6 -9,15.6 -18,0 -9,-15.6 9,-15.6" fill="none" stroke="#4FE3D1" stroke-width="1.6"/><path d="M0 -9 C0 -9 -6.5 -0.5 -6.5 3.5 a6.5 6.5 0 0 0 13 0 C6.5 -0.5 0 -9 0 -9Z" fill="#FFB04A"/></svg>
<span>
<span class="brand__title"><?= e(t('site.name')) ?></span>
<span class="brand__sub"><?= e(t('site.tagline')) ?></span>
</span>
</a>
<div class="lang" role="group" aria-label="<?= e(t('site.language')) ?>">
<?php foreach ($locales as $code): ?>
<a href="<?= e(url($pagePath, $code)) ?>" hreflang="<?= e($code) ?>"<?= $code === $current ? ' aria-current="true"' : '' ?>><?= e(strtoupper($code)) ?></a>
<?php endforeach; ?>
</div>
</div>
<nav class="nav" aria-label="<?= e(t('site.menu')) ?>">
<?php foreach ($nav as $item): ?>
<a href="<?= e(url($item['path'])) ?>"<?= $item['key'] === $pageKey ? ' aria-current="page"' : '' ?>><?= e(t('nav.' . $item['key'])) ?></a>
<?php endforeach; ?>
</nav>
</div>
</header>
