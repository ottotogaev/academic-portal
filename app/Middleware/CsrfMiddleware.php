<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/** Rejects state-changing requests that do not carry the session's CSRF token. */
final class CsrfMiddleware implements Middleware
{
    public function __construct(private App $app)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)
            && !$this->app->csrf()->valid($request->input('_token'))) {
            throw new HttpException(400);
        }

        return $next($request);
    }
}
