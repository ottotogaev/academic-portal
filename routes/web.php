<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Public\PageController;
use App\Core\App;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AdminAuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\LocaleMiddleware;

return static function (Router $router): void {
    // Liveness only: no database access, no configuration details.
    $router->get('/health', static fn (): Response => Response::text('ok'));

    // Bare domain goes to the default locale.
    $router->get('/', static fn (): Response => Response::redirect(url()));

    // Administration (Uzbek only, outside the locale prefix). Everything except login needs a session;
    // every POST needs a CSRF token.
    $router->get('/admin', static fn (): Response => Response::redirect('/admin/dashboard'));
    $router->get('/admin/login', [AuthController::class, 'showLogin']);
    $router->post('/admin/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
    $router->post('/admin/logout', [AuthController::class, 'logout'], [AdminAuthMiddleware::class, CsrfMiddleware::class]);
    $router->get('/admin/dashboard', [DashboardController::class, 'index'], [AdminAuthMiddleware::class]);
    $router->get('/admin/password', [AuthController::class, 'showPassword'], [AdminAuthMiddleware::class]);
    $router->post('/admin/password', [AuthController::class, 'changePassword'], [AdminAuthMiddleware::class, CsrfMiddleware::class]);

    // Locale-prefixed public pages. The route list comes from config/navigation.php.
    foreach ((array) App::current()->config()->get('navigation', []) as $item) {
        $pattern = $item['path'] === '' ? '/{locale}' : '/{locale}/' . $item['path'];
        $router->get($pattern, [PageController::class, $item['key']], [LocaleMiddleware::class]);
    }
};
