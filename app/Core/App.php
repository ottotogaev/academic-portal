<?php

declare(strict_types=1);

namespace App\Core;

/** Application container and request lifecycle: request -> router -> middleware -> controller -> response. */
final class App
{
    private static ?self $current = null;

    private string $locale;
    private Router $router;
    private View $view;
    private Translator $translator;
    private ?Database $database = null;

    public function __construct(
        private string $basePath,
        private Config $config,
        private Logger $logger,
    ) {
        self::$current = $this;

        $this->locale = (string) $config->get('app.default_locale', 'uz');
        $this->translator = new Translator($basePath . '/app/Lang', (array) $config->get('app.locales', ['uz']));
        $this->view = new View($basePath . '/app/Views');
        $this->router = new Router(fn (string $class): object => new $class($this));
    }

    public static function current(): self
    {
        return self::$current ?? throw new \LogicException('Application is not initialised.');
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function logger(): Logger
    {
        return $this->logger;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function view(): View
    {
        return $this->view;
    }

    public function translator(): Translator
    {
        return $this->translator;
    }

    public function database(): Database
    {
        return $this->database ??= new Database((array) $this->config->get('database', []));
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        if (!in_array($locale, (array) $this->config->get('app.locales', []), true)) {
            throw new \InvalidArgumentException('Unsupported locale.');
        }
        $this->locale = $locale;
    }

    public function run(): void
    {
        $request = null;

        try {
            $request = Request::fromGlobals();
            $routes = require $this->basePath . '/routes/web.php';
            $routes($this->router);
            $response = $this->router->dispatch($request);
        } catch (HttpException $e) {
            $response = $this->errorResponse($e, $request);
        }

        $this->withSecurityHeaders($response)->send($request !== null && $request->method === 'HEAD');
    }

    public function errorResponse(HttpException $e, ?Request $request): Response
    {
        $status = in_array($e->status(), [400, 404, 405], true) ? $e->status() : 500;
        $this->localeFromPath($request?->path ?? '/');

        try {
            $translator = $this->translator;
            $title = $translator->get($this->locale, 'errors.' . $status . '.title') . ' · ' . $translator->get($this->locale, 'site.name');
            $body = $this->view->render('errors/http', [
                'status' => $status,
                'title' => $title,
                'noindex' => true,
            ], 'layouts/public');
        } catch (\Throwable $viewError) {
            $this->logger->error('Error page failed to render', ['message' => $viewError->getMessage()]);

            return Response::text('Error ' . $status, $status);
        }

        $response = Response::html($body, $status)->withHeader('X-Robots-Tag', 'noindex');
        foreach ($e->headers() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /** Use the locale from the first path segment for error pages when it is supported and translated. */
    private function localeFromPath(string $path): void
    {
        $segment = explode('/', trim($path, '/'))[0] ?? '';
        if ($this->translator->available($segment)) {
            $this->locale = $segment;
        }
    }

    private function withSecurityHeaders(Response $response): Response
    {
        $defaults = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];
        foreach ($defaults as $name => $value) {
            if (!$response->hasHeader($name)) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }
}
