<?= partial('components/banner', [
    'heading' => t('nav.about'),
    'text' => t('pages.about.description'),
]) ?>
<section class="section">
<div class="container profile">
<div class="profile__photo">
<img src="<?= e(asset('images/professor.jpg')) ?>" alt="<?= e(t('profile.photo_alt')) ?>" width="400" height="500">
</div>
<div class="profile__body">
<h2 class="profile__name"><?= e(t('profile.full_name')) ?></h2>
<dl class="facts">
<div class="facts__row"><dt><?= e(t('about.position')) ?></dt><dd><?= e(t('profile.position')) ?></dd></div>
<div class="facts__row"><dt><?= e(t('about.degree')) ?></dt><dd><?= e(t('profile.degree')) ?></dd></div>
<div class="facts__row"><dt><?= e(t('about.university')) ?></dt><dd><?= e(t('site.university')) ?></dd></div>
<div class="facts__row"><dt><?= e(t('about.specialization')) ?></dt><dd><?= e(t('profile.specialization')) ?></dd></div>
</dl>
</div>
</div>
</section>
