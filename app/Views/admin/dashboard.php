<?php
/** @var string $username @var array<string,int> $counts @var int $newMessages */
?>
<section class="admin-card">
<p class="admin-card__eyebrow"><?= e(t('admin.title')) ?></p>
<h1 class="admin-card__title"><?= e(t('admin.dashboard.title')) ?></h1>
<p class="admin-card__text"><?= e(t('admin.dashboard.welcome', ['name' => $username])) ?></p>
<p class="admin-card__text admin-card__text--muted"><?= e(t('admin.dashboard.soon')) ?></p>
<div class="admin-tiles">
<a class="admin-tile" href="/admin/feedback"><span class="admin-tile__n"><?= (int) $newMessages ?></span><span><?= e(t('admin.feedback.new_count')) ?></span></a>
<?php foreach ($counts as $type => $count): ?>
<a class="admin-tile" href="/admin/<?= e($type) ?>"><span class="admin-tile__n"><?= (int) $count ?></span><span><?= e(t('admin.types.' . $type)) ?></span></a>
<?php endforeach; ?>
</div>
<div class="admin-actions">
<a class="admin-link" href="/admin/password"><?= e(t('admin.dashboard.password')) ?></a>
<a class="admin-link" href="<?= e(url()) ?>" target="_blank" rel="noopener"><?= e(t('admin.dashboard.view_site')) ?></a>
<form method="post" action="/admin/logout">
<?= csrf_field() ?>
<button class="admin-link admin-link--button" type="submit"><?= e(t('admin.dashboard.logout')) ?></button>
</form>
</div>
</section>
