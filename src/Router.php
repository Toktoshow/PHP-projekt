<?php
declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<int, array{string, string, callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        // /tasks/{id} -> ~^/tasks/(?P<id>[^/]+)$~
        $regex = preg_replace('~\{(\w+)\}~', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [strtoupper($method), '~^' . $regex . '$~', $handler];
    }

    public function get(string $p, callable $h): void    { $this->add('GET', $p, $h); }
    public function post(string $p, callable $h): void   { $this->add('POST', $p, $h); }
    public function put(string $p, callable $h): void    { $this->add('PUT', $p, $h); }
    public function patch(string $p, callable $h): void  { $this->add('PATCH', $p, $h); }
    public function delete(string $p, callable $h): void { $this->add('DELETE', $p, $h); }

    public function dispatch(Request $request): void
    {
        $allowed = [];

        foreach ($this->routes as [$method, $regex, $handler]) {
            if (!preg_match($regex, $request->path, $m)) {
                continue;
            }
            if ($method !== $request->method) {
                $allowed[] = $method;
                continue;
            }

            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler($request, $params);
            return;
        }

        if ($allowed) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            throw new HttpException(405, 'Метод не поддерживается');
        }

        throw new HttpException(404, 'Маршрут не найден');
    }
}
