# Notes Service на Yii2 Advanced

Сервис заметок с публичной формой обратной связи, JWT REST API и административной панелью на AdminLTE 4. Проект рассчитан на PHP 8.4 и PostgreSQL; локальное окружение полностью поднимается через Docker Compose.

## Возможности

- регистрация, вход и отзыв JWT через REST API;
- CRUD только собственных заметок пользователя с обязательной категорией;
- фильтрация заметок по категории и пагинация;
- единый JSON-формат ошибок и валидация HTTP input models;
- публичная форма обратной связи без авторизации;
- AdminLTE 4: заметки, категории, сообщения, пользователи и администраторы;
- роли админки `admin` и `moderator`;
- PostgreSQL-миграции, кэширование, журналирование событий;
- Swagger UI и OpenAPI 3.0;
- unit- и HTTP integration-тесты на PHPUnit 12.

## Требования

- Docker с поддержкой Compose v2;
- свободный порт `8082` либо другое значение `APP_PORT` в `.env`;
- `jq` нужен только для автоматического извлечения значений в приведённых ниже `curl`-примерах.

Устанавливать PHP, Composer, PostgreSQL или расширения PHP на хост не требуется.

## Быстрый запуск

1. Создайте локальный файл настроек:

   ```bash
   cp .env.example .env
   ```

2. Замените в `.env` как минимум `DB_PASSWORD`, `JWT_SECRET` и `COOKIE_VALIDATION_KEY`. Для секретов можно дважды получить независимое случайное значение командой:

   ```bash
   openssl rand -hex 32
   ```

   Не используйте значения из `.env.example` в production. Файл `.env` исключён из Git.

3. Соберите и запустите окружение:

   ```bash
   docker compose up -d --build
   ```

4. Установите PHP-зависимости внутри контейнера приложения:

   ```bash
   docker compose exec -T app composer install --no-interaction --prefer-dist
   ```

5. Примените миграции PostgreSQL:

   ```bash
   docker compose exec -T app php yii migrate --interactive=0
   ```

Проверить состояние сервисов можно командой `docker compose ps`, остановить их — `docker compose down`. Команда `down` не удаляет данные PostgreSQL и Redis из каталогов проекта.

## Первый администратор

Миграции намеренно не создают администратора и не содержат паролей. Передайте пароль длиной не менее 8 символов через временную переменную окружения, чтобы он не оказался аргументом команды и истории shell:

```bash
printf 'Пароль первого администратора: '
IFS= read -r -s ADMIN_PASSWORD
printf '\n'
docker compose exec -T -e ADMIN_PASSWORD="$ADMIN_PASSWORD" app php yii admin/create admin
unset ADMIN_PASSWORD
```

Команда создаёт учётную запись с логином `admin`, именем `Administrator` и ролью `admin`. Она завершится ошибкой, если такой логин уже существует. Следующих администраторов и модераторов можно создавать из раздела админки.

## Адреса приложения

При стандартном `APP_PORT=8082`:

| Раздел | URL |
|---|---|
| Публичная часть и форма обратной связи | <http://localhost:8082/> |
| Вход в админку | <http://localhost:8082/login> |
| Главная страница админки | <http://localhost:8082/cp> |
| Пользователи | <http://localhost:8082/cp/users> |
| Администраторы | <http://localhost:8082/cp/admins> |
| Категории | <http://localhost:8082/cp/categories> |
| Заметки | <http://localhost:8082/cp/notes> |
| Сообщения | <http://localhost:8082/cp/messages> |
| Корень REST API | <http://localhost:8082/api/v1> |
| Swagger UI | <http://localhost:8082/api/documentation> |
| OpenAPI YAML | <http://localhost:8082/docs> |

Если порт изменён, замените `8082` на значение `APP_PORT`.

Модератор имеет доступ к заметкам, категориям и сообщениям. Разделы пользователей и администраторов доступны только роли `admin`.

## REST API

Все тела запросов и ответов используют `application/json`. Защищённые маршруты требуют заголовок `Authorization: Bearer <JWT>`. Пользователь может получать и изменять только свои заметки; чужой идентификатор возвращается как ненайденный ресурс.

| Метод | Маршрут | Авторизация | Назначение |
|---|---|---:|---|
| `GET` | `/api/v1` | нет | информация об API |
| `POST` | `/api/v1/register` | нет | регистрация пользователя |
| `POST` | `/api/v1/login` | нет | получение JWT |
| `POST` | `/api/v1/logout` | JWT | отзыв текущего JWT |
| `GET` | `/api/v1/categories` | JWT | список категорий |
| `GET` | `/api/v1/notes` | JWT | список своих заметок |
| `POST` | `/api/v1/notes` | JWT | создание заметки |
| `GET` | `/api/v1/notes/{id}` | JWT | просмотр своей заметки |
| `PUT`, `PATCH` | `/api/v1/notes/{id}` | JWT | обновление своей заметки |
| `DELETE` | `/api/v1/notes/{id}` | JWT | удаление своей заметки |

