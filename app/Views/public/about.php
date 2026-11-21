<?= partial('components/banner', [
    'heading' => t('nav.about'),
    'text' => t('pages.about.description'),
]) ?>
<section class="section">
<div class="container profile">
<div class="profile__photo">
<img src="<?= e(asset('images/professor.jpg')) ?>" alt="<?= e(profile('full_name')) ?>" width="400" height="500">
</div>
<div class="profile__body">
<h2 class="profile__name"><?= e(profile('full_name')) ?></h2>
<dl class="facts">
<?php if (profile('position') !== ''): ?>
<div class="facts__row"><dt><?= e(t('about.position')) ?></dt><dd><?= e(profile('position')) ?></dd></div>
<?php endif; ?>
<?php if (profile('degree') !== ''): ?>
<div class="facts__row"><dt><?= e(t('about.degree')) ?></dt><dd><?= e(profile('degree')) ?></dd></div>
<?php endif; ?>
<div class="facts__row"><dt><?= e(t('about.university')) ?></dt><dd><?= e(t('site.university')) ?></dd></div>
<?php if (profile('specialization') !== ''): ?>
<div class="facts__row"><dt><?= e(t('about.specialization')) ?></dt><dd><?= e(profile('specialization')) ?></dd></div>
<?php endif; ?>
</dl>
<?php foreach (['biography' => 'about.biography', 'education' => 'about.education', 'experience' => 'about.experience'] as $field => $label): ?>
<?php if (profile($field) !== ''): ?>
<h3 class="profile__sub"><?= e(t($label)) ?></h3>
<div class="prose"><?= text_html(profile($field)) ?></div>
<?php endif; ?>
<?php endforeach; ?>
</div>
</div>
</section>
