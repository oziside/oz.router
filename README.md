# oz.router

> [!WARNING]
> Этот проект находится в активной разработке. Между релизами могут происходить критические изменения.

> Роутер для Bitrix D7 с PHP-DI, группами маршрутов, guards, middleware, гидратацией аргументов и автоматической нормализацией ответа.

`oz.router` сопоставляет текущий `HttpRequest` с маршрутом, собирает request-scoped DI-контейнер, выполняет guards и middleware, вызывает обработчик и преобразует результат в `HttpResponse`.

## Quick Start

```php
<?php

use Bitrix\Main\Loader;
use Oz\Router\Router;
use Oz\Router\RouterRunner;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loader::includeModule('oz.router');

$router = new Router();

$router->get('/ping', static function (): array {
    return ['status' => 'ok'];
});

$response = (new RouterRunner($router))->run();
$response->send();
```

## Что есть в модуле

- регистрация маршрутов через `get/post/put/patch/delete/option/any/add`
- вложенные `group()` с префиксами и наследованием policy
- guards через `guard()` и `exceptGuard()`
- middleware через `middleware()` и `exceptMiddleware()`
- резолв handler arguments из path params, query, form-data и JSON body
- автосвязывание контроллеров, middleware и guards через PHP-DI
- валидация параметров и DTO через `Bitrix\Main\Validation\ValidationService`
- встроенные точки входа для Bitrix component и service

## Точки входа

- ручной bootstrap через `Router` + `RouterRunner`
- компонент `oz:router.provider`
- service endpoint `/bitrix/services/oz.api/`

## Практический пример

`oz.router.sample` показывает реальный layout для production-подобного API:

- routes в `config/routes/api.php`
- DI definitions в `config/di.php`
- versioned API через `group('/api/v1', ...)`
- controller handlers, DTO, guards и middleware

## Документация

| Раздел | Описание |
|--------|----------|
| [Старт и точки входа](docs/getting-started.md) | Установка, минимальный bootstrap, routes file, component и service endpoint |
| [Маршрутизация](docs/routing.md) | Методы роутера, группы, handlers, route params, DI и нормализация ответа |
| [Guards](docs/guards.md) | Контракт guard-классов, порядок выполнения и исключение guards |
| [Middleware](docs/middleware.md) | Контракт middleware, цепочка выполнения и post-processing ответа |
| [Валидация](docs/validation.md) | Гидратация DTO, валидация параметров и формат ошибок |
| [Конфигурация](docs/configuration.md) | `Module\Config`, настройки модуля и provider/service runtime |

## Ключевые caveats

- встроенный `oz:router.provider` автоматически пытается подключить `di.php` рядом с routes layout, но не использует сохранённый в настройках путь к DI-файлу
- `ExceptionHandler` возвращает JSON только при `Accept: application/json`, иначе отвечает простым HTML-телом

## Лицензия

См. [LICENSE](LICENSE).
