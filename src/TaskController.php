<?php
declare(strict_types=1);

namespace App;

use PDO;

final class TaskController
{
    public function index(Request $request): void
    {
        $page    = max(1, (int)($request->query['page'] ?? 1));
        $perPage = min(100, max(1, (int)($request->query['per_page'] ?? 20)));

        $where = [];
        $args  = [];

        if (isset($request->query['done'])) {
            $where[] = 'done = :done';
            $args[':done'] = filter_var($request->query['done'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        if (!empty($request->query['q']) && is_string($request->query['q'])) {
            $where[] = '(title LIKE :q OR description LIKE :q)';
            $args[':q'] = '%' . $request->query['q'] . '%';
        }
        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $pdo = Database::connection();

        $count = $pdo->prepare("SELECT COUNT(*) FROM tasks $sqlWhere");
        $count->execute($args);
        $total = (int)$count->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT * FROM tasks $sqlWhere ORDER BY id DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($args as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        Response::json([
            'data' => array_map([$this, 'format'], $stmt->fetchAll()),
            'meta' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int)ceil($total / $perPage),
            ],
        ]);
    }

    public function show(Request $request, array $params): void
    {
        Response::json(['data' => $this->format($this->find($params['id']))]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request->json(), requireTitle: true);

        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO tasks (title, description, done) VALUES (:title, :description, :done)'
        );
        $stmt->execute([
            ':title'       => $data['title'],
            ':description' => $data['description'] ?? '',
            ':done'        => (int)($data['done'] ?? false),
        ]);

        $id = (int)$pdo->lastInsertId();
        header('Location: /tasks/' . $id);
        Response::json(['data' => $this->format($this->find((string)$id))], 201);
    }

    /** Используется и для PUT, и для PATCH: обновляются только переданные поля. */
    public function update(Request $request, array $params): void
    {
        $task = $this->find($params['id']);
        $data = $this->validate($request->json(), requireTitle: false);

        if (!$data) {
            throw new HttpException(422, 'Не передано ни одного поля для обновления');
        }

        $merged = array_merge($task, $data);
        $stmt = Database::connection()->prepare(
            "UPDATE tasks SET title = :title, description = :description, done = :done,
             updated_at = datetime('now') WHERE id = :id"
        );
        $stmt->execute([
            ':title'       => $merged['title'],
            ':description' => $merged['description'],
            ':done'        => (int)$merged['done'],
            ':id'          => $task['id'],
        ]);

        Response::json(['data' => $this->format($this->find($params['id']))]);
    }

    public function destroy(Request $request, array $params): void
    {
        $task = $this->find($params['id']);
        $stmt = Database::connection()->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute([':id' => $task['id']]);

        Response::json(null, 204);
    }

    // ---------------------------------------------------------------

    private function find(string $id): array
    {
        if (!ctype_digit($id)) {
            throw new HttpException(404, 'Задача не найдена');
        }
        $stmt = Database::connection()->prepare('SELECT * FROM tasks WHERE id = :id');
        $stmt->execute([':id' => (int)$id]);
        $task = $stmt->fetch();

        if (!$task) {
            throw new HttpException(404, 'Задача не найдена');
        }
        return $task;
    }

    private function validate(array $input, bool $requireTitle): array
    {
        $errors = [];
        $clean  = [];

        if (array_key_exists('title', $input)) {
            $title = is_string($input['title']) ? trim($input['title']) : '';
            if ($title === '' || mb_strlen($title) > 255) {
                $errors['title'] = 'Обязательное поле, максимум 255 символов';
            } else {
                $clean['title'] = $title;
            }
        } elseif ($requireTitle) {
            $errors['title'] = 'Обязательное поле';
        }

        if (array_key_exists('description', $input)) {
            if (!is_string($input['description']) || mb_strlen($input['description']) > 5000) {
                $errors['description'] = 'Должно быть строкой, максимум 5000 символов';
            } else {
                $clean['description'] = $input['description'];
            }
        }

        if (array_key_exists('done', $input)) {
            if (!is_bool($input['done'])) {
                $errors['done'] = 'Должно быть true или false';
            } else {
                $clean['done'] = $input['done'];
            }
        }

        if ($errors) {
            throw new HttpException(422, 'Ошибка валидации', $errors);
        }
        return $clean;
    }

    private function format(array $row): array
    {
        return [
            'id'          => (int)$row['id'],
            'title'       => $row['title'],
            'description' => $row['description'],
            'done'        => (bool)$row['done'],
            'created_at'  => $row['created_at'],
            'updated_at'  => $row['updated_at'],
        ];
    }
}
