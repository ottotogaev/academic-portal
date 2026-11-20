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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>
<?= $content ?>
</body>
</html>
