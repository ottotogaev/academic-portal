<?php
/**
 * Contact page and form. Email and phone are shown only when set in the admin profile.
 *
 * @var array<string,string> $values
 * @var array<string,string> $errors
 * @var bool                 $sent
 * @var ?string              $formError
 */
$email = profile('email');
$phone = profile('phone');
$err = static fn (string $key): string => isset($errors[$key])
    ? '<p class="field__error" role="alert">' . e(t('contact.errors.' . $errors[$key])) . '</p>'
    : '';
?>
<?= partial('components/banner', [
    'heading' => t('nav.contact'),
    'text' => t('pages.contact.description'),
]) ?>
<section class="section">
<div class="container contact">
<div class="contact__info">
<?php if ($email !== ''): ?>
<div class="contact__row">
<p class="contact__label"><?= e(t('contact.email')) ?></p>
<p><a href="mailto:<?= e(rawurlencode($email)) ?>"><?= e($email) ?></a></p>
</div>
<?php endif; ?>
<?php if ($phone !== ''): ?>
<div class="contact__row">
<p class="contact__label"><?= e(t('contact.phone')) ?></p>
<p><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a></p>
</div>
<?php endif; ?>
<div class="contact__row">
<p class="contact__label"><?= e(t('contact.university')) ?></p>
<p><?= e(t('site.university')) ?></p>
</div>
</div>

<?php $live = contact_enabled(); ?>
<form class="form" method="post" action="<?= e(url('contact')) ?>" novalidate>
<?php if ($live): ?>
<?= csrf_field() ?>
<?php else: ?>
<p class="form__note form__note--top" role="status"><?= e(t('contact.unavailable')) ?></p>
<?php endif; ?>
<?php if ($sent): ?>
<p class="form__ok" role="status"><?= e(t('contact.sent')) ?></p>
<?php endif; ?>
<?php if ($formError !== null): ?>
<p class="form__error" role="alert"><?= e($formError) ?></p>
<?php endif; ?>
<fieldset class="form__fields"<?= $live ? '' : ' disabled' ?>>
<div class="form__pair">
<div class="field<?= isset($errors['name']) ? ' field--error' : '' ?>"><label for="f-name"><?= e(t('contact.name')) ?></label><input id="f-name" name="name" type="text" autocomplete="name" maxlength="120" required value="<?= e($values['name'] ?? '') ?>"><?= $err('name') ?></div>
<div class="field<?= isset($errors['email']) ? ' field--error' : '' ?>"><label for="f-email"><?= e(t('contact.sender_email')) ?></label><input id="f-email" name="email" type="email" autocomplete="email" maxlength="190" required value="<?= e($values['email'] ?? '') ?>"><?= $err('email') ?></div>
</div>
<div class="field<?= isset($errors['subject']) ? ' field--error' : '' ?>"><label for="f-subject"><?= e(t('contact.subject')) ?></label><input id="f-subject" name="subject" type="text" maxlength="190" value="<?= e($values['subject'] ?? '') ?>"><?= $err('subject') ?></div>
<div class="field<?= isset($errors['message']) ? ' field--error' : '' ?>"><label for="f-message"><?= e(t('contact.message')) ?></label><textarea id="f-message" name="message" rows="6" maxlength="5000" required><?= e($values['message'] ?? '') ?></textarea><?= $err('message') ?></div>
<?php if ($live): ?><div class="hp" aria-hidden="true"><label>Website <input name="website" type="text" tabindex="-1" autocomplete="off"></label></div><?php endif; ?>
<div><button class="btn" type="submit"<?= $live ? '' : ' disabled aria-disabled="true"' ?>><?= e(t('contact.submit')) ?></button></div>
</fieldset>
<p class="form__note"><?= e(t('contact.privacy')) ?></p>
</form>
</div>
</section>