Для списка заметок доступны query-параметры `category_id`, `page` и `per_page`; `page` принимает значения от 1 до 1 000 000, `per_page` — от 1 до 100. Создание возвращает HTTP `201`, удаление и выход — `204`, ошибки валидации — `422`.

Публичные POST-запросы регистрации, входа в API и админку, а также формы обратной связи ограничены по IP на уровне Nginx. При превышении лимита возвращается HTTP `429`.

### Пример полного API-цикла

Примеры не содержат пароля: он читается без отображения и хранится только в текущем shell-процессе.

```bash
BASE_URL=http://localhost:8082
EMAIL="api-user-$(date +%s)@example.test"
printf 'Пароль API-пользователя: '
IFS= read -r -s API_PASSWORD
printf '\n'
```

Регистрация:

```bash
curl -sS -X POST "$BASE_URL/api/v1/register" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  --data "{\"name\":\"API User\",\"email\":\"$EMAIL\",\"password\":\"$API_PASSWORD\",\"password_confirmation\":\"$API_PASSWORD\"}"
```

Вход и сохранение JWT:

```bash
TOKEN=$(curl -sS -X POST "$BASE_URL/api/v1/login" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  --data "{\"email\":\"$EMAIL\",\"password\":\"$API_PASSWORD\"}" \
  | jq -r '.data.token')
```

Категории и идентификатор первой категории:

```bash
CATEGORIES=$(curl -sS "$BASE_URL/api/v1/categories" \
  -H 'Accept: application/json' \
  -H "Authorization: Bearer $TOKEN")
printf '%s\n' "$CATEGORIES" | jq
CATEGORY_ID=$(printf '%s' "$CATEGORIES" | jq -r '.data[0].id')
```

Создание заметки:

```bash
NOTE=$(curl -sS -X POST "$BASE_URL/api/v1/notes" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  --data "{\"category_id\":$CATEGORY_ID,\"title\":\"Первая заметка\",\"content\":\"Текст заметки\"}")
printf '%s\n' "$NOTE" | jq
NOTE_ID=$(printf '%s' "$NOTE" | jq -r '.data.id')
```

Список, просмотр и фильтрация:

```bash
curl -sS "$BASE_URL/api/v1/notes?category_id=$CATEGORY_ID&page=1&per_page=20" \
  -H 'Accept: application/json' \
  -H "Authorization: Bearer $TOKEN" | jq

curl -sS "$BASE_URL/api/v1/notes/$NOTE_ID" \
  -H 'Accept: application/json' \
  -H "Authorization: Bearer $TOKEN" | jq
```

Обновление:

```bash
curl -sS -X PATCH "$BASE_URL/api/v1/notes/$NOTE_ID" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  --data "{\"category_id\":$CATEGORY_ID,\"title\":\"Обновлённая заметка\",\"content\":\"Новый текст\"}" | jq
```

Удаление и выход:

```bash
curl -sS -o /dev/null -w 'delete: %{http_code}\n' -X DELETE \
  "$BASE_URL/api/v1/notes/$NOTE_ID" \
  -H "Authorization: Bearer $TOKEN"

curl -sS -o /dev/null -w 'logout: %{http_code}\n' -X POST \
  "$BASE_URL/api/v1/logout" \
  -H "Authorization: Bearer $TOKEN"

unset API_PASSWORD TOKEN
```

Ожидаемый статус обеих последних команд — `204`. После выхода JWT записывается в таблицу отозванных токенов и больше не принимается.

## Публичная форма обратной связи

Форма на `/` доступна без авторизации. Обязательные поля: тема, email и сообщение; телефон необязателен. Данные сохраняются в таблице `messages` со статусом `new`. В админке сообщение можно открыть, пометить как `read` и удалить.

## Архитектура

Бизнес-часть не зависит от HTTP-контроллеров и ActiveRecord:

```text
application/      чистые readonly DTO и сценарии AuthService, NoteService, CategoryService
domain/           сущности, исключения, контракты репозиториев и DataMapper
infrastructure/   ActiveRecord, DataMapper, Repository, JWT, password hashing, logger
frontend/         формы, HTTP input models, API handlers и тонкие REST-контроллеры
backend/          формы, use-case services и тонкие контроллеры AdminLTE 4
common/           Yii Identity/AR-адаптеры и общая конфигурация
console/          команды и PostgreSQL-миграции
tests/            unit- и API integration-тесты
```

REST-контроллеры делегируют запрос handlers. HTTP input models валидируют внешние `snake_case`-поля и преобразуют их в типизированные application DTO; application-слой не зависит от Yii. Сервисы работают с доменными сущностями через `UserRepositoryInterface`, `NoteRepositoryInterface` и `CategoryRepositoryInterface`. Реализации в `infrastructure/persistence` преобразуют канонические ActiveRecord-записи `UserRecord`, `NoteRecord` и `CategoryRecord` в доменные сущности через DataMapper. Backoffice-контроллеры делегируют изменение данных узким management-сервисам. Привязки сервисов, репозиториев, мапперов, JWT и кэша находятся в конфигурации DI-контейнера Yii2.

