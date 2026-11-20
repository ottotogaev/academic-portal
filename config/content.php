<?php

declare(strict_types=1);

/**
 * Editable content types. One generic admin form, repository and public renderer are driven by this file.
 *
 * table / translations / fk  database names (never taken from user input)
 * fixed                      column values every row of this type has (e.g. lessons.kind)
 * base                       non-translated columns: type int | slug | datetime | topic
 * fields                     translated columns: type text | textarea; the first one ("title") is required
 * admin_order / public_order ORDER BY clauses (b = base table)
 */
$title = ['type' => 'text', 'required' => true, 'max' => 255];
$long = static fn (bool $required = false): array => ['type' => 'textarea', 'required' => $required, 'max' => 50000];

$lesson = static fn (string $kind, array $extra = []): array => [
    'table' => 'lessons',
    'translations' => 'lesson_translations',
    'fk' => 'lesson_id',
    'fixed' => ['kind' => $kind],
    'base' => ['topic_id' => ['type' => 'topic'], 'sort_order' => ['type' => 'int']],
    'fields' => ['title' => $title, 'description' => $long(true)] + $extra,
    'admin_order' => 'b.sort_order ASC, b.id ASC',
    'public_order' => 'b.sort_order ASC, b.id ASC',
];

return [
    'topics' => [
        'table' => 'topics',
        'translations' => 'topic_translations',
        'fk' => 'topic_id',
        'fixed' => [],
        'base' => ['sort_order' => ['type' => 'int']],
        'fields' => ['title' => $title, 'description' => $long()],
        'admin_order' => 'b.sort_order ASC, b.id ASC',
        'public_order' => 'b.sort_order ASC, b.id ASC',
    ],
    'news' => [
        'table' => 'news',
        'translations' => 'news_translations',
        'fk' => 'news_id',
        'fixed' => [],
        'base' => ['slug' => ['type' => 'slug', 'max' => 120], 'published_at' => ['type' => 'datetime']],
        'fields' => ['title' => $title, 'summary' => ['type' => 'textarea', 'required' => false, 'max' => 1000], 'body' => $long(true)],
        'admin_order' => 'b.id DESC',
        'public_order' => 'b.published_at DESC, b.id DESC',
        'dated' => 'published_at',
    ],
    'assignments' => [
        'table' => 'assignments',
        'translations' => 'assignment_translations',
        'fk' => 'assignment_id',
        'fixed' => [],
        'base' => ['topic_id' => ['type' => 'topic'], 'deadline' => ['type' => 'datetime']],
        'fields' => ['title' => $title, 'description' => $long(true)],
        'admin_order' => 'b.id DESC',
        'public_order' => 'b.deadline IS NULL, b.deadline ASC, b.id DESC',
    ],
    'practical' => $lesson('practical'),
    'laboratory' => $lesson('laboratory', ['safety_information' => ['type' => 'textarea', 'required' => false, 'max' => 10000]]),
];
