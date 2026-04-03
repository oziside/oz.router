[← Previous Page](middleware.md) · [Back to README](../README.md)

# Настройки

## Где хранятся настройки

Модуль сохраняет настройки в опциях Bitrix и отдаёт их через `Oz\Router\Module\Config`.

Доступные getter-методы:

```php
use Oz\Router\Module\Config;

$config = new Config();

$routesFile = $config->getConfigRoutesFilePath();
$diFile = $config->getConfigDIFilePath();
$openApiSources = $config->getOpenApiSourses();
$openApiOutput = $config->getOpenApiSchemaOutput();
```

## Поля в админке

| Поле | Что хранится | Валидация |
|------|--------------|-----------|
| Файл конфигурации маршрутов | путь к читаемому `.php`-файлу | путь должен указывать на существующий PHP-файл |
| Файл конфигурации DI | путь к читаемому `.php`-файлу | путь должен указывать на существующий PHP-файл |
| Пути к OpenAPI-источникам | массив файлов и директорий | каждый путь должен существовать |
| Путь сохранения OpenAPI-схемы | путь с расширением `.json`, `.yaml` или `.yml` | проверяется только расширение |

## Runtime-поведение

Не каждая сохранённая настройка одинаково используется в рантайме.

| Настройка | Где используется | Комментарий |
|-----------|------------------|-------------|
| Файл конфигурации маршрутов | `services/api/index.php` и `oz:router.provider` | основная runtime-настройка |
| Файл конфигурации DI | доступен только через `Config` | встроенные точки входа не загружают его автоматически |
| Пути к OpenAPI-источникам | генератор OpenAPI в админке | сканируются `OpenApiGenerator` |
| Путь сохранения OpenAPI-схемы | генератор OpenAPI и вкладка Swagger | генератор умеет писать JSON и YAML |

## Важный нюанс про DI

Сохранённый путь к DI-конфигу сейчас не используется встроенными runtime-точками входа.

Что происходит фактически:

- `services/api/index.php` передаёт в `oz:router.provider` только `ROUTES_FILE_PATH`
- `oz:router.provider` пытается угадать DI-конфиг по правилу `dirname(dirname(ROUTES_FILE_PATH)) . '/di.php'`

Если нужен другой DI-файл, есть два практичных варианта:

- собирать `Router` вручную и передавать определения в `new Router($definitions)`
- или держать структуру файлов совместимой с правилом автопоиска компонента

## Генерация OpenAPI

Экран OpenAPI в админке использует `Oz\Router\Module\Service\OpenApiGenerator`.

Порядок работы такой:

1. каждый путь к источнику резолвится относительно `DOCUMENT_ROOT`
2. проверяется наличие `OpenApi\Generator` в Composer autoload
3. сканируются указанные файлы и директории
4. схема записывается в целевой путь

При необходимости генератор пытается создать отсутствующие директории назначения.

## Ограничение Swagger UI

Встроенный компонент `oz:swagger.ui` читает схему как JSON.

Из этого следует:

- `.json` - безопасный формат для встроенной вкладки `Swagger`
- `.yaml` и `.yml` можно сгенерировать, но встроенный viewer их не парсит

## Пример настроек

Для стандартного сценария со встроенным сервисом и компонентом удобно использовать:

```text
Файл маршрутов:      /local/php_interface/api/routes.php
Файл DI:             /local/php_interface/di.php
OpenAPI источники:   /local/php_interface/api
OpenAPI output:      /local/php_interface/openapi/openapi.json
```

Практический layout из `oz.router.sample`:

```text
Файл маршрутов:      /local/modules/oz.router.sample/config/routes/api.php
Файл DI:             /local/modules/oz.router.sample/config/di.php
OpenAPI источники:   пути, охватывающие /local/modules/oz.router.sample/lib/Product/Presentation
```

По коду sample именно в `Product/Presentation` лежат:

- OpenAPI-атрибуты контроллера
- request DTO-схемы
- response DTO-схемы

## OpenAPI и синхронизация с runtime

Практика sample показывает ещё одно важное правило: OpenAPI-пути и реальные router-path должны поддерживаться синхронно вручную.

Причина:

- маршруты строятся из `group('/api/v1')` + `group('/product')`
- OpenAPI-описание задаётся отдельно атрибутами `#[OA\Get]`, `#[OA\Post]` и т.д.

Роутер не проверяет, совпадают ли эти два источника. Если префиксы изменились в маршрутах, аннотации тоже нужно обновлять вручную.

## See Also

- [Установка и запуск](getting-started.md) - как стартуют встроенные точки входа
- [Маршрутизация](routing.md) - загрузка маршрутов из файла и сборка request-контейнера
- [Валидация](validation.md) - поведение валидации при dispatch
