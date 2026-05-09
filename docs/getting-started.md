# Старт и точки входа

## Обзор

Эта страница показывает самый короткий путь от подключения модуля до первого рабочего маршрута. Если `routing.md` объясняет общую mental model, то здесь фокус только на запуске и практической интеграции.

## Что понадобится

Для базового запуска нужны:

- PHP `>= 8.1`
- Bitrix D7
- установленный модуль `oz.router`
- зависимость `php-di/php-di`, уже описанная в `composer.json` модуля

В большинстве Bitrix-проектов autoload модуля подключается через `local/php_interface/init.php`.

## Подключение модуля

Минимальное подключение выглядит так:

```php
use Bitrix\Main\Loader;

Loader::includeModule('oz.router');
```

Если модуль не подключён, ни ручной bootstrap, ни встроенные entrypoints не смогут создать `Router`.

## Первый рабочий маршрут

Самый короткий bootstrap:

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

Здесь происходят три вещи:

1. создаётся `Router`;
2. регистрируется маршрут;
3. `RouterRunner` получает текущий `HttpApplication`, вызывает `Router::dispatch()` и централизованно обрабатывает исключения через `ExceptionHandler`.

## Как проверить, что всё работает

После регистрации `GET /ping`:

1. вызовите endpoint из браузера или через `curl`;
2. передайте `Accept: application/json`, если хотите увидеть JSON-ответы и JSON-ошибки;
3. ожидайте результат:

```json
{"status":"ok"}
```

## Когда нужны DI definitions

Если handler, middleware или guards требуют собственные сервисы, передайте definitions в конструктор `Router`:

```php
use DI\autowire;
use Oz\Router\Router;

$router = new Router([
    App\Controller\UserController::class => autowire(),
    App\Service\UserService::class => autowire(),
]);
```

Эти definitions используются при создании request-scoped контейнера на каждый совпавший запрос.

## Когда выносить маршруты в файл

Пока вы только проверяете модуль, можно регистрировать маршруты прямо рядом с bootstrap-кодом. Но для реальной интеграции удобнее быстро перейти к routes file:

```php
$router->loadRoutesFromFile(__DIR__ . '/routes/api.php');
```

Файл должен вернуть:

- один `callable`, принимающий `Router`
- или массив `callable`

Пример:

```php
<?php

use Oz\Router\Router;

return static function (Router $router): void {
    $router->get('/ping', static fn (): array => ['status' => 'ok']);
};
```

## Рекомендуемый layout

Если вы используете встроенные entrypoints, наиболее предсказуемый layout выглядит так:

```text
/local/modules/oz.router.sample/config/routes/api.php
/local/modules/oz.router.sample/config/di.php
```

Он хорошо сочетается со встроенным компонентом, потому что `oz:router.provider` ищет DI-файл по правилу:

```text
dirname(dirname(ROUTES_FILE_PATH)) . '/di.php'
```

То есть для `config/routes/api.php` ожидается соседний `config/di.php`.

## Запуск через компонент

Компонент `oz:router.provider` подходит, когда вы хотите встроить runtime в привычный Bitrix flow.

Он:

1. подключает модуль;
2. читает `ROUTES_FILE_PATH`;
3. пытается автоматически загрузить `di.php`;
4. создаёт `Router`;
5. выполняет `RouterRunner`;
6. отправляет response и завершает приложение.

Пример:

```php
$APPLICATION->IncludeComponent('oz:router.provider', '', [
    'ROUTES_FILE_PATH' => '/local/modules/oz.router.sample/config/routes/api.php',
]);
```

## Запуск через Bitrix service

После установки модуля доступен endpoint:

```text
/bitrix/services/oz.api/
```

Service entrypoint:

1. создаёт `Oz\Router\Module\Config`;
2. берёт из него `getConfigRoutesFilePath()`;
3. пробрасывает путь в `oz:router.provider`.

То есть service использует module config как источник маршрутов, а сам provider уже решает, как подключать `di.php`.

## Что выбрать на практике

| Сценарий | Что использовать |
|----------|------------------|
| Нужен самый прозрачный старт | ручной `Router` + `RouterRunner` |
| Нужна интеграция через Bitrix component | `oz:router.provider` |
| Нужен встроенный service endpoint | `/bitrix/services/oz.api/` |

## Типичный next step после первого запуска

Обычно последовательность такая:

1. поднять `GET /ping`;
2. вынести маршруты в routes file;
3. подключить `config/di.php`;
4. добавить groups, guards и middleware;
5. перейти к более сложным handler arguments и validation.
