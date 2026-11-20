<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;

final class DashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->page('admin/dashboard', [
            'title' => t('admin.dashboard.title'),
            'username' => $this->app->auth()->username() ?? '',
        ]);
    }
}
