<?php
/**
 * Contact page. The form is rendered disabled until feedback handling (CSRF, validation,
 * rate limiting, admin inbox) exists in Phase 5. Email and phone stay hidden until they are supplied.
 */
?>
<?= partial('components/banner', [
    'heading' => t('nav.contact'),
    'text' => t('pages.contact.description'),
]) ?>
<section class="section">
<div class="container contact">
<div class="contact__info">
<div class="contact__row">
<p class="contact__label"><?= e(t('contact.university')) ?></p>
<p><?= e(t('site.university')) ?></p>
</div>
</div>

<form class="form" aria-describedby="form-note">
<fieldset class="form__fields" disabled>
<div class="form__pair">
<div class="field"><label for="f-name"><?= e(t('contact.name')) ?></label><input id="f-name" name="name" type="text" autocomplete="name"></div>
<div class="field"><label for="f-email"><?= e(t('contact.sender_email')) ?></label><input id="f-email" name="email" type="email" autocomplete="email"></div>
</div>
<div class="field"><label for="f-subject"><?= e(t('contact.subject')) ?></label><input id="f-subject" name="subject" type="text"></div>
<div class="field"><label for="f-message"><?= e(t('contact.message')) ?></label><textarea id="f-message" name="message" rows="6"></textarea></div>
<div><button class="btn" type="submit"><?= e(t('contact.submit')) ?></button></div>
</fieldset>
<p class="form__note" id="form-note"><?= e(t('contact.unavailable')) ?></p>
</form>
</div>
</section>
