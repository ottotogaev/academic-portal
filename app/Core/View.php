<?php

declare(strict_types=1);

namespace App\Core;

/** Renders PHP templates from app/Views. Template names come from code only and are strictly validated. */
final class View
{
    public function __construct(private string $dir)
    {
    }

    /** Render a template; with a layout the rendered page is passed to it as $content. */
    public function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = $this->capture($template, $data);
        if ($layout === null) {
            return $content;
        }

        return $this->capture($layout, array_merge($data, ['content' => $content]));
    }

    private function capture(string $template, array $data): string
    {
        if (preg_match('#^[a-z0-9_]+(/[a-z0-9_]+)*$#', $template) !== 1) {
            throw new \InvalidArgumentException('Invalid template name.');
        }
        $file = $this->dir . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        $include = static function (string $__file, array $__data): void {
            extract($__data, EXTR_SKIP);
            include $__file;
        };

        ob_start();
        try {
            $include($file, $data);

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
