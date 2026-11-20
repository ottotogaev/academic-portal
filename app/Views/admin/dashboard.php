<?php
/** @var string $username */
?>
<section class="admin-card">
<p class="admin-card__eyebrow"><?= e(t('admin.title')) ?></p>
<h1 class="admin-card__title"><?= e(t('admin.dashboard.title')) ?></h1>
<p class="admin-card__text"><?= e(t('admin.dashboard.welcome', ['name' => $username])) ?></p>
<p class="admin-card__text admin-card__text--muted"><?= e(t('admin.dashboard.soon')) ?></p>
<div class="admin-actions">
<a class="admin-link" href="/admin/password"><?= e(t('admin.dashboard.password')) ?></a>
<a class="admin-link" href="<?= e(url()) ?>" target="_blank" rel="noopener"><?= e(t('admin.dashboard.view_site')) ?></a>
<form method="post" action="/admin/logout">
<?= csrf_field() ?>
<button class="admin-link admin-link--button" type="submit"><?= e(t('admin.dashboard.logout')) ?></button>
</form>
</div>
</section>
