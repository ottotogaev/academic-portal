<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;

final class DashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        $counts = [];
        foreach (array_keys((array) $this->app->config()->get('content', [])) as $type) {
            $counts[$type] = $this->app->content($type)->count();
        }

        return $this->page('admin/dashboard', [
            'counts' => $counts,
            'title' => t('admin.dashboard.title'),
            'username' => $this->app->auth()->username() ?? '',
        ]);
    }
}
