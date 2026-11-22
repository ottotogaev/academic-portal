<?php

declare(strict_types=1);

// English interface strings. Keys must match uz.php (checked by tests/run.php); the admin panel is Uzbek only.
return [
    'site' => [
        'name' => 'Oil and Gas Processing',
        'tagline' => 'processes and equipment',
        'university' => 'University of Economics and Pedagogy',
        'subject' => 'Processes and Equipment for Chemical Processing of Oil and Gas',
        'skip' => 'Skip to main content',
        'menu' => 'Main menu',
        'language' => 'Language',
        'pages' => 'Pages',
    ],

    'nav' => [
        'home' => 'Home',
        'about' => 'About the lecturer',
        'topics' => 'Course topics',
        'news' => 'News',
        'assignments' => 'Assignments',
        'practical' => 'Practical classes',
        'laboratory' => 'Laboratory classes',
        'materials' => 'Study materials',
        'contact' => 'Contact',
    ],

    'pages' => [
        'home' => [
            'title' => 'Processes and Equipment for Chemical Processing of Oil and Gas',
            'description' => 'Study materials, assignments and news for the course "Processes and Equipment for Chemical Processing of Oil and Gas".',
        ],
        'about' => [
            'title' => 'About the lecturer',
            'description' => 'Position, academic degree and specialization.',
        ],
        'topics' => [
            'title' => 'Course topics',
            'description' => 'Course topics in the order they are studied.',
            'empty' => 'Topics will appear here once they are added.',
            'topic' => 'Topic',
        ],
        'news' => [
            'title' => 'News',
            'description' => 'News and announcements.',
            'empty' => 'No news has been published yet.',
            'back' => 'All news',
        ],
        'assignments' => [
            'title' => 'Assignments',
            'description' => 'Assignments with their deadlines.',
            'deadline' => 'Deadline',
            'empty' => 'Assignments will appear here once they are added.',
        ],
        'practical' => [
            'title' => 'Practical classes',
            'description' => 'List of practical classes and instructions.',
            'empty' => 'Classes will appear here once they are added.',
        ],
        'laboratory' => [
            'title' => 'Laboratory classes',
            'description' => 'List of laboratory classes.',
            'safety' => 'Safety',
            'empty' => 'Classes will appear here once they are added.',
        ],
        'materials' => [
            'title' => 'Study materials',
            'description' => 'Documents, presentations and images to download.',
            'download' => 'Download',
            'other' => 'Other materials',
            'empty' => 'Materials will appear here once they are uploaded.',
        ],
        'contact' => [
            'title' => 'Contact',
            'description' => 'Send a question or suggestion. Only the administrator can read the message.',
        ],
    ],

    'profile' => [
        'full_name' => 'Samatov San\'at Baxtiyorovich',
        'position' => 'Acting Professor, Department of Oil and Gas Engineering',
        'degree' => 'Doctor of Philosophy (PhD) in Chemical Sciences',
        'specialization' => 'Processes and Equipment for Chemical Processing of Oil and Gas',
        'photo_alt' => 'Samatov San\'at Baxtiyorovich',
    ],

    'home' => [
        'hero_a' => 'Processes and equipment for',
        'hero_b' => 'chemical processing',
        'hero_c' => 'of oil and gas',
        'quick_title' => 'Quick links',
        'quick_note' => 'Jump to any section of the site in one click.',
        'news_title' => 'Latest news',
        'news_empty' => 'No news has been published yet.',
        'materials_title' => 'New materials',
        'materials_empty' => 'Materials will appear here once they are uploaded.',
        'all' => 'View all',
        'quick' => [
            'about' => 'Position, academic degree and specialization.',
            'topics' => 'Course content organised by topic.',
            'assignments' => 'With deadlines.',
            'practical' => 'Classes and instructions.',
            'laboratory' => 'List of laboratory classes.',
            'materials' => 'PDFs, presentations and documents to download.',
        ],
    ],

    'about' => [
        'position' => 'Position',
        'degree' => 'Academic degree',
        'university' => 'University',
        'specialization' => 'Specialization',
        'biography' => 'Short biography',
        'education' => 'Education',
        'experience' => 'Work experience',
    ],

    'footer' => [
        'cta_label' => 'Contact',
        'cta_title' => 'Have a question or a suggestion?',
        'cta_button' => 'Send a message',
    ],

    'contact' => [
        'email' => 'Email',
        'phone' => 'Phone',
        'university' => 'University',
        'name' => 'Your name',
        'sender_email' => 'Email',
        'subject' => 'Subject',
        'message' => 'Message',
        'submit' => 'Send',
        'unavailable' => 'Sending messages is not available yet. It will be added soon.',
        'sent' => 'Your message has been sent. Thank you!',
        'privacy' => 'Only the administrator can read your message; it is never published.',
        'too_fast' => 'Please press "Send" again in a few seconds.',
        'rate_limited' => 'Too many messages have been sent. Please try again later.',
        'failed' => 'The message could not be sent. Please try again later.',
        'errors' => [
            'required' => 'Please fill in this field.',
            'email' => 'Enter a valid email address.',
            'too_long' => 'The text is too long.',
            'invalid' => 'Invalid characters were entered.',
            'message_short' => 'The message must be at least 10 characters long.',
        ],
    ],

    'errors' => [
        'home' => 'Back to the home page',
        '400' => ['title' => 'Bad request', 'text' => 'The request could not be processed.'],
        '404' => ['title' => 'Page not found', 'text' => 'The page you are looking for does not exist or has been moved.'],
        '405' => ['title' => 'Method not allowed', 'text' => 'This request method is not supported for this page.'],
        '500' => ['title' => 'Something went wrong', 'text' => 'Please try again later.'],
    ],
];
