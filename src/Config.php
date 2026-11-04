<?php
declare(strict_types=1);

namespace App;

final class Config
{
    private static array $values = [];

    public static function load(string $root): void
    {
        $env = [];
        $envFile = $root . '/.env';

        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $val] = explode('=', $line, 2);
                $env[trim($key)] = trim($val);
            }
        }

        $dbPath = $env['DB_PATH'] ?? (getenv('DB_PATH') ?: 'storage/database.sqlite');
        if ($dbPath[0] !== '/' && !preg_match('~^[A-Za-z]:[\\\\/]~', $dbPath)) {
            $dbPath = $root . '/' . $dbPath;
        }

        self::$values = [
            'root'    => $root,
            'db_path' => $dbPath,
            'api_key' => $env['API_KEY'] ?? (getenv('API_KEY') ?: ''),
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$values[$key] ?? $default;
    }
}
