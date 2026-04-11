[← Previous Page](validation.md) · [Back to README](../README.md)

# Конфигурация

## `Oz\Router\Module\Config`

Настройки модуля хранятся в Bitrix options и доступны через `Oz\Router\Module\Config`.

Основные методы:

```php
use Oz\Router\Module\Config;

$config = new Config();

$routesFile = $config->getConfigRoutesFilePath();
$diFile = $config->getConfigDIFilePath();
```

Методы записи:

- `setConfigRoutesFilePath(string $path)`
- `setConfigDIFilePath(string $path)`

## Что реально используется в runtime

| Настройка | Где используется | Комментарий |
|----------|------------------|-------------|
| Путь к routes file | `services/api/index.php` | основная runtime-настройка для service endpoint |
| Путь к DI file | хранится в options | встроенный provider его не использует напрямую |

## Важный нюанс про DI

Сохранённый `configDIFilePath` не участвует во встроенном runtime bootstrap.

Фактическое поведение такое:

1. `services/api/index.php` берёт только путь к routes file
2. `oz:router.provider` вычисляет DI-файл как `dirname(dirname(routesFile)) . '/di.php'`
3. если этот файл существует, definitions загружаются автоматически

Если нужен другой layout, есть два практичных варианта:

- собирать `Router` вручную и передавать definitions в конструктор
- придерживаться layout `config/routes/*.php` + `config/di.php`

## Формат ошибок встроенных entrypoints

Для API-клиента важно помнить:

- JSON-ошибки возвращаются только при `Accept: application/json`
- иначе `ExceptionHandler` отдаёт HTML-тело с текстом ошибки

Это касается и service endpoint, и ручного запуска через `RouterRunner`.

## Практический набор настроек

```text
Файл маршрутов:    /local/modules/oz.router.sample/config/routes/api.php
Файл DI:           /local/modules/oz.router.sample/config/di.php
```

## See Also

- [Старт и точки входа](getting-started.md) - provider, service endpoint и bootstrap
- [Маршрутизация](routing.md) - `loadRoutesFromFile()` и request container
- [Guards](guards.md) - policy-модель маршрутов
