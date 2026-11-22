<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Slug;
use App\Core\Time;
use App\Services\ContactForm;

/**
 * Public pages. Phase 1 renders the layout and empty states; content comes from services in Phase 3-4.
 * Method names match the keys in config/navigation.php.
 */
final class PageController extends Controller
{
    public function home(): Response
    {
        return $this->page('home', 'public/home', footerCta: true, data: [
            'latestNews' => array_slice($this->items('news'), 0, 3),
            'latestMaterials' => array_slice($this->items('materials'), 0, 4),
        ]);
    }

    public function about(): Response
    {
        return $this->page('about', 'public/about');
    }

    public function topics(): Response
    {
        return $this->page('topics', 'public/topics', data: ['items' => $this->items('topics')]);
    }

    public function news(): Response
    {
        return $this->page('news', 'public/news', data: ['items' => $this->items('news')]);
    }

    public function assignments(): Response
    {
        return $this->page('assignments', 'public/assignments', data: ['items' => $this->items('assignments')]);
    }

    public function practical(): Response
    {
        return $this->page('practical', 'public/lessons', data: ['items' => $this->items('practical')]);
    }

    public function laboratory(): Response
    {
        return $this->page('laboratory', 'public/lessons', data: ['items' => $this->items('laboratory')]);
    }

    public function materials(): Response
    {
        return $this->page('materials', 'public/materials', data: ['groups' => $this->materialGroups()]);
    }

    public function newsItem(Request $request): Response
    {
        $slug = (string) $request->param('slug');
        if (!Slug::valid($slug)) {
            throw new HttpException(404);
        }
        $item = null;
        if ($this->app->translator()->available($this->app->locale())) {
            try {
                $item = $this->app->content('news')->publishedBySlug($slug, $this->app->locale(), Time::nowUtc());
            } catch (\Throwable $e) {
                $this->app->logger()->error('Public content query failed', ['type' => 'news', 'message' => $e->getMessage()]);
                throw new HttpException(404);
            }
            if ($item === null) {
                throw new HttpException(404);
            }
        }

        return $this->page('news', 'public/news_item', data: ['item' => $item], pagePath: 'news/' . $slug, title: (string) ($item['title'] ?? ''), description: (string) ($item['summary'] ?? ''));
    }

    /** Download of a published material. Only the numeric id is public; the stored file name never leaves the server. */
    public function download(Request $request): Response
    {
        $raw = (string) $request->param('id');
        if (preg_match('/^\d{1,10}$/', $raw) !== 1) {
            throw new HttpException(404);
        }
        try {
            $row = $this->app->content('materials')->publishedRow((int) $raw);
        } catch (\Throwable $e) {
            $this->app->logger()->error('Download lookup failed', ['message' => $e->getMessage()]);
            throw new HttpException(404);
        }
        $path = $row === null ? null : $this->app->uploads()->path((string) $row['stored_name']);
        if ($row === null || $path === null) {
            throw new HttpException(404);
        }

        return Response::file($path, (string) $row['original_filename'], (string) $row['mime_type']);
    }

    public function contact(): Response
    {
        return $this->contactPage();
    }

    /**
     * Stores a contact message. Order: spam traps, validation, rate limit, save. Failures never reveal which
     * rule fired to a bot; humans get a clear message and their text back.
     */
    public function contactSubmit(Request $request): Response
    {
        if (!contact_enabled() || !$this->app->translator()->available($this->app->locale())) {
            throw new HttpException(404);
        }
        $session = $this->app->session();
        $logger = $this->app->logger();

        // Honeypot: a field no human sees. Pretend it worked so the bot learns nothing.
        if (trim($request->input('website')) !== '') {
            $logger->warning('Contact rejected', ['reason' => 'honeypot']);
            $session->set('contact_sent', true);

            return Response::redirect(url('contact'), 303);
        }
        // Time trap: the form must have been on screen for a moment.
        $shown = (int) $session->get('contact_t', 0);
        if ($shown === 0 || time() - $shown < self::MIN_FILL_SECONDS) {
            $logger->warning('Contact rejected', ['reason' => 'too_fast']);

            return $this->contactPage($this->contactValues($request), [], t('contact.too_fast'), 429);
        }

        $result = ContactForm::validate($request);
        if ($result['errors'] !== []) {
            return $this->contactPage($result['values'], $result['errors'], null, 422);
        }

        $client = hash('sha256', $request->ip);
        try {
            $feedback = $this->app->feedback();
            $now = time();
            if ($feedback->countSince(Time::nowUtc($now - 3600), $client) >= self::MAX_PER_CLIENT_HOUR
                || $feedback->countSince(Time::nowUtc($now - 86400)) >= self::MAX_PER_DAY) {
                $logger->warning('Contact rejected', ['reason' => 'rate_limit']);

                return $this->contactPage($result['values'], [], t('contact.rate_limited'), 429)->withHeader('Retry-After', '3600');
            }
            $v = $result['values'];
            $feedback->create($v['name'], $v['email'], $v['subject'] === '' ? null : $v['subject'], $v['message'], $client, Time::nowUtc($now));
        } catch (\Throwable $e) {
            $logger->error('Contact message could not be saved', ['message' => $e->getMessage()]);

            return $this->contactPage($result['values'], [], t('contact.failed'), 503);
        }

        $session->set('contact_sent', true);

        return Response::redirect(url('contact'), 303);
    }

