<?php

declare(strict_types=1);

/**
 * Dependency-free unit tests: php tests/run.php
 * HTTP-level checks live in tests/smoke.sh.
 */

use App\Core\App;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Services\ContactForm;
use App\Services\ProfileForm;
use App\Services\UploadStorage;
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

echo "Sessions, CSRF and admin authentication\n";
$session = new Session($tmp . '/sessions', false, false);
$csrf = new Csrf($session);
$token = $csrf->token();
check('csrf token is 64 hex chars and stable per session', preg_match('/^[a-f0-9]{64}$/', $token) === 1 && $csrf->token() === $token);
check('csrf accepts the right token', $csrf->valid($token));
check('csrf rejects wrong, empty and prefix tokens', !$csrf->valid('x' . $token) && !$csrf->valid('') && !$csrf->valid(substr($token, 0, 10)));

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('CREATE TABLE admins (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL, last_login_at TEXT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL)');
$pdo->exec('CREATE TABLE login_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, client_hash TEXT NOT NULL, attempted_at TEXT NOT NULL)');
$t0 = 1800000000;

$threw = static function (callable $fn): bool {
    try {
        $fn();
    } catch (InvalidArgumentException | RuntimeException) {
        return true;
    }

    return false;
};
check('createAdmin rejects short password', $threw(static fn () => Auth::createAdmin($pdo, 'prof', 'short', $t0)));
check('createAdmin rejects bad username', $threw(static fn () => Auth::createAdmin($pdo, 'a b', 'correct horse battery', $t0)));
Auth::createAdmin($pdo, 'prof', 'correct horse battery', $t0);
check('createAdmin refuses a second administrator', $threw(static fn () => Auth::createAdmin($pdo, 'other', 'correct horse battery', $t0)));
$stored = (string) $pdo->query('SELECT password_hash FROM admins')->fetchColumn();
check('password is stored hashed, never plain', $stored !== 'correct horse battery' && password_verify('correct horse battery', $stored));

$session = new Session($tmp . '/sessions', false, false);
$auth = new Auth($pdo, $session, $t0 + 10);
check('not authenticated initially', !$auth->check() && $auth->id() === null);
check('wrong password fails', !$auth->attempt('prof', 'wrong password!!', '1.2.3.4') && !$auth->check());
check('unknown user fails', !$auth->attempt('nobody', 'correct horse battery', '1.2.3.4') && !$auth->check());
check('failures are recorded without the raw address', (int) $pdo->query('SELECT COUNT(*) FROM login_attempts')->fetchColumn() === 2 && !str_contains((string) $pdo->query('SELECT group_concat(client_hash) FROM login_attempts')->fetchColumn(), '1.2.3.4'));
check('correct password logs in', $auth->attempt('prof', 'correct horse battery', '1.2.3.4') && $auth->check() && $auth->username() === 'prof');
check('success clears that client failures and sets last_login_at', (int) $pdo->query('SELECT COUNT(*) FROM login_attempts')->fetchColumn() === 0 && $pdo->query('SELECT last_login_at FROM admins')->fetchColumn() !== null);
$auth->logout();
check('logout clears the session', !$auth->check());

$auth = new Auth($pdo, new Session($tmp . '/sessions', false, false), $t0 + 100);
for ($i = 0; $i < Auth::MAX_FAILURES_PER_CLIENT; $i++) {
    check('not throttled before limit #' . $i, !$auth->throttled('9.9.9.9'));
    $auth->attempt('prof', 'bad password ' . $i, '9.9.9.9');
}
check('throttled after repeated failures', $auth->throttled('9.9.9.9'));
check('other clients are not throttled', !$auth->throttled('8.8.8.8'));
$later = new Auth($pdo, new Session($tmp . '/sessions', false, false), $t0 + 100 + Auth::WINDOW_SECONDS + 5);
check('throttle expires after the window', !$later->throttled('9.9.9.9'));

$pdo->exec('DELETE FROM login_attempts');
$global = new Auth($pdo, new Session($tmp . '/sessions', false, false), $t0 + 200);
for ($i = 0; $i < Auth::MAX_FAILURES_GLOBAL; $i++) {
    $global->attempt('prof', 'nope nope nope ' . $i, 'ip-' . $i);
}
check('global failure budget throttles everyone', $global->throttled('brand-new-ip'));
$pdo->exec('DELETE FROM login_attempts');

