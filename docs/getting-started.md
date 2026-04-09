[Back to README](../README.md) · [Next Page →](routing.md)

# Старт и точки входа

## Требования

- PHP `>= 8.1`
- Bitrix D7
- зависимости из `composer.json` модуля:
  - `php-di/php-di`
  - `zircote/swagger-php`

В текущем проекте autoload обычно подключается через `local/php_interface/init.php`.

## Базовое подключение

```php
use Bitrix\Main\Loader;

Loader::includeModule('oz.router');
```

## Минимальный bootstrap

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

`RouterRunner` берёт текущий `HttpApplication`, проверяет наличие `HttpContext`, вызывает `Router::dispatch()` и централизованно обрабатывает исключения через `ExceptionHandler`.

## Router c DI definitions

Если контроллеры, middleware или guards требуют свои зависимости, передайте PHP-DI definitions в конструктор:

```php
<?php

use DI\autowire;
use Oz\Router\Router;

$router = new Router([
    App\Controller\UserController::class => autowire(),
    App\Service\UserService::class => autowire(),
]);
```

Эти definitions используются при сборке request-scoped контейнера на каждый совпавший запрос.

## Routes file

Маршруты удобно держать в отдельном PHP-файле и подключать через `loadRoutesFromFile()`:

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

Практический layout из `oz.router.sample`:

```text
/local/modules/oz.router.sample/config/routes/api.php
/local/modules/oz.router.sample/config/di.php
```

Такой layout хорошо сочетается со встроенным компонентом, потому что он ищет DI-файл по правилу:

```text
dirname(dirname(ROUTES_FILE_PATH)) . '/di.php'
```

То есть для `config/routes/api.php` ожидается соседний `config/di.php`.

## Запуск через компонент

Компонент `oz:router.provider`:

1. подключает модуль
2. читает `ROUTES_FILE_PATH`
3. пытается автоматически загрузить `di.php`
4. создаёт `Router`
5. выполняет `RouterRunner`
6. отправляет ответ и завершает приложение

Пример вызова:

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

Сервис:

1. создаёт `Oz\Router\Module\Config`
2. берёт из него `getConfigRoutesFilePath()`
3. пробрасывает путь в `oz:router.provider`

Это делает сохранённый путь к файлу маршрутов основной runtime-настройкой для встроенного service entrypoint.

## Быстрая проверка

1. зарегистрируйте `GET /ping`
2. вызовите endpoint из браузера или `curl`
3. ожидайте JSON-ответ:

```json
{"status":"ok"}
```

## See Also

- [Маршрутизация](routing.md) - методы роутера, группы и обработчики
- [Guards](guards.md) - предобработка доступа до middleware и handler
- [Конфигурация и OpenAPI](configuration.md) - настройки модуля и runtime caveats