    private const MIN_FILL_SECONDS = 2;
    private const MAX_PER_CLIENT_HOUR = 3;
    private const MAX_PER_DAY = 100;

    /** @return array{name:string,email:string,subject:string,message:string} */
    private function contactValues(Request $request): array
    {
        return ['name' => $request->input('name'), 'email' => $request->input('email'), 'subject' => $request->input('subject'), 'message' => $request->input('message')];
    }

    /** @param array<string,string> $values @param array<string,string> $errors */
    private function contactPage(array $values = [], array $errors = [], ?string $formError = null, int $status = 200): Response
    {
        $sent = false;
        if (contact_enabled() && $this->app->translator()->available($this->app->locale())) {
            $session = $this->app->session();
            $session->set('contact_t', time());
            $sent = (bool) $session->pull('contact_sent', false);
        }

        $response = $this->page('contact', 'public/contact', data: ['values' => $values, 'errors' => $errors, 'formError' => $formError, 'sent' => $sent]);

        // The page carries a per-session token: never cache it.
        return new Response($response->body(), $status, $response->headers() + ['Cache-Control' => 'no-store']);
    }

    /** @param array<string,mixed> $data */
    private function page(string $key, string $template, bool $footerCta = false, array $data = [], ?string $pagePath = null, ?string $title = null, ?string $description = null): Response
    {
        $locale = $this->app->locale();
        $pagePath ??= $this->pathFor($key);

        // No translation for this locale yet: show the "unavailable" state, never another language's content.
        if (!$this->app->translator()->available($locale)) {
            $body = $this->app->view()->render('public/unavailable', [
                'locale' => $locale,
                'pagePath' => $pagePath,
            ], 'layouts/plain');

            return Response::html($body)->withHeader('X-Robots-Tag', 'noindex');
        }

        $siteName = t('site.name');
        $title = $title !== null && $title !== ''
            ? $title . ' · ' . $siteName
            : ($key === 'home' ? t('pages.home.title') : t('pages.' . $key . '.title') . ' · ' . $siteName);
        $description = $description !== null && $description !== '' ? $description : t('pages.' . $key . '.description');
        $baseUrl = (string) $this->app->config()->get('app.url');

        $body = $this->app->view()->render($template, [
            'pageKey' => $key,
            'pagePath' => $pagePath,
        ] + $data + $this->layoutData($title, $description, $baseUrl . url($pagePath), $pagePath, $footerCta), 'layouts/public');

        return Response::html($body);
    }

    /** @return array<string,mixed> */
    private function layoutData(string $title, string $description, string $canonical, string $pagePath, bool $footerCta): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'pagePath' => $pagePath,
            'footerCta' => $footerCta,
        ];
    }

    /**
     * Published items of a content type in the current locale. A database failure is logged and shown as an
     * empty list so visitors never see technical details.
     *
     * @return list<array<string,mixed>>
     */
    private function items(string $type): array
    {
        if (!$this->app->translator()->available($this->app->locale())) {
            return [];
        }
        try {
            return $this->app->content($type)->published($this->app->locale(), Time::nowUtc());
        } catch (\Throwable $e) {
            $this->app->logger()->error('Public content query failed', ['type' => $type, 'message' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Published materials grouped by published category (category order first, uncategorised last).
     *
     * @return list<array{name:string,items:list<array<string,mixed>>}>
     */
    private function materialGroups(): array
    {
        $items = $this->items('materials');
        if ($items === []) {
            return [];
        }
        $names = [];
        try {
            foreach ($this->app->content('categories')->published($this->app->locale(), Time::nowUtc()) as $category) {
                $names[(int) $category['id']] = (string) $category['name'];
            }
        } catch (\Throwable $e) {
            $this->app->logger()->error('Public content query failed', ['type' => 'categories', 'message' => $e->getMessage()]);
        }

        $groups = [];
        $other = [];
        foreach ($items as $item) {
            $category = $item['category_id'] === null ? null : (int) $item['category_id'];
            if ($category !== null && isset($names[$category])) {
                $groups[$category][] = $item;
            } else {
                $other[] = $item;
            }
        }
        $out = [];
        foreach ($names as $id => $name) {
            if (isset($groups[$id])) {
                $out[] = ['name' => $name, 'items' => $groups[$id]];
            }
        }
        if ($other !== []) {
            $out[] = ['name' => $out === [] ? '' : t('pages.materials.other'), 'items' => $other];
        }

        return $out;
    }

    private function pathFor(string $key): string
    {
        foreach ((array) $this->app->config()->get('navigation', []) as $item) {
            if ($item['key'] === $key) {
                return (string) $item['path'];
            }
        }

        return '';
    }
}
