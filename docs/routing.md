# Маршрутизация

## Обзор

`oz.router` строится вокруг простого потока: вы регистрируете маршруты, роутер сопоставляет текущий запрос, собирает request-scoped контейнер, выполняет guards и middleware, вызывает handler и нормализует результат в `HttpResponse`.

Если вы только начинаете работать с модулем, именно эту страницу лучше читать первой после [Старт и точки входа](getting-started.md). Она даёт основную mental model, а остальные страницы раскрывают отдельные части runtime подробнее.

## Базовая регистрация маршрутов

Самый простой маршрут принимает path и handler:

```php
use Oz\Router\Router;

$router = new Router();

$router->get('/ping', static function (): array {
    return ['status' => 'ok'];
});
```

Handler может быть:

- `callable`
- строкой в формате `Class@method`
- массивом `[ClassName::class, 'method']`

Пример:

```php
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', UserController::class . '@update');
```

## Где хранить маршруты

На практике маршруты удобно выносить в отдельный PHP-файл:

```php
$router->loadRoutesFromFile(__DIR__ . '/routes/api.php');
```

`RoutesFileLoader` ожидает, что файл вернёт:

- один `callable`, принимающий `Router`
- или массив `callable`

Пример routes file:

```php
<?php

use Oz\Router\Router;

return static function (Router $router): void {
    $router->get('/ping', static fn (): array => ['status' => 'ok']);
};
```

Если вы используете встроенные entrypoints модуля, лучше придерживаться layout:

```text
config/routes/api.php
config/di.php
```

Причина в том, что `oz:router.provider` ищет `di.php` относительно routes file, а не по отдельной runtime-настройке. Подробнее это разобрано на странице [Архитектура и runtime](architecture.md).

## Доступные методы роутера

Роутер поддерживает основные HTTP verb helpers:

```php
$router->get('/ping', static fn (): array => ['ok' => true]);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->patch('/users/{id}', [UserController::class, 'patch']);
$router->delete('/users/{id}', [UserController::class, 'delete']);
$router->option('/options', static fn (): string => 'ok');
$router->any('/health', static fn (): string => 'ok');
```

Если нужно обслуживать несколько методов одним handler, используйте `add()`:

```php
$router->add(['GET', 'POST'], '/import', ImportController::class . '@run');
```

## Нормализация path и method

Перед match роутер нормализует вход:

- `users`, `/users` и `/users/` считаются одним и тем же path
- HTTP method приводится к верхнему регистру
- пустой или невалидный method даёт `400 Bad Request`

Это делает регистрацию маршрутов менее хрупкой, но важно помнить, что match всё равно остаётся строгим по итоговому нормализованному path.

## Route Parameters

### Обязательные параметры

Поддерживаются динамические сегменты вида:

```php
$router->get('/users/{id}', [UserController::class, 'show']);
```

После совпадения path параметр попадает:

- в handler arguments
- в request-scoped DI контейнер под ключом `'route_params'`

### Ограничения через регулярные выражения

Можно задавать constraint прямо в path:

```php
$router->get('/users/{id:\d+}', [UserController::class, 'show']);
```

Это удобно, когда нужно сразу отфильтровать некорректный URI ещё на этапе match.

## Route Groups

Группы позволяют накапливать path prefix и policy:

```php
$router->group('/api', static function (Router $router): void {
    $router->get('/ping', static fn (): array => ['scope' => 'api']);

    $router->group('/v1', static function (Router $router): void {
        $router->get('/users', static fn (): array => ['version' => 'v1']);
    });
});
```

В результате будут зарегистрированы:

- `GET /api/ping`
- `GET /api/v1/users`

Практически `group()` полезен в трёх сценариях:

- versioned API
- общие guards и middleware для набора маршрутов
- группировка по bounded URI segment вроде `/admin`, `/internal`, `/api`

## Guards и Middleware на маршрутах

Маршруты и группы поддерживают policy-методы:

```php
$router->guard(AuthGuard::class);
$router->middleware(TraceMiddleware::class);

$router
    ->get('/admin/users', [AdminController::class, 'index'])
    ->guard(AdminGuard::class)
    ->middleware(AdminAuditMiddleware::class);
```

Также поддерживаются исключения:

```php
$router
    ->get('/health', static fn (): array => ['ok' => true])
    ->exceptGuard(AuthGuard::class)
    ->exceptMiddleware(TraceMiddleware::class);
```

Полезная mental model:

- guard отвечает на вопрос “можно ли идти дальше?”
- middleware оборачивает выполнение и может модифицировать response

