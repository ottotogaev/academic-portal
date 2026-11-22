<?php

declare(strict_types=1);

use App\Core\Env;

// Public navigation. 'path' is the URL segment after the locale prefix.
// The contact entry exists only while FEATURE_CONTACT=true; without it the page has no route (404) and no menu item.
$items = [
    ['key' => 'home', 'path' => ''],
    ['key' => 'about', 'path' => 'about'],
    ['key' => 'topics', 'path' => 'topics'],
    ['key' => 'news', 'path' => 'news'],
    ['key' => 'assignments', 'path' => 'assignments'],
    ['key' => 'practical', 'path' => 'practical-lessons'],
    ['key' => 'laboratory', 'path' => 'laboratory-lessons'],
    ['key' => 'materials', 'path' => 'materials'],
    ['key' => 'contact', 'path' => 'contact'],
];

return array_values(array_filter(
    $items,
    static fn (array $item): bool => $item['key'] !== 'contact' || Env::bool('FEATURE_CONTACT', false)
));
