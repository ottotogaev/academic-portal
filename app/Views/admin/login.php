<?php
/**
 * @var ?string $error
 * @var string  $username
 */
?>
<section class="admin-card admin-card--narrow">
<p class="admin-card__eyebrow"><?= e(t('admin.title')) ?></p>
<h1 class="admin-card__title"><?= e(t('admin.login.title')) ?></h1>
<?php if ($error !== null): ?>
<p class="admin-alert" role="alert"><?= e($error) ?></p>
<?php endif; ?>
<form method="post" action="/admin/login" class="form__fields" autocomplete="on">
<?= csrf_field() ?>
<div class="field">
<label for="username"><?= e(t('admin.login.username')) ?></label>
<input id="username" name="username" type="text" value="<?= e($username) ?>" maxlength="50" autocomplete="username" required autofocus>
</div>
<div class="field">
<label for="password"><?= e(t('admin.login.password')) ?></label>
<input id="password" name="password" type="password" maxlength="1024" autocomplete="current-password" required>
</div>
<button class="btn" type="submit"><?= e(t('admin.login.submit')) ?></button>
</form>
</section>
