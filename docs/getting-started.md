[Back to README](../README.md) · [Next Page →](routing.md)

# Установка и запуск

## Требования

- PHP `>= 8.4`
- Bitrix D7 с классами `Bitrix\Main\*`
- Composer-зависимости из `composer.json`:
  - `php-di/php-di`
  - `symfony/validator`
  - `zircote/swagger-php`

В этом репозитории пакеты ставятся в `/bitrix/vendor`, а автозагрузка подключается через `local/php_interface/init.php`.

## Установка

1. Разместите модуль в `local/modules/oz.router` или подключите его как пакет типа `bitrix-d7-module`.
2. Установите Composer-зависимости проекта.
3. Убедитесь, что `/bitrix/vendor/autoload.php` подключается до выполнения кода роутера.
4. Установите модуль в административной панели Bitrix.
5. Подключите модуль в коде:

```php
use Bitrix\Main\Loader;

Loader::includeModule('oz.router');
```

## Минимальный запуск

```php
<?php

use Bitrix\Main\Loader;
use Oz\Router\RouterRunner;
use Oz\Router\Router;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loader::includeModule('oz.router');

$router = new Router();

$router->get('/ping', static function (): array {
    return ['status' => 'ok'];
});

$response = (new RouterRunner($router))->run();
$response->send();
```

`RouterRunner` берёт текущие `HttpApplication` и `HttpContext`, затем передаёт текущий `HttpRequest` в роутер.

## Рекомендуемая структура файлов

Если использовать встроенный компонент и сервисную точку входа, удобнее всего держать файлы так:

```text
/local/php_interface/api/routes.php
/local/php_interface/di.php
```

Почему это важно:

- `services/api/index.php` читает путь к файлу маршрутов из настроек модуля
- `oz:router.provider` загружает этот файл маршрутов
- затем компонент пытается автоматически найти DI-конфиг по правилу `dirname(dirname(ROUTES_FILE_PATH)) . '/di.php'`

Для `/local/php_interface/api/routes.php` это даст путь `/local/php_interface/di.php`.

## Практический layout из oz.router.sample

В реальном sample-модуле используется структура:

```text
/local/modules/oz.router.sample/config/routes/api.php
/local/modules/oz.router.sample/config/di.php
```

Этот layout тоже совместим со встроенным компонентом:

- routes file: `/local/modules/oz.router.sample/config/routes/api.php`
- guessed DI file: `/local/modules/oz.router.sample/config/di.php`

То есть для модулей удобен паттерн `config/routes/*.php` + `config/di.php`, а не только размещение файлов в `php_interface`.

## Пример файла маршрутов

```php
<?php

use Oz\Router\Router;

return static function (Router $router): void {
    $router->get('/ping', static fn (): array => ['status' => 'ok']);
};
```

Практический sample использует тот же формат, но с вложенными группами и контроллером:

```php
return static function (Router $router): void {
    $router->withMiddleware([
        Middleware\ResponseMetaMiddleware::class,
    ]);

    $router->group('/api/v1', static function (Router $router): void {
        $router->group('/product', static function (Router $router): void {
            $router->get('/{id}', [ProductController::class, 'getProduct']);
            $router->post('/', [ProductController::class, 'createProduct']);
            $router->put('/{id}', [ProductController::class, 'updateProduct']);
            $router->delete('/{id}', [ProductController::class, 'deleteProduct']);
        });
    });
};
```

## Пример DI-конфига

```php
<?php

use DI\autowire;
use Local\Api\UserController;
use Local\Api\UserService;

return [
    UserController::class => autowire(),
    UserService::class => autowire(),
];
```

В `oz.router.sample` через `config/di.php` подменяются интерфейсы инфраструктурными реализациями:

```php
return [
    ProductRepository::class => autowire(InMemoryProductRepository::class),
    EventBus::class => autowire(BitrixEventBus::class),
];
```

## Запуск через компонент

```php
$APPLICATION->IncludeComponent('oz:router.provider', '', [
    'ROUTES_FILE_PATH' => '/local/php_interface/api/routes.php',
]);
```

Компонент создаёт `Router`, загружает файл маршрутов, пытается подключить `di.php`, выполняет `RouterRunner`, отправляет ответ и завершает приложение.

## Запуск через сервис Bitrix

После установки модуль добавляет сервисную точку входа:

```text
/bitrix/services/oz.api/
```

Этот сервис проксирует в `services/api/index.php`, читает сохранённый путь к маршрутам из настроек модуля и затем включает `oz:router.provider`.

## Быстрая проверка

1. Зарегистрируйте маршрут `/ping`.
2. Откройте `/ping` в браузере или вызовите через `curl`.
3. Ожидаемый ответ:

```json
{"status":"ok"}
```

## See Also

- [Маршрутизация](routing.md) - методы, динамические сегменты, форматы обработчиков
- [Валидация](validation.md) - гидратация аргументов и проверка запроса
- [Настройки](configuration.md) - пути, OpenAPI и поведение Swagger
