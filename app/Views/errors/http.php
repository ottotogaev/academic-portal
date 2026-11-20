<?php
/** @var int $status 400, 404 or 405 */
?>
<section class="error-page container">
<p class="error-page__code"><?= (int) $status ?></p>
<h1 class="error-page__title"><?= e(t('errors.' . $status . '.title')) ?></h1>
<p class="error-page__text"><?= e(t('errors.' . $status . '.text')) ?></p>
<a class="btn" href="<?= e(url()) ?>"><?= e(t('errors.home')) ?></a>
</section>
