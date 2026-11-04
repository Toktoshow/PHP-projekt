# PHP REST API

Небольшой REST API на чистом PHP 8.1+ (без фреймворков): роутер, PDO + SQLite,
валидация, пагинация, фильтры, опциональная авторизация по ключу.

## Запуск

Нужен PHP ≥ 8.1 с расширением `pdo_sqlite`. Composer не обязателен.

```bash
cp .env.example .env        # по желанию
php -S localhost:8000 -t public public/index.php
```

База (`storage/database.sqlite`) создаётся автоматически при первом запросе.

## Эндпоинты

| Метод  | URL           | Описание                                  |
|--------|---------------|-------------------------------------------|
| GET    | /tasks        | Список (`page`, `per_page`, `done`, `q`)  |
| POST   | /tasks        | Создать задачу                            |
| GET    | /tasks/{id}   | Получить задачу                           |
| PUT    | /tasks/{id}   | Обновить (передавайте нужные поля)        |
| PATCH  | /tasks/{id}   | То же, что PUT                            |
| DELETE | /tasks/{id}   | Удалить (204)                             |

## Примеры

```bash
# создать
curl -X POST localhost:8000/tasks \
  -H "Content-Type: application/json" \
  -d '{"title":"Купить молоко","description":"2 литра"}'

# список с фильтром
curl "localhost:8000/tasks?done=false&q=молоко&page=1&per_page=10"

# отметить выполненной
curl -X PATCH localhost:8000/tasks/1 \
  -H "Content-Type: application/json" -d '{"done":true}'

# удалить
curl -X DELETE localhost:8000/tasks/1
```

## Авторизация

Укажите `API_KEY=секрет` в `.env` — тогда каждый запрос должен содержать заголовок
`X-API-Key: секрет`.

## Структура

```
public/index.php        точка входа, маршруты
src/Router.php          простой роутер с параметрами {id}
src/TaskController.php  логика CRUD + валидация
src/Database.php        подключение PDO (SQLite)
src/Request.php         разбор входящего запроса
src/Response.php        JSON-ответы
database/schema.sql     схема БД
```

## Как добавить новый ресурс

1. Добавьте таблицу в `database/schema.sql`.
2. Создайте контроллер по образцу `TaskController`.
3. Зарегистрируйте маршруты в `public/index.php`.
