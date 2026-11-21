<?php
/**
 * Public page layout.
 *
 * @var string $content rendered page body
 */
$title ??= t('site.name');
$description ??= '';
$canonical ??= '';
$pagePath ??= '';
$pageKey ??= '';
$footerCta ??= false;
$noindex ??= false;
?>
<!doctype html>
<html lang="<?= e(locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<?php if ($description !== ''): ?>
<meta name="description" content="<?= e($description) ?>">
<?php endif; ?>
<?php if ($noindex): ?>
<meta name="robots" content="noindex">
<?php endif; ?>
<?php if ($canonical !== ''): ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
<meta name="theme-color" content="#070E13">
<link rel="icon" type="image/svg+xml" href="<?= e(asset('images/favicon.svg')) ?>">
<link rel="preload" href="<?= e(asset('fonts/manrope-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main"><?= e(t('site.skip')) ?></a>
<?= partial('components/header', ['pageKey' => $pageKey, 'pagePath' => $pagePath]) ?>
<main id="main" tabindex="-1">
<?= $content ?>
</main>
<?= partial('components/footer', ['footerCta' => $footerCta]) ?>
</body>
</html>
