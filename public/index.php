<?php
declare(strict_types=1);

use App\Config;
use App\HttpException;
use App\Request;
use App\Response;
use App\Router;
use App\TaskController;

$root = dirname(__DIR__);
$composer = $root . '/vendor/autoload.php';
require is_file($composer) ? $composer : $root . '/src/autoload.php';

Config::load($root);

// CORS (при необходимости ограничьте домен вместо *)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $request = Request::fromGlobals();

    // Простая авторизация по ключу (если API_KEY задан)
    $apiKey = Config::get('api_key');
    if ($apiKey !== '' && !hash_equals($apiKey, (string)$request->header('X-API-Key'))) {
        throw new HttpException(401, 'Неверный или отсутствующий X-API-Key');
    }

    $router = new Router();
    $tasks  = new TaskController();

    $router->get('/', fn() => Response::json(['name' => 'PHP REST API', 'version' => '1.0']));
    $router->get('/tasks',          [$tasks, 'index']);
    $router->post('/tasks',         [$tasks, 'store']);
    $router->get('/tasks/{id}',     [$tasks, 'show']);
    $router->put('/tasks/{id}',     [$tasks, 'update']);
    $router->patch('/tasks/{id}',   [$tasks, 'update']);
    $router->delete('/tasks/{id}',  [$tasks, 'destroy']);

    $router->dispatch($request);
} catch (HttpException $e) {
    Response::error($e->status, $e->getMessage(), $e->errors);
} catch (Throwable $e) {
    error_log((string)$e);
    Response::error(500, 'Внутренняя ошибка сервера');
}
