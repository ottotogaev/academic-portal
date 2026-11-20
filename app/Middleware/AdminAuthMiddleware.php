<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;

/** Every admin route except login goes through this: no session, no access. Enforced on the server, not by hiding links. */
final class AdminAuthMiddleware implements Middleware
{
    public function __construct(private App $app)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->app->auth()->check()) {
            return Response::redirect('/admin/login')->withHeader('Cache-Control', 'no-store');
        }

        return $next($request);
    }
}