## Кэширование

В Docker по умолчанию `CACHE_DRIVER=memcached`, а время жизни запросов заметок задаётся `NOTES_CACHE_TTL`. Репозиторий кэширует отдельные заметки, списки и количество записей; после создания, изменения или удаления связанные теги пользователя и категории инвалидируются.

Если выбрать другое значение `CACHE_DRIVER`, Yii2 использует файловый кэш. Redis также поднимается отдельным сервисом и доступен приложению через `REDIS_HOST`/`REDIS_PORT` для дальнейших интеграций; текущий компонент Yii `cache` по умолчанию использует Memcached.

Memcached хранит данные только в памяти. Redis настроен с AOF и сохраняет данные в `docker/redis/data`.

## Логи

Прикладные события не содержат паролей и JWT и записываются отдельно:

- `frontend/runtime/logs/events.log` — API и форма обратной связи;
- `backend/runtime/logs/events.log` — события админки;
- `console/runtime/logs/events.log` — консольные команды.

Обычные ошибки Yii находятся в `runtime/logs` соответствующего приложения. Логи контейнеров доступны через:

```bash
docker compose logs -f app nginx postgres redis memcached
```

## Тесты и проверки

Сначала поднимите Docker-окружение, установите зависимости и примените миграции, как описано выше.

Только быстрые unit-тесты сервисов и архитектуры:

```bash
docker compose exec -T app vendor/bin/phpunit \
  --configuration phpunit.xml.dist --testsuite unit
```

Полный HTTP integration-тест регистрации, JWT, категорий, CRUD заметок, разграничения владельцев и logout:

```bash
docker compose exec -T app vendor/bin/phpunit \
  --configuration phpunit.xml.dist --testsuite api
```

Внутри Docker переменная `API_TEST_BASE_URL=http://nginx` уже задана. Вне Docker её нужно установить вручную; без неё локальный API-набор помечается как пропущенный, а в CI завершается ошибкой. Интеграционный тест создаёт пользователей с уникальными адресами и гарантированно удаляет созданные заметки, пользователей и записи отозванных JTI в `tearDown`. Он обращается к реально запущенному приложению, поэтому запускайте его только на тестовом или локальном экземпляре БД.

Все PHPUnit-наборы одной командой:

```bash
docker compose exec -T app composer test
```

Дополнительные проверки:

```bash
docker compose exec -T app composer cs
docker compose exec -T app composer static
```

Swagger UI использует единственный версионируемый источник спецификации — `frontend/modules/api/openapi/openapi.yaml`, доступный по `/docs`.

## Хранение данных Docker

Персистентные данные лежат в корне проекта, внутри требуемого каталога `docker`:

- `docker/postgres/data` — кластер PostgreSQL;
- `docker/redis/data` — AOF Redis.

Эти каталоги исключены из Git. Bind mount проекта позволяет контейнеру работать с текущим исходным кодом и каталогом `vendor`. Memcached персистентного каталога не имеет.

Перед удалением `docker/postgres/data` обязательно сделайте резервную копию. Обычный `docker compose down` эти каталоги не удаляет.

## Обновление существующей установки

Перед обновлением сделайте резервную копию PostgreSQL, обновите исходный код и зависимости, затем выполните:

```bash
docker compose exec -T app composer install --no-interaction --prefer-dist
docker compose exec -T app php yii migrate --interactive=0
```

Миграция `m260719_000001_upgrade_notes_service` совместима с ранней схемой проекта: `catalog` переименовывается в `categories`, `admin` — в `admins`, существующим заметкам назначается первая доступная категория, а также создаётся таблица `messages`. Email существующих пользователей приводятся к нижнему регистру и защищаются уникальным индексом `LOWER(email)`. Если старая база содержит два email, различающихся только регистром, миграция безопасно остановится: сначала объедините или переименуйте конфликтующие записи. При пустом каталоге добавляются стартовые категории. Учётные данные администратора миграция не создаёт — используйте безопасную команду `yii admin/create`, описанную выше.

Upgrade-миграция намеренно необратима: она нормализует данные и может принимать частично обновлённую схему, поэтому автоматический `migrate/down` был бы небезопасен. Для отката восстановите резервную копию PostgreSQL, созданную перед обновлением.

Для production дополнительно выключите debug-режим, задайте уникальные секреты и пароли, ограничьте сетевой доступ к PostgreSQL/Redis/Memcached, настройте TLS и политику резервного копирования. При `APP_ENV=prod` приложение откажется запускаться с пустым, коротким или известным примером `JWT_SECRET`/cookie key.
