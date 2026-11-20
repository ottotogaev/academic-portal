<?php

declare(strict_types=1);

/**
 * Dependency-free unit tests: php tests/run.php
 * HTTP-level checks live in tests/smoke.sh.
 */

use App\Core\App;
use App\Core\Config;
use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\HttpException;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Translator;
use App\Core\View;
use App\Middleware\LocaleMiddleware;

$basePath = dirname(__DIR__);
error_reporting(E_ALL);
ini_set('display_errors', '1');

spl_autoload_register(static function (string $class) use ($basePath): void {
    if (strncmp($class, 'App\\', 4) === 0) {
        $file = $basePath . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
require $basePath . '/app/helpers.php';

$failures = 0;
$passed = 0;

function check(string $name, bool $ok, string $detail = ''): void
{
    global $failures, $passed;
    if ($ok) {
        $passed++;
        echo "  ok   {$name}\n";
    } else {
        $failures++;
        echo "  FAIL {$name}" . ($detail !== '' ? " ({$detail})" : '') . "\n";
    }
}

function throwsStatus(callable $fn, int $status): bool
{
    try {
        $fn();
    } catch (HttpException $e) {
        return $e->status() === $status;
    }

    return false;
}

$tmp = sys_get_temp_dir() . '/ap-test-' . bin2hex(random_bytes(4));
mkdir($tmp . '/config', 0777, true);
mkdir($tmp . '/lang', 0777, true);
mkdir($tmp . '/views/x', 0777, true);

echo "Env\n";
file_put_contents($tmp . '/.env', "# comment\nAPP_ENV=local\nQUOTED=\"a # b\"\nPLAIN=value # trailing\nEMPTY=\nFLAG=true\nbad key=1\n");
Env::load($tmp . '/.env');
check('reads plain value', Env::get('APP_ENV') === 'local');
check('quoted value keeps hash', Env::get('QUOTED') === 'a # b');
check('strips inline comment', Env::get('PLAIN') === 'value');
check('empty value', Env::get('EMPTY') === '');
check('bool true', Env::bool('FLAG') === true);
check('bool default on missing', Env::bool('MISSING_FLAG', true) === true);
check('default on missing', Env::get('MISSING', 'd') === 'd');
check('invalid keys ignored', Env::get('bad key') === null);

echo "Config\n";
file_put_contents($tmp . '/config/demo.php', "<?php return ['a' => ['b' => 5], 'list' => [1, 2]];");
$config = new Config($tmp . '/config');
check('dotted lookup', $config->get('demo.a.b') === 5);
check('default for missing key', $config->get('demo.a.zzz', 'x') === 'x');
check('missing file gives default', $config->get('nofile.a', 'y') === 'y');
check('rejects path-like file part', $config->get('../etc.x', 'z') === 'z');

echo "Request\n";
check('normalize collapses slashes', Request::normalize('//uz///about//') === '/uz/about');
check('normalize root', Request::normalize('') === '/' && Request::normalize('/') === '/');
check('decodes path', Request::create('GET', '/uz/%D0%B0')->path === '/uz/а');
check('rejects NUL byte', throwsStatus(static fn () => Request::create('GET', '/uz/%00'), 400));
check('rejects invalid UTF-8', throwsStatus(static fn () => Request::create('GET', '/uz/%FF'), 400));
check('uppercases method', Request::create('get', '/')->method === 'GET');

echo "Response\n";
check('redirect to local path', Response::redirect('/uz/')->headers()['Location'] === '/uz/');
foreach (['//evil.com', 'https://evil.com', "/ok\r\nSet-Cookie: x=1", '/a\\b', 'uz/'] as $bad) {
    $threw = false;
    try {
        Response::redirect($bad);
    } catch (InvalidArgumentException) {
        $threw = true;
    }
    check('redirect rejects ' . json_encode($bad), $threw);
}
check('hasHeader is case-insensitive', Response::text('x')->hasHeader('content-type'));

echo "Router\n";
$router = new Router();
$log = [];
$router->get('/', static fn (): Response => Response::text('root'));
$router->get('/{locale}/page', static fn (Request $r): Response => Response::text('page:' . $r->param('locale')));
check('matches root', $router->dispatch(Request::create('GET', '/'))->body() === 'root');
check('captures param', $router->dispatch(Request::create('GET', '/ru/page'))->body() === 'page:ru');
check('HEAD served by GET route', $router->dispatch(Request::create('HEAD', '/uz/page'))->body() === 'page:uz');
check('trailing slash tolerated', $router->dispatch(Request::create('GET', '/uz/page/'))->body() === 'page:uz');
check('unknown path is 404', throwsStatus(static fn () => $router->dispatch(Request::create('GET', '/nope')), 404));
check('param does not span segments', throwsStatus(static fn () => $router->dispatch(Request::create('GET', '/a/b/page')), 404));
check('wrong method is 405', throwsStatus(static fn () => $router->dispatch(Request::create('POST', '/uz/page')), 405));
try {
    $router->dispatch(Request::create('DELETE', '/uz/page'));
} catch (HttpException $e) {
    check('405 carries Allow header', ($e->headers()['Allow'] ?? '') === 'GET, HEAD');
}

final class TraceMiddleware implements \App\Middleware\Middleware
{
    public static array $log = [];

    public function handle(Request $request, callable $next): Response
    {
        self::$log[] = 'before';
        $response = $next($request);
        self::$log[] = 'after';

        return $response;
    }
}
$router2 = new Router();
$router2->get('/m', static function (): Response {
    TraceMiddleware::$log[] = 'handler';

    return Response::text('m');
}, [TraceMiddleware::class]);
$router2->dispatch(Request::create('GET', '/m'));
check('middleware wraps handler', TraceMiddleware::$log === ['before', 'handler', 'after']);
$router3 = new Router();
$router3->get('/bad', static fn () => 'not a response');
$threw = false;
try {
    $router3->dispatch(Request::create('GET', '/bad'));
} catch (UnexpectedValueException) {
    $threw = true;
}
check('handler must return Response', $threw);

echo "Translator\n";
file_put_contents($tmp . '/lang/uz.php', "<?php return ['a' => ['b' => 'Salom {name}'], 'n' => 5];");
$translator = new Translator($tmp . '/lang', ['uz', 'ru', 'en']);
check('uz available', $translator->available('uz'));
check('ru unavailable without file', !$translator->available('ru'));
check('unknown locale unavailable', !$translator->available('../x'));
check('nested key with replacement', $translator->get('uz', 'a.b', ['name' => 'Ali']) === 'Salom Ali');
check('missing key returns key', $translator->get('uz', 'a.zzz') === 'a.zzz');
check('non-string value returns key', $translator->get('uz', 'n') === 'n');
check('unavailable locale returns key (no fallback invention)', $translator->get('ru', 'a.b') === 'a.b');

echo "View\n";
file_put_contents($tmp . '/views/x/hello.php', '<p><?= e($name) ?></p>');
file_put_contents($tmp . '/views/x/layout.php', '[<?= $content ?>]');
$view = new View($tmp . '/views');
check('escapes output', $view->render('x/hello', ['name' => '<b>"x"</b>']) === '<p>&lt;b&gt;&quot;x&quot;&lt;/b&gt;</p>');
check('layout wraps content', $view->render('x/hello', ['name' => 'a'], 'x/layout') === '[<p>a</p>]');
foreach (['../x', 'x/../hello', '/etc/passwd', 'x/Hello', 'x/hello.php'] as $bad) {
    $threw = false;
    try {
        $view->render($bad);
    } catch (InvalidArgumentException) {
        $threw = true;
    }
    check('rejects template ' . $bad, $threw);
}
$level = ob_get_level();
file_put_contents($tmp . '/views/x/boom.php', '<?php throw new RuntimeException("boom");');
try {
    $view->render('x/boom');
} catch (RuntimeException) {
}
check('output buffer restored after exception', ob_get_level() === $level);

echo "App, locale and helpers\n";
$appConfig = new Config($basePath . '/config');
$app = new App($basePath, $appConfig, new Logger($tmp . '/logs'));
check('default locale is uz', $app->locale() === 'uz' && locale() === 'uz');
check('url() home', url() === '/uz/');
check('url() page', url('about') === '/uz/about' && url('/about/', 'ru') === '/ru/about');
$app->setLocale('ru');
check('setLocale changes url prefix', url('news') === '/ru/news');
$threw = false;
try {
    $app->setLocale('de');
} catch (InvalidArgumentException) {
    $threw = true;
}
check('setLocale rejects unsupported', $threw);
$app->setLocale('uz');
check('t() reads uz strings', t('nav.home') === 'Bosh sahifa');
check('e() escapes quotes', e('"a" & <b>') === '&quot;a&quot; &amp; &lt;b&gt;');

$mw = new LocaleMiddleware($app);
$next = static fn (Request $r): Response => Response::text('next');
check('locale middleware rejects unknown locale', throwsStatus(static fn () => $mw->handle(Request::create('GET', '/xx')->withParams(['locale' => 'xx']), $next), 404));
check('locale middleware rejects missing param', throwsStatus(static fn () => $mw->handle(Request::create('GET', '/'), $next), 404));
$mw->handle(Request::create('GET', '/en')->withParams(['locale' => 'en']), $next);
check('locale middleware activates locale', $app->locale() === 'en');
$app->setLocale('uz');

echo "Navigation and views\n";
$nav = $appConfig->get('navigation');
$uz = require $basePath . '/app/Lang/uz.php';
$missing = [];
foreach ($nav as $item) {
    if (!isset($uz['nav'][$item['key']])) {
        $missing[] = $item['key'];
    }
    if ($item['key'] !== 'home' && !isset($uz['pages'][$item['key']]['title'])) {
        $missing[] = 'pages.' . $item['key'];
    }
    if (!method_exists(\App\Controllers\Public\PageController::class, $item['key'])) {
        $missing[] = 'method ' . $item['key'];
    }
}
check('every navigation key has label, page strings and controller method', $missing === [], implode(',', $missing));

$html = $app->view()->render('components/header', ['pageKey' => 'news', 'pagePath' => 'news']);
check('header marks current page', str_contains($html, 'href="/uz/news" aria-current="page"'));
check('header links language switcher to same page', str_contains($html, 'href="/ru/news"') && str_contains($html, 'href="/en/news"'));
$footerOff = $app->view()->render('components/footer', ['footerCta' => false]);
$footerOn = $app->view()->render('components/footer', ['footerCta' => true]);
check('footer without CTA has no contact button', !str_contains($footerOff, 'href="/uz/contact"'));
check('footer with CTA links to contact', str_contains($footerOn, 'href="/uz/contact"'));

echo "Error handling\n";
$prod = ErrorHandler::render(new RuntimeException('SECRET-DB-PASSWORD at /home/claude/app/X.php'), false);
check('production 500 hides message and paths', !str_contains($prod, 'SECRET') && !str_contains($prod, '/home/claude') && str_contains($prod, 'Xatolik yuz berdi'));
$dbg = ErrorHandler::render(new RuntimeException('visible <b>'), true);
check('debug 500 shows escaped details', str_contains($dbg, 'visible &lt;b&gt;'));
$logger = new Logger($tmp . '/logs2');
$logger->error('hello', ['k' => 'v']);
$logFile = $tmp . '/logs2/app-' . date('Y-m-d') . '.log';
check('logger writes a private log line', is_file($logFile) && str_contains((string) file_get_contents($logFile), 'ERROR hello {"k":"v"}'));

// cleanup
$rm = static function (string $dir) use (&$rm): void {
    foreach (scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        is_dir("$dir/$f") ? $rm("$dir/$f") : unlink("$dir/$f");
    }
    rmdir($dir);
};
$rm($tmp);

echo "\n{$passed} passed, {$failures} failed\n";
exit($failures === 0 ? 0 : 1);
