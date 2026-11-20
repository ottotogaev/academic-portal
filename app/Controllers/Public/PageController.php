<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Slug;
use App\Core\Time;

/**
 * Public pages. Phase 1 renders the layout and empty states; content comes from services in Phase 3-4.
 * Method names match the keys in config/navigation.php.
 */
final class PageController extends Controller
{
    public function home(): Response
    {
        return $this->page('home', 'public/home', footerCta: true);
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
        return $this->page('materials', 'public/list');
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

    public function contact(): Response
    {
        return $this->page('contact', 'public/contact');
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
