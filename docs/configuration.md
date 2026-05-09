# Конфигурация

## Обзор

Конфигурация модуля живёт в Bitrix options и доступна через `Oz\Router\Module\Config`. Главная практическая задача этой страницы — объяснить, какие настройки реально участвуют во runtime, а какие только хранятся как metadata.

## `Oz\Router\Module\Config`

Базовое использование:

```php
use Oz\Router\Module\Config;

$config = new Config();

$routesFile = $config->getConfigRoutesFilePath();
$diFile = $config->getConfigDIFilePath();
```

Методы записи:

- `setConfigRoutesFilePath(string $path)`
- `setConfigDIFilePath(string $path)`

## Что реально используется во встроенном runtime

| Настройка | Где используется | Комментарий |
|----------|------------------|-------------|
| Путь к routes file | `services/api/index.php` | основная runtime-настройка для service endpoint |
| Путь к DI file | хранится в options | встроенный provider его не использует напрямую |

Это главное, что стоит понимать перед интеграцией через встроенные entrypoints.

## Почему путь к DI file не является source of truth

Сохранённый `configDIFilePath` не участвует во встроенном runtime bootstrap напрямую.

Фактическое поведение такое:

1. `services/api/index.php` берёт только путь к routes file;
2. `oz:router.provider` вычисляет DI-файл как `dirname(dirname(routesFile)) . '/di.php'`;
3. если этот файл существует, definitions загружаются автоматически.

Именно поэтому routes layout влияет на runtime сильнее, чем сохранённая настройка DI file.

## Рекомендуемый layout

Самый предсказуемый вариант:

```text
Файл маршрутов: /local/modules/oz.router.sample/config/routes/api.php
Файл DI:        /local/modules/oz.router.sample/config/di.php
```

Если придерживаться layout `config/routes/*.php` + `config/di.php`, встроенный provider ведёт себя ожидаемо.

## Когда нужен ручной bootstrap

Если ваш layout не укладывается в ожидаемое правило поиска `di.php`, есть два практичных варианта:

- собирать `Router` вручную и передавать definitions в конструктор;
- перестроить layout под ожидаемую структуру.

Если проекту нужен полный контроль над bootstrap, ручной путь обычно надёжнее.

## Формат ошибок встроенных entrypoints

Для API-клиента важно помнить:

- JSON-ошибки возвращаются только при `Accept: application/json`
- иначе `ExceptionHandler` отдаёт HTML-тело с текстом ошибки

Это касается:

- service endpoint;
- запуска через `oz:router.provider`;
- ручного запуска через `RouterRunner`.

## Практические рекомендации

Обычно конфигурацию безопасно выстраивать так:

1. сначала проверить ручной bootstrap;
2. затем вынести маршруты в routes file;
3. положить `di.php` рядом по ожидаемому layout;
4. только после этого переключаться на встроенные provider/service entrypoints.

Так проще локализовать проблемы: отдельно routing, отдельно DI, отдельно Bitrix-specific bootstrap.
