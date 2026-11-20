<?php
/**
 * Shown for locales that have no translation yet (ru, en). The notice is a system message, not site content.
 *
 * @var string $pagePath
 */
?>
<main class="plain">
<div class="plain__card">
<p class="plain__line">Bu til uchun tarjima hozircha mavjud emas.</p>
<p class="plain__line" lang="ru">Перевод на этот язык пока недоступен.</p>
<p class="plain__line" lang="en">This language is not available yet.</p>
<a class="btn" href="<?= e(url($pagePath, 'uz')) ?>" hreflang="uz">O‘zbekcha</a>
</div>
</main>
