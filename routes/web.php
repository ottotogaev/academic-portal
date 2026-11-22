<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\ContentController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\FeedbackController;
use App\Controllers\Admin\ProfileController;
use App\Controllers\Public\PageController;
use App\Core\App;
use App\Core\Request;
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

    $guard = [AdminAuthMiddleware::class];
    $post = [AdminAuthMiddleware::class, CsrfMiddleware::class];
    if (contact_enabled()) {
        $router->get('/admin/feedback', [FeedbackController::class, 'index'], $guard);
        $router->get('/admin/feedback/{id}', [FeedbackController::class, 'show'], $guard);
        $router->post('/admin/feedback/{id}/status', [FeedbackController::class, 'status'], $post);
        $router->post('/admin/feedback/{id}/delete', [FeedbackController::class, 'destroy'], $post);
    }
    $router->get('/admin/profile', [ProfileController::class, 'show'], $guard);
    $router->post('/admin/profile', [ProfileController::class, 'save'], $post);

    // Content management: literal routes per type from config/content.php.
    foreach (array_keys((array) App::current()->config()->get('content', [])) as $type) {
        $guard = [AdminAuthMiddleware::class];
        $post = [AdminAuthMiddleware::class, CsrfMiddleware::class];
        $c = static fn (string $method): \Closure => static fn (Request $r): Response => (new ContentController(App::current(), $type))->$method($r);
        $router->get('/admin/' . $type, $c('index'), $guard);
        $router->get('/admin/' . $type . '/new', $c('create'), $guard);
        $router->post('/admin/' . $type, $c('store'), $post);
        $router->get('/admin/' . $type . '/{id}/edit', $c('edit'), $guard);
        $router->post('/admin/' . $type . '/{id}', $c('update'), $post);
        $router->post('/admin/' . $type . '/{id}/delete', $c('destroy'), $post);
    }

    // Locale-prefixed public pages. The route list comes from config/navigation.php.
    foreach ((array) App::current()->config()->get('navigation', []) as $item) {
        $pattern = $item['path'] === '' ? '/{locale}' : '/{locale}/' . $item['path'];
        $router->get($pattern, [PageController::class, $item['key']], [LocaleMiddleware::class]);
    }

    if (contact_enabled()) {
        $router->post('/{locale}/contact', [PageController::class, 'contactSubmit'], [LocaleMiddleware::class, CsrfMiddleware::class]);
    }
    $router->get('/{locale}/materials/{id}/download', [PageController::class, 'download'], [LocaleMiddleware::class]);
    $router->get('/{locale}/news/{slug}', [PageController::class, 'newsItem'], [LocaleMiddleware::class]);
};
