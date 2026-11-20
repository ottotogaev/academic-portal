<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Response;

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
        return $this->page('topics', 'public/list');
    }

    public function news(): Response
    {
        return $this->page('news', 'public/list');
    }

    public function assignments(): Response
    {
        return $this->page('assignments', 'public/list');
    }

    public function practical(): Response
    {
        return $this->page('practical', 'public/list');
    }

    public function laboratory(): Response
    {
        return $this->page('laboratory', 'public/list');
    }

    public function materials(): Response
    {
        return $this->page('materials', 'public/list');
    }

    public function contact(): Response
    {
        return $this->page('contact', 'public/contact');
    }

    private function page(string $key, string $template, bool $footerCta = false): Response
    {
        $locale = $this->app->locale();
        $pagePath = $this->pathFor($key);

        // No translation for this locale yet: show the "unavailable" state, never another language's content.
        if (!$this->app->translator()->available($locale)) {
            $body = $this->app->view()->render('public/unavailable', [
                'locale' => $locale,
                'pagePath' => $pagePath,
            ], 'layouts/plain');

            return Response::html($body)->withHeader('X-Robots-Tag', 'noindex');
        }

        $siteName = t('site.name');
        $title = $key === 'home'
            ? t('pages.home.title')
            : t('pages.' . $key . '.title') . ' · ' . $siteName;
        $baseUrl = (string) $this->app->config()->get('app.url');

        $body = $this->app->view()->render($template, [
            'pageKey' => $key,
            'pagePath' => $pagePath,
        ] + $this->layoutData($title, t('pages.' . $key . '.description'), $baseUrl . url($pagePath), $pagePath, $footerCta), 'layouts/public');

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
