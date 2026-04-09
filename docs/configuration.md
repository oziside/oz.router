[← Previous Page](validation.md) · [Back to README](../README.md)

# Конфигурация и OpenAPI

## `Oz\Router\Module\Config`

Настройки модуля хранятся в Bitrix options и доступны через `Oz\Router\Module\Config`.

Основные методы:

```php
use Oz\Router\Module\Config;

$config = new Config();

$routesFile = $config->getConfigRoutesFilePath();
$diFile = $config->getConfigDIFilePath();
$openApiSources = $config->getOpenApiSourses();
$openApiOutput = $config->getOpenApiSchemaOutput();
```

Методы записи:

- `setConfigRoutesFilePath(string $path)`
- `setConfigDIFilePath(string $path)`
- `setOpenApiSources(array $paths)`
- `setOpenApiSchemaOutput(string $path)`

## Что реально используется в runtime

| Настройка | Где используется | Комментарий |
|----------|------------------|-------------|
| Путь к routes file | `services/api/index.php` | основная runtime-настройка для service endpoint |
| Путь к DI file | хранится в options | встроенный provider его не использует напрямую |
| OpenAPI source paths | `Module\Service\OpenApiGenerator` | используются при ручной генерации схемы |
| OpenAPI output path | `Module\Service\OpenApiGenerator` и Swagger view | влияет на место сохранения и чтение схемы |

## Важный нюанс про DI

Сохранённый `configDIFilePath` не участвует во встроенном runtime bootstrap.

Фактическое поведение такое:

1. `services/api/index.php` берёт только путь к routes file
2. `oz:router.provider` вычисляет DI-файл как `dirname(dirname(routesFile)) . '/di.php'`
3. если этот файл существует, definitions загружаются автоматически

Если нужен другой layout, есть два практичных варианта:

- собирать `Router` вручную и передавать definitions в конструктор
- придерживаться layout `config/routes/*.php` + `config/di.php`

## OpenAPI generator

`Oz\Router\Module\Service\OpenApiGenerator`:

1. резолвит source paths относительно `DOCUMENT_ROOT`
2. проверяет наличие `OpenApi\Generator`
3. сканирует файлы и директории
4. сериализует схему в JSON или YAML
5. пишет результат в output path

Поддерживаются расширения:

- `.json`
- `.yaml`
- `.yml`

## Swagger UI caveat

Встроенный viewer ориентирован на JSON-схему. Поэтому для вкладки Swagger безопаснее указывать `.json`.

YAML можно генерировать, но встроенный UI не стоит считать надёжным потребителем `.yaml/.yml`.

## OpenAPI не управляет runtime

OpenAPI-атрибуты и реальные маршруты живут отдельно.

Роутер:

- не синхронизирует paths из `group()`
- не выставляет HTTP status по OpenAPI-описанию
- не проверяет, что аннотации соответствуют handler

Поэтому при изменении runtime routes нужно отдельно обновлять OpenAPI-атрибуты.

## Формат ошибок встроенных entrypoints

Для API-клиента важно помнить:

- JSON-ошибки возвращаются только при `Accept: application/json`
- иначе `ExceptionHandler` отдаёт HTML-тело с текстом ошибки

Это касается и service endpoint, и ручного запуска через `RouterRunner`.

## Практический набор настроек

```text
Файл маршрутов:    /local/modules/oz.router.sample/config/routes/api.php
Файл DI:           /local/modules/oz.router.sample/config/di.php
OpenAPI sources:   /local/modules/oz.router.sample/lib
OpenAPI output:    /local/modules/oz.router.sample/openapi/openapi.json
```

## See Also

- [Старт и точки входа](getting-started.md) - provider, service endpoint и bootstrap
- [Маршрутизация](routing.md) - `loadRoutesFromFile()` и request container
- [Guards](guards.md) - policy-модель маршрутов
