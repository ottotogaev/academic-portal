<?php

declare(strict_types=1);

/**
 * Schema behaviour test on a DISPOSABLE database (run migrations first: php bin/migrate.php).
 * It inserts and deletes rows: never run it against real data. php tests/schema-test.php
 */

$basePath = dirname(__DIR__);
$app = require $basePath . '/bootstrap/app.php';
$pdo = $app->database()->pdo();
$pass = 0;
$fail = 0;
$check = static function (string $name, bool $ok) use (&$pass, &$fail): void {
    $ok ? $pass++ : $fail++;
    echo ($ok ? '  ok   ' : '  FAIL ') . $name . "\n";
};
$fails = static function (callable $fn): bool {
    try {
        $fn();
    } catch (PDOException) {
        return true;
    }

    return false;
};
$now = gmdate('Y-m-d H:i:s');
$ins = static function (string $sql, array $p = []) use ($pdo): int {
    $pdo->prepare($sql)->execute($p);

    return (int) $pdo->lastInsertId();
};

foreach (['feedback_messages', 'teacher_profile_translations', 'teacher_profiles', 'material_translations', 'materials',
    'material_category_translations', 'material_categories', 'lesson_translations', 'lessons', 'assignment_translations',
    'assignments', 'news_translations', 'news', 'topic_translations', 'topics'] as $table) {
    $pdo->exec("DELETE FROM {$table}");
}

$topic = $ins('INSERT INTO topics (sort_order, status, created_at, updated_at) VALUES (1, \'published\', :n, :n2)', ['n' => $now, 'n2' => $now]);
$ins('INSERT INTO topic_translations (topic_id, locale, title) VALUES (:t, \'uz\', \'Neftni qayta ishlash\')', ['t' => $topic]);
$check('one translation per topic and locale', $fails(fn () => $ins('INSERT INTO topic_translations (topic_id, locale, title) VALUES (:t, \'uz\', \'dup\')', ['t' => $topic])));
$ins('INSERT INTO topic_translations (topic_id, locale, title) VALUES (:t, \'ru\', \'Переработка нефти\')', ['t' => $topic]);
$check('unicode survives the round trip (utf8mb4)', (string) $pdo->query("SELECT title FROM topic_translations WHERE locale='ru'")->fetchColumn() === 'Переработка нефти');
$check('translation needs an existing topic (FK)', $fails(fn () => $ins('INSERT INTO topic_translations (topic_id, locale, title) VALUES (99999, \'uz\', \'x\')')));

$news = $ins('INSERT INTO news (slug, status, created_at, updated_at) VALUES (\'birinchi-yangilik\', \'draft\', :n, :n2)', ['n' => $now, 'n2' => $now]);
$check('news slug is unique', $fails(fn () => $ins('INSERT INTO news (slug, status, created_at, updated_at) VALUES (\'birinchi-yangilik\', \'draft\', :n, :n2)', ['n' => $now, 'n2' => $now])));
$ins('INSERT INTO news_translations (news_id, locale, title, body) VALUES (:i, \'uz\', \'T\', \'B\')', ['i' => $news]);

$as = $ins('INSERT INTO assignments (topic_id, status, created_at, updated_at) VALUES (:t, \'published\', :n, :n2)', ['t' => $topic, 'n' => $now, 'n2' => $now]);
$ins('INSERT INTO assignment_translations (assignment_id, locale, title, description) VALUES (:a, \'uz\', \'T\', \'D\')', ['a' => $as]);
$lesson = $ins('INSERT INTO lessons (kind, topic_id, status, created_at, updated_at) VALUES (\'laboratory\', :t, \'draft\', :n, :n2)', ['t' => $topic, 'n' => $now, 'n2' => $now]);
$ins('INSERT INTO lesson_translations (lesson_id, locale, title, description, safety_information) VALUES (:l, \'uz\', \'T\', \'D\', NULL)', ['l' => $lesson]);
$check('topic with dependent assignments/lessons cannot be deleted (RESTRICT)', $fails(fn () => $pdo->exec("DELETE FROM topics WHERE id = {$topic}")));

$cat = $ins('INSERT INTO material_categories (created_at, updated_at) VALUES (:n, :n2)', ['n' => $now, 'n2' => $now]);
$mat = $ins('INSERT INTO materials (category_id, stored_name, original_filename, mime_type, file_size, created_at, updated_at) VALUES (:c, :s, \'a.pdf\', \'application/pdf\', 10, :n, :n2)', ['c' => $cat, 's' => bin2hex(random_bytes(16)) . '.pdf', 'n' => $now, 'n2' => $now]);
$check('stored file name is unique', $fails(fn () => $ins('INSERT INTO materials (stored_name, original_filename, mime_type, file_size, created_at, updated_at) VALUES ((SELECT stored_name FROM (SELECT stored_name FROM materials LIMIT 1) x), \'b.pdf\', \'application/pdf\', 1, :n, :n2)', ['n' => $now, 'n2' => $now])));
$check('category with materials cannot be deleted (RESTRICT)', $fails(fn () => $pdo->exec("DELETE FROM material_categories WHERE id = {$cat}")));

$pdo->exec("DELETE FROM assignments");
$pdo->exec("DELETE FROM lessons");
$check('deleting an assignment/lesson removes its translations (CASCADE)', (int) $pdo->query('SELECT COUNT(*) FROM assignment_translations')->fetchColumn() === 0 && (int) $pdo->query('SELECT COUNT(*) FROM lesson_translations')->fetchColumn() === 0);
$pdo->exec("DELETE FROM materials");
$pdo->exec("DELETE FROM material_categories");
$pdo->exec("DELETE FROM topics");
$check('deleting a topic removes its translations (CASCADE)', (int) $pdo->query('SELECT COUNT(*) FROM topic_translations')->fetchColumn() === 0);

$prof = $ins('INSERT INTO teacher_profiles (created_at, updated_at) VALUES (:n, :n2)', ['n' => $now, 'n2' => $now]);
$ins('INSERT INTO teacher_profile_translations (teacher_profile_id, locale, full_name) VALUES (:p, \'uz\', \'Test\')', ['p' => $prof]);
$check('profile fields other than the name are optional', (string) $pdo->query('SELECT biography FROM teacher_profile_translations')->fetchColumn() === '');
$ins('INSERT INTO feedback_messages (sender_name, sender_email, message, client_hash, created_at, updated_at) VALUES (\'A\', \'a@b.uz\', \'hi\', :h, :n, :n2)', ['h' => hash('sha256', 'x'), 'n' => $now, 'n2' => $now]);
$check('feedback defaults to status "new"', (string) $pdo->query('SELECT status FROM feedback_messages')->fetchColumn() === 'new');

foreach (['feedback_messages', 'teacher_profile_translations', 'teacher_profiles', 'news_translations', 'news', 'topic_translations'] as $table) {
    $pdo->exec("DELETE FROM {$table}");
}
echo "\n{$pass} passed, {$fail} failed\n";
exit($fail === 0 ? 0 : 1);
