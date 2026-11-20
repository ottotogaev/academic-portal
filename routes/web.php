<?php

declare(strict_types=1);

use App\Controllers\Public\PageController;
use App\Core\App;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\LocaleMiddleware;

return static function (Router $router): void {
    // Liveness only: no database access, no configuration details.
    $router->get('/health', static fn (): Response => Response::text('ok'));

    // Bare domain goes to the default locale.
    $router->get('/', static fn (): Response => Response::redirect(url()));

    // Locale-prefixed public pages. The route list comes from config/navigation.php.
    foreach ((array) App::current()->config()->get('navigation', []) as $item) {
        $pattern = $item['path'] === '' ? '/{locale}' : '/{locale}/' . $item['path'];
        $router->get($pattern, [PageController::class, $item['key']], [LocaleMiddleware::class]);
    }
};
