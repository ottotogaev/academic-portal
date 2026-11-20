<?php
/**
 * Inner page banner.
 *
 * @var string $heading page title (h1)
 * @var string $text    short description
 */
?>
<section class="banner">
<svg class="banner__bg" viewBox="0 0 1440 300" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
<defs><pattern id="gb" width="48" height="48" patternUnits="userSpaceOnUse"><path d="M48 0H0V48" fill="none" stroke="#12242D" stroke-width="1"/></pattern></defs>
<rect width="1440" height="300" fill="url(#gb)"/>
<g transform="translate(1240 140) scale(2)" opacity=".4"><g class="hx-spin"><polygon points="40,0 20,34.64 -20,34.64 -40,0 -20,-34.64 20,-34.64" fill="none" stroke="#4FE3D1" stroke-width="1.2"/><circle r="22" fill="none" stroke="#4FE3D1" stroke-width="1" stroke-opacity=".5"/></g></g>
<path class="flow" d="M0 282 H300 V254 H900 V282 H1440" fill="none" stroke="#4FE3D1" stroke-width="2" opacity=".5"/>
<g fill="none" stroke-width="1.5" stroke="#4FE3D1">
<circle class="b b--sm" cx="560" cy="300" r="5" stroke="#FFB04A" style="animation-duration:12s;animation-delay:-8s"/>
<circle class="b b--sm" cx="700" cy="300" r="4" style="animation-duration:11s"/>
<circle class="b b--sm" cx="860" cy="300" r="6" stroke="#FFB04A" style="animation-duration:14s;animation-delay:-5s"/>
<circle class="b b--sm" cx="1010" cy="300" r="3" style="animation-duration:9s;animation-delay:-3s"/>
</g>
</svg>
<div class="container banner__inner">
<p class="crumb"><a href="<?= e(url()) ?>"><?= e(t('nav.home')) ?></a> / <?= e($heading) ?></p>
<h1 class="banner__title"><?= e($heading) ?></h1>
<?php if ($text !== ''): ?>
<p class="banner__text"><?= e($text) ?></p>
<?php endif; ?>
</div>
</section>
