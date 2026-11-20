<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/** Validates the {locale} route parameter against the supported locales and activates it. */
final class LocaleMiddleware implements Middleware
{
    public function __construct(private App $app)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $locale = $request->param('locale');
        if ($locale === null || !in_array($locale, (array) $this->app->config()->get('app.locales', []), true)) {
            throw new HttpException(404);
        }
        $this->app->setLocale($locale);

        return $next($request);
    }
}
