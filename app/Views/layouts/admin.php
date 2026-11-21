<?php
/**
 * Admin layout: Uzbek only, never indexed, no public navigation.
 *
 * @var string $content
 * @var string $title
 */
?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · <?= e(t('admin.title')) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('images/favicon.svg')) ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">
<?php if (app()->auth()->check()): ?>
<nav class="admin-nav" aria-label="<?= e(t('admin.title')) ?>">
<a href="/admin/dashboard"><?= e(t('admin.nav.dashboard')) ?></a>
<?php foreach (array_keys((array) app()->config()->get('content', [])) as $navType): ?>
<a href="/admin/<?= e($navType) ?>"><?= e(t('admin.types.' . $navType)) ?></a>
<?php endforeach; ?>
<?php
$newMessages = 0;
try {
    $newMessages = app()->feedback()->countNew();
} catch (\Throwable) {
    // The inbox badge is a convenience; a database problem must not break the page.
}
?>
<a href="/admin/feedback"><?= e(t('admin.feedback.title')) ?><?php if ($newMessages > 0): ?> <span class="badge"><?= (int) $newMessages ?></span><?php endif; ?></a>
<a href="/admin/profile"><?= e(t('admin.profile.title')) ?></a>
</nav>
<?php endif; ?>
<main class="admin__main">
<?= $content ?>
</main>
</body>
</html>
