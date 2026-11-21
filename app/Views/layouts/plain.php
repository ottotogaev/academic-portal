<?php
/**
 * Minimal layout for states that have no translated chrome (unavailable locale).
 *
 * @var string $content
 */
?>
<!doctype html>
<html lang="<?= e(locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Academic Portal</title>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('images/favicon.svg')) ?>">
<link rel="preload" href="<?= e(asset('fonts/manrope-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>
<?= $content ?>
</body>
</html>
