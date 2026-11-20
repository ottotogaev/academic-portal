<?php
/**
 * @var ?array<string,mixed> $item null only when the locale has no translation (the layout shows that state)
 */
if ($item === null) {
    return;
}
?>
<?= partial('components/banner', [
    'heading' => (string) $item['title'],
    'text' => format_date((string) $item['published_at']),
]) ?>
<section class="section">
<div class="container container--narrow">
<article class="prose prose--page"><?= text_html((string) $item['body']) ?></article>
<p class="section__foot"><a class="link-accent" href="<?= e(url('news')) ?>">← <?= e(t('pages.news.back')) ?></a></p>
</div>
</section>