$ps = new Session($tmp . '/sessions', false, false);
$pa = new Auth($pdo, $ps, $t0 + 300);
check('changePassword needs a session', $pa->changePassword('correct horse battery', 'another long password') === 'auth');
$pa->attempt('prof', 'correct horse battery', '5.5.5.5');
check('changePassword rejects wrong current password', $pa->changePassword('not it not it!!', 'another long password') === 'current');
check('changePassword rejects short new password', $pa->changePassword('correct horse battery', 'short') === 'weak');
check('changePassword succeeds', $pa->changePassword('correct horse battery', 'another long password') === null);
$pb = new Auth($pdo, new Session($tmp . '/sessions', false, false), $t0 + 400);
check('old password no longer works, new one does', !$pb->attempt('prof', 'correct horse battery', '6.6.6.6') && $pb->attempt('prof', 'another long password', '6.6.6.6'));

$lazy = new Auth(static fn (): PDO => throw new RuntimeException('database must not be touched'), new Session($tmp . '/sessions', false, false));
check('check() works without touching the database', $lazy->check() === false);

echo "Uploads\n";
$types = require $basePath . '/config/uploads.php';
$up = new UploadStorage($tmp . '/uploads', $types, 1000);
$mk = static function (string $name, string $content, int $err = UPLOAD_ERR_OK) use ($tmp): array {
    $f = $tmp . '/up-' . bin2hex(random_bytes(3));
    file_put_contents($f, $content);

    return ['name' => $name, 'tmp_name' => $f, 'error' => $err, 'size' => strlen($content)];
};
$pdf = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n";
$r = $up->inspect($mk('Dars.PDF', $pdf));
check('accepts a real PDF and keeps a safe mime', $r['error'] === null && $r['meta']['ext'] === 'pdf' && $r['meta']['mime'] === 'application/pdf' && $r['meta']['original'] === 'Dars.PDF');
check('no file is "empty", not an error', $up->inspect(null)['empty'] === true && $up->inspect(['error' => UPLOAD_ERR_NO_FILE])['empty'] === true);
check('PHP code named .pdf is refused (content check)', $up->inspect($mk('x.pdf', "<?php echo 1;"))['error'] === 'file_type');
check('.php is refused by extension', $up->inspect($mk('shell.php', $pdf))['error'] === 'file_type');
check('double extension keeps only the real one', $up->inspect($mk('a.php.pdf', $pdf))['meta']['ext'] === 'pdf');
check('executable renamed .txt is refused', $up->inspect($mk('a.txt', "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xff\xff"))['error'] === 'file_type');
check('HTML renamed .png is refused', $up->inspect($mk('a.png', '<html><script>alert(1)</script></html>'))['error'] === 'file_type');
check('no extension is refused', $up->inspect($mk('README', 'hello text'))['error'] === 'file_type');
check('too big is refused', $up->inspect($mk('big.txt', str_repeat('a', 1001)))['error'] === 'file_too_big');
check('PHP ini size error maps to too big', $up->inspect(['name' => 'a.pdf', 'tmp_name' => '', 'error' => UPLOAD_ERR_INI_SIZE])['error'] === 'file_too_big');
check('partial upload is a failure', $up->inspect(['name' => 'a.pdf', 'tmp_name' => '', 'error' => UPLOAD_ERR_PARTIAL])['error'] === 'file_failed');
check('empty file is a failure', $up->inspect($mk('a.txt', ''))['error'] === 'file_failed');
check('cleanName strips paths, quotes and control chars', UploadStorage::cleanName("..\\..\\a/b/\"x\"\n.pdf") === 'x.pdf');
check('cleanName keeps unicode names', UploadStorage::cleanName('Ma‘ruza №1.pdf') === 'Ma‘ruza №1.pdf');
check('path() refuses traversal and malformed names', $up->path('../../etc/passwd') === null && $up->path('a.pdf') === null && $up->path(str_repeat('a', 32) . '.php.pdf') === null);
check('store refuses a file that was not uploaded via HTTP', (static function () use ($up, $mk): bool {
    try {
        $up->store($mk('a.txt', 'x'), 'txt');
    } catch (RuntimeException) {
        return true;
    }

    return false;
})());
file_put_contents($tmp . '/dl.pdf', 'data');
$dl = Response::file($tmp . '/dl.pdf', "Ma‘ruza \"1\".pdf", 'application/pdf');
$cd = $dl->headers()['Content-Disposition'];
check('download is always an attachment with nosniff and exact length', str_starts_with($cd, 'attachment;') && $dl->headers()['X-Content-Type-Options'] === 'nosniff' && $dl->headers()['Content-Length'] === '4');
check('download header cannot be injected through the file name', !str_contains($cd, "\n") && substr_count($cd, '"') === 2 && str_contains($cd, "filename*=UTF-8''Ma%E2%80%98ruza%20%221%22.pdf"));
$rq = Request::create('POST', '/x', [], [], '', ['file' => ['name' => 'a', 'error' => 0], 'multi' => ['name' => ['a'], 'error' => [0]]]);
check('Request::file returns single entries only', $rq->file('file') !== null && $rq->file('multi') === null && $rq->file('none') === null);
check('format_bytes', format_bytes(500) === '500 B' && format_bytes(2048) === '2 KB' && format_bytes(1572864) === '1.5 MB');

