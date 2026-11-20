<?php

declare(strict_types=1);

/**
 * Allowed upload types. Key = file extension. "mime" is what the download is served as (never the client's claim);
 * "detected" are the content types libmagic may report for a genuine file of that type. Anything else is refused.
 */
$zipOffice = static fn (string $mime): array => ['mime' => $mime, 'detected' => [$mime, 'application/zip']];
$oleOffice = static fn (string $mime): array => ['mime' => $mime, 'detected' => [$mime, 'application/msword', 'application/vnd.ms-office', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/CDFV2', 'application/x-ole-storage']];

return [
    'pdf' => ['mime' => 'application/pdf', 'detected' => ['application/pdf']],
    'docx' => $zipOffice('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    'xlsx' => $zipOffice('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
    'pptx' => $zipOffice('application/vnd.openxmlformats-officedocument.presentationml.presentation'),
    'doc' => $oleOffice('application/msword'),
    'xls' => $oleOffice('application/vnd.ms-excel'),
    'ppt' => $oleOffice('application/vnd.ms-powerpoint'),
    'txt' => ['mime' => 'text/plain; charset=UTF-8', 'detected' => ['text/plain']],
    'png' => ['mime' => 'image/png', 'detected' => ['image/png']],
    'jpg' => ['mime' => 'image/jpeg', 'detected' => ['image/jpeg']],
    'jpeg' => ['mime' => 'image/jpeg', 'detected' => ['image/jpeg']],
];
