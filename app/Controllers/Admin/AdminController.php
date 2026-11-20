<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Response;

/** Base for admin pages: always uncached and never indexed. */
abstract class AdminController extends Controller
{
    /** @param array<string,mixed> $data */
    protected function page(string $template, array $data = [], int $status = 200): Response
    {
        $title = (string) ($data['title'] ?? t('admin.title'));
        $body = $this->app->view()->render($template, $data + ['title' => $title], 'layouts/admin');

        return Response::html($body, $status)
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Robots-Tag', 'noindex');
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect($path)->withHeader('Cache-Control', 'no-store');
    }
}