Подробности вынесены в [Guards](guards.md) и [Middleware](middleware.md), но для чтения routing lifecycle достаточно помнить именно это разделение ролей.

## Handler Arguments и DI

На каждый совпавший маршрут создаётся новый контейнер через `RequestContainerFactory`.

В него автоматически попадают:

- `Bitrix\Main\HttpApplication`
- `Bitrix\Main\HttpContext`
- `Bitrix\Main\HttpRequest`
- `'route_params'`
- пользовательские definitions из `new Router($definitions)`

Это позволяет писать handler с типизированными аргументами:

```php
use DI\autowire;

$router = new Router([
    App\Controller\UserController::class => autowire(),
    App\Service\UserService::class => autowire(),
]);
```

Пример handler с route parameter, DTO и DI:

```php
final class ShowUserQuery
{
    public function __construct(
        public readonly bool $withPosts = false,
    ) {}
}

final class UserController
{
    public function __construct(
        private readonly UserService $service
    ) {}

    public function show(int $id, ShowUserQuery $query): array
    {
        return $this->service->find($id, $query->withPosts);
    }
}
```

## Источники входных данных

Когда роутер резолвит handler arguments, input собирается в таком порядке:

1. query-параметры
2. `POST`-данные
3. JSON body
4. параметры маршрута

Поздние источники перекрывают ранние, поэтому route params имеют наивысший приоритет.

Это особенно важно понимать, если вы ожидаете одинаковые имена полей и в body, и в path.

## Что умеет резолвер аргументов

Из коробки поддерживаются:

- scalar-типы `string`, `int`, `float`, `bool`, `array`
- nullable и union-типы
- `HttpRequest`
- backed enum
- DTO-объекты с гидратацией через конструктор
- сервисы из DI-контейнера

Если тип нельзя корректно собрать из входа, вы получите mapping error, который будет превращён в `400 Bad Request`.

## Валидация аргументов и DTO

После маппинга входа `RequestValidator` проверяет аргументы и DTO через `Bitrix\Main\Validation\ValidationService`.

Пример:

```php
use Bitrix\Main\Validation\Rule;

final class CreateUserReq
{
    public function __construct(
        #[Rule\NotEmpty]
        public readonly string $name,

        #[Rule\Length(max: 255)]
        public readonly string $email,
    ) {}
}

$router->post('/users', static function (CreateUserReq $req): array {
    return ['created' => true, 'name' => $req->name];
});
```

Разделение ошибок такое:

- ошибка маппинга -> `400 Bad Request`
- ошибка валидации -> `422 Unprocessable Content`

Отдельные детали описаны на странице [Валидация](validation.md).

## Порядок dispatch

Внутренне `Router::dispatch()` проходит по такому pipeline:

1. извлекает method и path
2. находит совпавший `Route`
3. создаёт request-scoped container
4. выполняет guards
5. выполняет middleware chain
6. вызывает handler
7. нормализует результат в `HttpResponse`

Если маршрут не найден:

- `404 Not Found`, если path неизвестен
- `405 Method Not Allowed`, если path найден, но method не совпал

## Нормализация ответа

`ResponseNormalizer` приводит результат handler к `HttpResponse`.

Основные сценарии:

- `HttpResponse` возвращается как есть
- `array` и `object` превращаются в `Bitrix\Main\Engine\Response\Json`
- scalar и `null` становятся телом обычного `HttpResponse`

Для объектов JSON-сериализация работает лучше всего, если объект:

- реализует `JsonSerializable`
- реализует `Bitrix\Main\Type\Contract\Arrayable`
- или помечен `#[Oz\Router\Attribute\JsonResource]`

Пример:

```php
use Oz\Router\Attribute\JsonResource;

#[JsonResource]
final class ProductRes
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
```

## Ошибки и формат ответа

`RouterRunner` перехватывает исключения и передаёт их в `ExceptionHandler`.

Формат ответа зависит от `Accept` header:

- `application/json` -> JSON c `statusCode`, `message` и, при необходимости, `errors`
- всё остальное -> HTML response с текстом ошибки

Для API-клиентов это критично: чтобы получать JSON-ошибки стабильно, отправляйте `Accept: application/json`.

## Практические рекомендации

Если смотреть на модуль в стиле Laravel docs, то хорошая рабочая стратегия такая:

1. начните с простого `GET /ping`
2. затем вынесите маршруты в routes file
3. подключите DI definitions
4. используйте groups для версионирования и префиксов
5. добавляйте guards и middleware только после того, как базовый routing flow уже ясен

Так вы быстрее поймёте основной pipeline и избежите ощущения, что магия происходит сразу в нескольких местах.
