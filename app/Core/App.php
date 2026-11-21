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
    private ?Session $session = null;
    private ?Csrf $csrf = null;
    private ?Auth $auth = null;
    /** @var array<string,array<string,string>> */
    private array $profileCache = [];

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

    /** Repository for a content type from config/content.php. */
    public function content(string $type): \App\Services\ContentRepository
    {
        $def = $this->config->get('content.' . $type);
        if (!is_array($def)) {
            throw new \InvalidArgumentException('Unknown content type.');
        }

        return new \App\Services\ContentRepository(
            $this->database()->pdo(),
            $def,
            (string) $this->config->get('app.default_locale', 'uz')
        );
    }

    public function feedback(): \App\Services\FeedbackRepository
    {
        return new \App\Services\FeedbackRepository($this->database()->pdo());
    }

    public function profileRepository(): \App\Services\ProfileRepository
    {
        return new \App\Services\ProfileRepository($this->database()->pdo(), (string) $this->config->get('app.default_locale', 'uz'));
    }

    /**
     * Teacher profile for the current locale, as plain strings. The confirmed data in the language file is used
     * until a profile has been saved in the admin; after that the database is the source (empty = hidden).
     *
     * @return array<string,string> keys: full_name position degree specialization biography education experience email phone
     */
    public function profile(): array
    {
        if (isset($this->profileCache[$this->locale])) {
            return $this->profileCache[$this->locale];
        }
        $tr = fn (string $key): string => $this->translator->get($this->locale, $key);
        $profile = [
            'full_name' => $tr('profile.full_name'), 'position' => $tr('profile.position'), 'degree' => $tr('profile.degree'),
            'specialization' => $tr('profile.specialization'), 'biography' => '', 'education' => '', 'experience' => '',
            'email' => '', 'phone' => '',
        ];
        try {
            $row = $this->profileRepository()->get();
        } catch (\Throwable $e) {
            $this->logger->error('Profile lookup failed', ['message' => $e->getMessage()]);
            $row = null;
        }
        if ($row !== null) {
            $profile['email'] = (string) ($row['email'] ?? '');
            $profile['phone'] = (string) ($row['phone'] ?? '');
            $t = $row['translations'][$this->locale] ?? null;
            if ($t !== null) {
                foreach (['full_name', 'position', 'specialization', 'biography', 'education', 'experience'] as $field) {
                    $profile[$field] = (string) ($t[$field] ?? '');
                }
                $profile['degree'] = (string) ($t['academic_degree'] ?? '');
            }
        }

        return $this->profileCache[$this->locale] = $profile;
    }

    public function uploads(): \App\Services\UploadStorage
    {
        return new \App\Services\UploadStorage(
            $this->basePath . '/storage/uploads/materials',
            (array) $this->config->get('uploads', []),
            (int) $this->config->get('app.upload_max_size', 10485760)
        );
    }

    public function session(): Session
    {
        return $this->session ??= new Session(
            $this->basePath . '/storage/sessions',
            (bool) $this->config->get('app.session_secure', true)
        );
    }

    public function csrf(): Csrf
    {
        return $this->csrf ??= new Csrf($this->session());
    }

    public function auth(): Auth
    {
        return $this->auth ??= new Auth(fn (): \PDO => $this->database()->pdo(), $this->session());
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
            // Everything (fonts, CSS, scripts) is served from this origin. Inline styles are used by the SVG animations;
            // there are no inline scripts, so scripts stay locked to 'self'.
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'",
        ];
        // HSTS only when the site is configured for HTTPS (a plain-HTTP site must never send it).
        if (str_starts_with((string) $this->config->get('app.url', ''), 'https://')) {
            $defaults['Strict-Transport-Security'] = 'max-age=15552000';
        }
        foreach ($defaults as $name => $value) {
            if (!$response->hasHeader($name)) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }
}
