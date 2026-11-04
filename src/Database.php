<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $path = Config::get('db_path');
            $isNew = !is_file($path);

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0775, true);
            }

            self::$pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // При первом запуске создаём таблицы автоматически.
            if ($isNew) {
                self::$pdo->exec(file_get_contents(Config::get('root') . '/database/schema.sql'));
            }
        }

        return self::$pdo;
    }
}
