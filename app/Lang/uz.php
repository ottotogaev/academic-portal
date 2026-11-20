<?php

declare(strict_types=1);

// Uzbek (default locale) interface strings. ru.php / en.php do not exist yet: those locales
// show the "translation unavailable" state until real translations are supplied.
return [
    'site' => [
        'name' => 'Neft va gazni qayta ishlash',
        'tagline' => 'jarayonlari va qurilmalari',
        'university' => 'Iqtisodiyot va pedagogika universiteti',
        'subject' => 'Neft va gazni kimyoviy qayta ishlash jarayonlari va qurilmalari',
        'skip' => 'Asosiy mazmunga o‘tish',
        'menu' => 'Asosiy menyu',
        'language' => 'Til',
        'pages' => 'Sahifalar',
    ],

    'nav' => [
        'home' => 'Bosh sahifa',
        'about' => 'O‘qituvchi haqida',
        'topics' => 'Fan mavzulari',
        'news' => 'Yangiliklar',
        'assignments' => 'Topshiriqlar',
        'practical' => 'Amaliy mashg‘ulotlar',
        'laboratory' => 'Laboratoriya mashg‘ulotlari',
        'materials' => 'O‘quv materiallari',
        'contact' => 'Aloqa',
    ],

    'pages' => [
        'home' => [
            'title' => 'Neft va gazni kimyoviy qayta ishlash jarayonlari va qurilmalari',
            'description' => 'Neft va gazni kimyoviy qayta ishlash jarayonlari va qurilmalari fani bo‘yicha o‘quv materiallari, topshiriqlar va yangiliklar.',
        ],
        'about' => [
            'title' => 'O‘qituvchi haqida',
            'description' => 'Lavozim, ilmiy daraja va mutaxassislik.',
        ],
        'topics' => [
            'title' => 'Fan mavzulari',
            'description' => 'Fan mavzulari ketma-ketlik bo‘yicha joylashtirilgan.',
            'empty' => 'Mavzular qo‘shilganidan so‘ng shu yerda ko‘rinadi.',
        ],
        'news' => [
            'title' => 'Yangiliklar',
            'description' => 'Yangiliklar va e‘lonlar.',
            'empty' => 'Hozircha e‘lon qilingan yangiliklar yo‘q.',
        ],
        'assignments' => [
            'title' => 'Topshiriqlar',
            'description' => 'Topshiriqlar muddati va biriktirilgan fayllari bilan.',
            'empty' => 'Topshiriqlar qo‘shilganidan so‘ng shu yerda ko‘rinadi.',
        ],
        'practical' => [
            'title' => 'Amaliy mashg‘ulotlar',
            'description' => 'Amaliy mashg‘ulotlar ro‘yxati va ko‘rsatmalari.',
            'empty' => 'Mashg‘ulotlar qo‘shilganidan so‘ng shu yerda ko‘rinadi.',
        ],
        'laboratory' => [
            'title' => 'Laboratoriya mashg‘ulotlari',
            'description' => 'Laboratoriya mashg‘ulotlari ro‘yxati.',
            'empty' => 'Mashg‘ulotlar qo‘shilganidan so‘ng shu yerda ko‘rinadi.',
        ],
        'materials' => [
            'title' => 'O‘quv materiallari',
            'description' => 'Hujjat, taqdimot va rasmlarni yuklab olish.',
            'empty' => 'Materiallar yuklanganidan so‘ng shu yerda ko‘rinadi.',
        ],
        'contact' => [
            'title' => 'Aloqa',
            'description' => 'Savol yoki taklifingizni yuboring. Xabar faqat administratorga ko‘rinadi.',
        ],
    ],

    // Confirmed data from PROJECT_SPEC.md section 2. Moves to teacher_profile_translations in Phase 3.
    'profile' => [
        'full_name' => 'Samatov San\'at Baxtiyorovich',
        'position' => 'Neft va gaz ishi kafedrasi v.b. professori',
        'degree' => 'Kimyo fanlari falsafa doktori (PhD)',
        'specialization' => 'Neft va gazni kimyoviy qayta ishlash jarayonlari va qurilmalari',
        'photo_alt' => 'Samatov San\'at Baxtiyorovich',
    ],

    'home' => [
        // Headline split into three parts so the middle and last can be accented.
        'hero_a' => 'Neft va gazni',
        'hero_b' => 'kimyoviy qayta ishlash',
        'hero_c' => 'jarayonlari va qurilmalari',
        'quick_title' => 'Tezkor o‘tish',
        'quick_note' => 'Sayt bo‘limlariga bir bosishda o‘ting.',
        'news_title' => 'So‘nggi yangiliklar',
        'news_empty' => 'Hozircha e‘lon qilingan yangiliklar yo‘q.',
        'materials_title' => 'Yangi materiallar',
        'materials_empty' => 'Materiallar yuklanganidan so‘ng shu yerda ko‘rinadi.',
        'all' => 'Hammasi',
        'quick' => [
            'about' => 'Lavozim, ilmiy daraja va mutaxassislik.',
            'topics' => 'Mavzular bo‘yicha tartiblangan o‘quv kontent.',
            'assignments' => 'Muddatlar va biriktirilgan fayllar bilan.',
            'practical' => 'Mashg‘ulotlar va ko‘rsatmalar.',
            'laboratory' => 'Laboratoriya darslari ro‘yxati.',
            'materials' => 'PDF, taqdimot va hujjatlarni yuklab olish.',
        ],
    ],

    'about' => [
        'position' => 'Lavozim',
        'degree' => 'Ilmiy daraja',
        'university' => 'Universitet',
        'specialization' => 'Mutaxassislik',
    ],

    'footer' => [
        'cta_label' => 'Aloqa',
        'cta_title' => 'Savol yoki taklifingiz bormi?',
        'cta_button' => 'Xabar yuborish',
    ],

    'contact' => [
        'email' => 'Elektron pochta',
        'phone' => 'Telefon',
        'university' => 'Universitet',
        'name' => 'Ismingiz',
        'sender_email' => 'Elektron pochta',
        'subject' => 'Mavzu',
        'message' => 'Xabar',
        'submit' => 'Yuborish',
        'unavailable' => 'Xabar yuborish hozircha ishlamaydi. Tez orada qo‘shiladi.',
    ],

    'errors' => [
        'home' => 'Bosh sahifaga qaytish',
        '400' => ['title' => 'Noto‘g‘ri so‘rov', 'text' => 'So‘rovni qayta ishlab bo‘lmadi.'],
        '404' => ['title' => 'Sahifa topilmadi', 'text' => 'Siz izlagan sahifa mavjud emas yoki boshqa joyga ko‘chirilgan.'],
        '405' => ['title' => 'Usul ruxsat etilmagan', 'text' => 'Bu sahifa uchun so‘rov usuli qo‘llab-quvvatlanmaydi.'],
        '500' => ['title' => 'Xatolik yuz berdi', 'text' => 'Iltimos, keyinroq qayta urinib ko‘ring.'],
    ],
];
