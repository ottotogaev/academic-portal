<?php
/**
 * @var array<string,string> $old
 * @var array<string,string> $errors
 * @var list<string> $locales
 * @var list<string> $fields
 * @var ?array{0:string,1:string} $flash
 */
$long = ['biography', 'education', 'experience'];
$err = static fn (string $key): string => isset($errors[$key])
    ? '<p class="field__error" role="alert">' . e(t('admin.errors.' . $errors[$key])) . '</p>'
    : '';
?>
<section class="admin-card admin-card--wide">
<div class="admin-bar"><h1 class="admin-card__title"><?= e(t('admin.profile.title')) ?></h1></div>
<p class="admin-card__text admin-card__text--muted"><?= e(t('admin.profile.hint')) ?></p>
<?php if ($flash !== null): ?>
<p class="<?= $flash[0] === 'ok' ? 'admin-ok' : 'admin-alert' ?>" role="status"><?= e($flash[1]) ?></p>
<?php endif; ?>
<?php if ($errors !== []): ?>
<p class="admin-alert" role="alert"><?= e(t('admin.errors.summary')) ?></p>
<?php endif; ?>
<form method="post" action="/admin/profile" class="form__fields" novalidate>
<?= csrf_field() ?>
<?php foreach ($locales as $locale): ?>
<fieldset class="admin-fieldset">
<legend><?= e(t('admin.form.content_in', ['lang' => t('admin.languages.' . $locale)])) ?></legend>
<?php foreach ($fields as $field): $key = $locale . '__' . $field; ?>
<div class="field<?= isset($errors[$key]) ? ' field--error' : '' ?>">
<label for="<?= e($key) ?>"><?= e(t('admin.fields.' . $field)) ?><?= ($field === 'full_name' && $locale === $locales[0]) ? ' *' : '' ?></label>
<?php if (in_array($field, $long, true)): ?>
<textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="6"><?= e($old[$key] ?? '') ?></textarea>
<?php else: ?>
<input id="<?= e($key) ?>" name="<?= e($key) ?>" type="text" maxlength="255" value="<?= e($old[$key] ?? '') ?>">
<?php endif; ?>
<?= $err($key) ?>
</div>
<?php endforeach; ?>
</fieldset>
<?php endforeach; ?>
<fieldset class="admin-fieldset">
<legend><?= e(t('admin.profile.contacts')) ?></legend>
<div class="field<?= isset($errors['email']) ? ' field--error' : '' ?>">
<label for="email"><?= e(t('admin.fields.email')) ?></label>
<input id="email" name="email" type="text" maxlength="190" autocomplete="off" value="<?= e($old['email'] ?? '') ?>">
<?= $err('email') ?>
</div>
<div class="field<?= isset($errors['phone']) ? ' field--error' : '' ?>">
<label for="phone"><?= e(t('admin.fields.phone')) ?></label>
<input id="phone" name="phone" type="text" maxlength="40" autocomplete="off" value="<?= e($old['phone'] ?? '') ?>">
<?= $err('phone') ?>
</div>
<p class="form__note"><?= e(t('admin.profile.contacts_hint')) ?></p>
</fieldset>
<button class="btn" type="submit"><?= e(t('admin.form.save')) ?></button>
</form>
</section>
