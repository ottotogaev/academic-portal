<?php
/**
 * @var ?string $error
 * @var bool    $done
 */
?>
<section class="admin-card admin-card--narrow">
<p class="admin-card__eyebrow"><?= e(t('admin.title')) ?></p>
<h1 class="admin-card__title"><?= e(t('admin.password.title')) ?></h1>
<?php if ($done): ?>
<p class="admin-ok" role="status"><?= e(t('admin.password.done')) ?></p>
<?php elseif ($error !== null): ?>
<p class="admin-alert" role="alert"><?= e($error) ?></p>
<?php endif; ?>
<form method="post" action="/admin/password" class="form__fields">
<?= csrf_field() ?>
<div class="field">
<label for="current_password"><?= e(t('admin.password.current')) ?></label>
<input id="current_password" name="current_password" type="password" maxlength="1024" autocomplete="current-password" required>
</div>
<div class="field">
<label for="new_password"><?= e(t('admin.password.new')) ?></label>
<input id="new_password" name="new_password" type="password" maxlength="1024" minlength="12" autocomplete="new-password" required>
</div>
<div class="field">
<label for="new_password_confirm"><?= e(t('admin.password.confirm')) ?></label>
<input id="new_password_confirm" name="new_password_confirm" type="password" maxlength="1024" minlength="12" autocomplete="new-password" required>
</div>
<p class="form__note"><?= e(t('admin.password.hint', ['min' => '12'])) ?></p>
<button class="btn" type="submit"><?= e(t('admin.password.submit')) ?></button>
</form>
<p class="admin-card__text"><a class="admin-link" href="/admin/dashboard"><?= e(t('admin.back')) ?></a></p>
</section>
