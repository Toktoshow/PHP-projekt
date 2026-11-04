<?php
declare(strict_types=1);

namespace App;

final class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);

        if ($status === 204) {
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public static function error(int $status, string $message, array $errors = []): void
    {
        $payload = ['error' => $message];
        if ($errors) {
            $payload['details'] = $errors;
        }
        self::json($payload, $status);
    }
}
