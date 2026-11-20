<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\Middleware;

/**
 * Small router: "{name}" placeholders match one path segment. HEAD is served by GET routes.
 * Handlers are closures or [ControllerClass, 'method']; controllers and middleware are built by $factory.
 */
final class Router
{
    /** @var list<array{method:string,regex:string,handler:\Closure|array,middleware:list<string>}> */
    private array $routes = [];

    private \Closure $factory;

    public function __construct(?\Closure $factory = null)
    {
        $this->factory = $factory ?? static fn (string $class): object => new $class();
    }

    /** @param list<string> $middleware middleware class names */
    public function get(string $pattern, \Closure|array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    /** @param list<string> $middleware middleware class names */
    public function post(string $pattern, \Closure|array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    /** @param list<string> $middleware */
    public function add(string $method, string $pattern, \Closure|array $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'regex' => self::compile($pattern),
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        $allowed = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed[$route['method']] = true;
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return $this->run($route, $request->withParams($params));
        }

        if ($allowed !== []) {
            $methods = array_keys($allowed);
            if (isset($allowed['GET'])) {
                $methods[] = 'HEAD';
            }
            throw new HttpException(405, ['Allow' => implode(', ', $methods)]);
        }

        throw new HttpException(404);
    }

    /** @param array{method:string,regex:string,handler:\Closure|array,middleware:list<string>} $route */
    private function run(array $route, Request $request): Response
    {
        $core = function (Request $req) use ($route): Response {
            $handler = $route['handler'];
            if ($handler instanceof \Closure) {
                $result = $handler($req);
            } else {
                [$class, $method] = $handler;
                $result = ($this->factory)($class)->$method($req);
            }
            if (!$result instanceof Response) {
                throw new \UnexpectedValueException('Route handler must return a Response.');
            }

            return $result;
        };

        $pipeline = array_reduce(
            array_reverse($route['middleware']),
            function (\Closure $next, string $class): \Closure {
                return function (Request $req) use ($class, $next): Response {
                    /** @var Middleware $middleware */
                    $middleware = ($this->factory)($class);

                    return $middleware->handle($req, $next);
                };
            },
            $core
        );

        return $pipeline($request);
    }

    private static function compile(string $pattern): string
    {
        $parts = preg_split('/(\{[a-z_]+\})/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $regex = '';
        foreach ($parts as $part) {
            if (preg_match('/^\{([a-z_]+)\}$/', $part, $m) === 1) {
                $regex .= '(?P<' . $m[1] . '>[^/]+)';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }

        return '#^' . $regex . '$#D';
    }
}
