<?php
/**
 * @var string              $type
 * @var array<string,mixed> $def
 * @var ?int                $id
 * @var array<string,string> $old
 * @var array<string,string> $errors
 * @var list<string>        $locales
 * @var array<string,array<int,string>> $refs
 * @var ?array<string,mixed> $upload
 * @var ?array{0:string,1:string} $flash
 */
$action = $id === null ? '/admin/' . $type : '/admin/' . $type . '/' . $id;
$err = static fn (string $key): string => isset($errors[$key])
    ? '<p class="field__error" role="alert">' . e(t('admin.errors.' . $errors[$key], ['max' => ''])) . '</p>'
    : '';
$multiline = static fn (array $spec): bool => $spec['type'] === 'textarea';
?>
<section class="admin-card admin-card--wide">
<div class="admin-bar">
<h1 class="admin-card__title"><?= e(t($id === null ? 'admin.form.new' : 'admin.form.edit')) ?>: <?= e(t('admin.types.' . $type)) ?></h1>
<a class="admin-link" href="/admin/<?= e($type) ?>"><?= e(t('admin.back')) ?></a>
</div>
<?php if ($flash !== null): ?>
<p class="<?= $flash[0] === 'ok' ? 'admin-ok' : 'admin-alert' ?>" role="status"><?= e($flash[1]) ?></p>
<?php endif; ?>
<?php if ($errors !== []): ?>
<p class="admin-alert" role="alert"><?= e(t('admin.errors.summary')) ?></p>
<?php endif; ?>
<form method="post" action="<?= e($action) ?>" class="form__fields" novalidate<?= $upload !== null ? ' enctype="multipart/form-data"' : '' ?>>
<?= csrf_field() ?>

<?php if ($upload !== null): ?>
<fieldset class="admin-fieldset">
<legend><?= e(t('admin.form.file')) ?></legend>
<div class="field<?= isset($errors['file']) ? ' field--error' : '' ?>">
<?php if (is_array($upload['current'])): ?>
<p class="admin-card__text"><?= e(t('admin.form.current_file')) ?>: <strong><?= e((string) $upload['current']['original_filename']) ?></strong> (<?= e(format_bytes((int) $upload['current']['file_size'])) ?>)</p>
<?php endif; ?>
<label for="file"><?= e(t(is_array($upload['current']) ? 'admin.form.replace_file' : 'admin.form.choose_file')) ?><?= is_array($upload['current']) ? '' : ' *' ?></label>
<input id="file" name="file" type="file">
<p class="form__note"><?= e(t('admin.form.file_hint', ['types' => strtoupper(implode(', ', $upload['extensions'])), 'max' => format_bytes((int) $upload['limit'])])) ?></p>
<?= $err('file') ?>
</div>
</fieldset>
<?php endif; ?>

<?php foreach ($locales as $locale): ?>
<fieldset class="admin-fieldset">
<legend><?= e(t('admin.form.content_in', ['lang' => t('admin.languages.' . $locale)])) ?></legend>
<?php foreach ($def['fields'] as $name => $spec): $key = $locale . '__' . $name; ?>
<div class="field<?= isset($errors[$key]) ? ' field--error' : '' ?>">
<label for="<?= e($key) ?>"><?= e(t('admin.fields.' . $name)) ?><?= ($spec['required'] && $locale === $locales[0]) ? ' *' : '' ?></label>
<?php if ($multiline($spec)): ?>
<textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="<?= $name === 'body' || $name === 'description' ? 10 : 4 ?>"><?= e($old[$key] ?? '') ?></textarea>
<?php else: ?>
<input id="<?= e($key) ?>" name="<?= e($key) ?>" type="text" maxlength="<?= (int) $spec['max'] ?>" value="<?= e($old[$key] ?? '') ?>">
<?php endif; ?>
<?= $err($key) ?>
</div>
<?php endforeach; ?>
</fieldset>
<?php endforeach; ?>

<fieldset class="admin-fieldset">
<legend><?= e(t('admin.form.settings')) ?></legend>
<?php foreach ($def['base'] as $name => $spec): ?>
<div class="field<?= isset($errors[$name]) ? ' field--error' : '' ?>">
<label for="<?= e($name) ?>"><?= e(t('admin.fields.' . $name)) ?></label>
<?php if ($spec['type'] === 'ref'): ?>
<select id="<?= e($name) ?>" name="<?= e($name) ?>">
<option value=""><?= e(t('admin.form.no_ref')) ?></option>
<?php foreach (($refs[$spec['ref']] ?? []) as $refId => $refTitle): ?>
<option value="<?= (int) $refId ?>"<?= ((string) ($old[$name] ?? '')) === (string) $refId ? ' selected' : '' ?>><?= e($refTitle) ?></option>
<?php endforeach; ?>
</select>
<?php elseif ($spec['type'] === 'datetime'): ?>
<input id="<?= e($name) ?>" name="<?= e($name) ?>" type="datetime-local" value="<?= e($old[$name] ?? '') ?>">
<?php elseif ($spec['type'] === 'int'): ?>
<input id="<?= e($name) ?>" name="<?= e($name) ?>" type="number" min="0" max="9999999" value="<?= e($old[$name] ?? '0') ?>">
<?php else: ?>
<input id="<?= e($name) ?>" name="<?= e($name) ?>" type="text" maxlength="<?= (int) ($spec['max'] ?? 120) ?>" value="<?= e($old[$name] ?? '') ?>">
<?php endif; ?>
<?php if ($name === 'slug'): ?><p class="form__note"><?= e(t('admin.form.slug_hint')) ?></p><?php endif; ?>
<?php if ($name === 'published_at'): ?><p class="form__note"><?= e(t('admin.form.published_hint')) ?></p><?php endif; ?>
<?= $err($name) ?>
</div>
<?php endforeach; ?>
<div class="field">
<label for="status"><?= e(t('admin.fields.status')) ?></label>
<select id="status" name="status">
<?php foreach (\App\Services\ContentRepository::STATUSES as $status): ?>
<option value="<?= e($status) ?>"<?= ($old['status'] ?? 'draft') === $status ? ' selected' : '' ?>><?= e(t('admin.status.' . $status)) ?></option>
<?php endforeach; ?>
</select>
</div>
</fieldset>

<button class="btn" type="submit"><?= e(t('admin.form.save')) ?></button>
</form>
</section>