echo "Contact and profile forms\n";
$cf = static fn (array $post): array => ContactForm::validate(Request::create('POST', '/uz/contact', [], $post));
$good = ['name' => 'Ali Valiyev', 'email' => 'ali@example.uz', 'subject' => 'Savol', 'message' => 'Assalomu alaykum, savolim bor.'];
check('valid message passes', $cf($good)['errors'] === []);
check('subject is optional', $cf(['subject' => ''] + $good)['errors'] === []);
check('name required', isset($cf(['name' => ' '] + $good)['errors']['name']));
check('email required and validated', isset($cf(['email' => ''] + $good)['errors']['email']) && ($cf(['email' => 'not-an-email'] + $good)['errors']['email'] ?? '') === 'email');
check('email header injection refused', isset($cf(['email' => "a@b.uz\r\nBcc: x@y.z"] + $good)['errors']['email']));
check('message too short / too long', ($cf(['message' => 'qisqa'] + $good)['errors']['message'] ?? '') === 'message_short' && ($cf(['message' => str_repeat('a', 5001)] + $good)['errors']['message'] ?? '') === 'too_long');
check('control characters refused in single-line fields', ($cf(["name" => "A\x00li"] + $good)['errors']['name'] ?? '') === 'invalid' && ($cf(['subject' => "a\nb"] + $good)['errors']['subject'] ?? '') === 'invalid');
check('newlines allowed in the message', $cf(['message' => "Birinchi qator\nIkkinchi qator"] + $good)['errors'] === []);
check('array input is ignored (treated as empty)', isset($cf(['name' => ['x']] + $good)['errors']['name']));
check('values are trimmed and kept for re-display', $cf(['name' => '  Ali  '] + $good)['values']['name'] === 'Ali');

$pf = static fn (array $post): array => ProfileForm::validate(Request::create('POST', '/admin/profile', [], $post), ['uz']);
$pgood = ['uz__full_name' => 'Samatov S.B.', 'email' => '', 'phone' => ''];
check('profile: only the name is required', $pf($pgood)['errors'] === [] && $pf($pgood)['base'] === ['email' => null, 'phone' => null]);
check('profile: missing name refused', ($pf(['uz__full_name' => ''])['errors']['uz__full_name'] ?? '') === 'required');
check('profile: email and phone validated', ($pf(['email' => 'x'] + $pgood)['errors']['email'] ?? '') === 'email' && ($pf(['phone' => 'abc'] + $pgood)['errors']['phone'] ?? '') === 'phone' && $pf(['phone' => '+998 (90) 123-45-67', 'email' => 'a@b.uz'] + $pgood)['errors'] === []);
check('profile: long text limit', ($pf(['uz__biography' => str_repeat('a', 20001)] + $pgood)['errors']['uz__biography'] ?? '') === 'too_long');

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
