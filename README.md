# Yii2 API Adminpanel

Переписанная на Yii2 версия Laravel API admin panel, разложенная по структуре Yii2 Advanced.

## Состав

- Yii2 Advanced-style layout: `frontend`, `backend`, `common`, `console`
- REST API как модуль `frontend/modules/api`
- REST API с JSON body
- JWT Bearer auth
- CRUD заметок текущего пользователя
- AdminLTE админ-панель
- CRUD администраторов, каталога и заметок
- Swagger UI
- Docker: php-fpm, nginx, MySQL, PostgreSQL, Redis, Memcached, phpMyAdmin

## Запуск

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec -T app composer install
docker compose exec -T app php yii migrate --interactive=0
docker compose exec -T app composer swagger
```

По умолчанию проект открывается на порту `8082`, чтобы не конфликтовать с Laravel-проектом на `8080`.

## URL

- Frontend: http://localhost:8082/
- Admin panel: http://localhost:8082/login
- Dashboard: http://localhost:8082/cp
- Users: http://localhost:8082/cp/admin
- Catalog: http://localhost:8082/cp/catalog
- Notes moderation: http://localhost:8082/cp/notes
- API root: http://localhost:8082/api/v1/
- Swagger: http://localhost:8082/api/documentation
- OpenAPI JSON: http://localhost:8082/docs

phpMyAdmin запускается отдельным профилем:

```bash
docker compose --profile tools up -d phpmyadmin
```

После запуска он доступен на http://localhost:8092.

## Структура

- `frontend/` - публичный фронт, web entrypoint и модуль REST API
- `frontend/modules/api/` - API controllers и OpenAPI generation
- `backend/` - админ-панель, web entrypoint и AdminLTE assets
- `common/` - общие ActiveRecord-модели, формы, параметры, DB config и mail layouts
- `console/` - консольные команды и миграции

## Доступ в админ-панель

- Логин: `admin`
- Пароль: `1234567`

## API

- `GET /api/v1/`
- `POST /api/v1/register`
- `POST /api/v1/login`
- `POST /api/v1/logout`
- `GET /api/v1/notes`
- `GET /api/v1/notes/{id}`
- `POST /api/v1/notes/store`
- `PUT /api/v1/notes/update/{id}`
- `DELETE /api/v1/notes/delete/{id}`
