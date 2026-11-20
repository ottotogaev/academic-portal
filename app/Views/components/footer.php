<?php
/** @var bool $footerCta show the "send a message" call to action (home page only) */
$host = (string) parse_url((string) app()->config()->get('app.url'), PHP_URL_HOST);
?>
<footer class="site-footer">
<div class="container">
<?php if ($footerCta): ?>
<div class="cta">
<div>
<p class="cta__label"><?= e(t('footer.cta_label')) ?></p>
<h2 class="cta__title"><?= e(t('footer.cta_title')) ?></h2>
<a class="btn" href="<?= e(url('contact')) ?>"><?= e(t('footer.cta_button')) ?></a>
</div>
</div>
<?php endif; ?>
<div class="site-footer__bar<?= $footerCta ? ' site-footer__bar--ruled' : '' ?>">
<span><?= e(t('site.subject')) ?> · <?= e(t('site.university')) ?></span>
<?php if ($host !== ''): ?>
<span><?= e($host) ?></span>
<?php endif; ?>
</div>
</div>
</footer>
