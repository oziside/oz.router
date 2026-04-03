[← Previous Page](getting-started.md) · [Back to README](../README.md) · [Next Page →](validation.md)

# Маршрутизация

## Регистрация маршрутов

```php
$router->get('/ping', static fn (): array => ['status' => 'ok']);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', UserController::class . '@update');
$router->patch('/users/{id}', UserController::class . '@patch');
$router->delete('/users/{id}', UserController::class . '@delete');
$router->option('/options', static fn () => 'ok'); // matches the HTTP OPTIONS method
$router->any('/health', static fn () => 'ok');

$router->add(['GET', 'POST'], '/import', ImportController::class . '@run');
```

Путь нормализуется к виду с ведущим слешем, поэтому `'users'`, `'/users'` и `'/users/'` считаются одним и тем же маршрутом.

## Форматы обработчиков

Поддерживаются:

- `Class@method`
- `[ClassName::class, 'method']`
- любой `callable`

```php
$router->get('/a', UserController::class . '@show');
$router->get('/b', [UserController::class, 'show']);
$router->get('/c', static fn () => 'ok');
```

Строковые и массивные обработчики резолвятся через request-scoped DI-контейнер.

## Динамические параметры

- `/products/{id}` - именованный параметр
- `/users/{id:\d+}` - именованный параметр с regex-ограничением

```php
$router->get('/users/{id:\d+}', UserController::class . '@show');
```

Параметры пути URL-декодируются перед передачей в обработчик.

## Группы и префиксы

```php
$router->group('/api', static function (Router $router): void {
    $router->get('/ping', static fn () => ['scope' => 'api']);

    $router->group('/v1', static function (Router $router): void {
        $router->get('/users', static fn () => ['version' => 'v1']);
    });
});
```

Вложенные группы автоматически объединяют префиксы. В примере выше будут созданы `/api/ping` и `/api/v1/users`.

Практический пример из `oz.router.sample`:

```php
$router->group('/api/v1', static function (Router $router): void {
    $router->group('/product', static function (Router $router): void {
        $router->get('/{id}', [ProductController::class, 'getProduct']);
        $router->post('/', [ProductController::class, 'createProduct']);
        $router->put('/{id}', [ProductController::class, 'updateProduct']);
        $router->delete('/{id}', [ProductController::class, 'deleteProduct']);
    });
});
```

Это даёт компактное versioning-дерево без ручной конкатенации строк:

- `GET /api/v1/product/{id}`
- `POST /api/v1/product/`
- `PUT /api/v1/product/{id}`
- `DELETE /api/v1/product/{id}`

## Маршруты из файла

Маршруты можно вынести в отдельный PHP-файл:

```php
$router->loadRoutesFromFile(__DIR__ . '/routes.php');
```

Файл может возвращать:

- один `callable`, принимающий `Router`
- массив `callable`, где каждый принимает `Router`

```php
<?php

use Oz\Router\Router;

return static function (Router $router): void {
    $router->get('/ping', static fn (): array => ['status' => 'ok']);
};
```

## Request DI-контейнер

Для каждого совпавшего запроса роутер собирает новый PHP-DI контейнер и регистрирует в нём:

- `Bitrix\Main\HttpApplication`
- `Bitrix\Main\HttpContext`
- `Bitrix\Main\HttpRequest`
- `'route_params'` с параметрами маршрута

Собственные определения можно передать в `new Router($definitions)`.

## Резолв аргументов обработчика

Входные данные для обработчика собираются из:

1. query-параметров
2. `POST`-данных формы
3. JSON-body
4. параметров маршрута

Каждый следующий источник перекрывает предыдущий, поэтому приоритет у параметров маршрута.

Резолвер поддерживает:

- scalar-типы `string`, `int`, `float`, `bool`, `array`
- nullable и union-типы
- `HttpRequest`
- backed enum
- DTO-объекты с гидратацией через конструктор
- сервисы из DI-контейнера

Пример:

```php
final class ShowUserQuery
{
    public function __construct(
        public readonly bool $withPosts = false,
    ) {
    }
}

final class UserController
{
    public function __construct(private readonly UserService $service)
    {
    }

    public function show(HttpRequest $request, int $id, ShowUserQuery $query): array
    {
        return [
            'method' => $request->getRequestMethod(),
            'user' => $this->service->find($id, $query->withPosts),
        ];
    }
}
```

На практике из `oz.router.sample` это выглядит так:

```php
public function updateProduct(
    int $id,
    Req\UpdateProductReq $req
): void
{
    $this->updateProduct->exec(new UpdateProduct\Command(
        id: $id,
        name: $req->name,
        code: $req->code,
        price: $req->price,
        sort: $req->sort
    ));
}
```

Здесь:

- `int $id` приходит из path-параметра `/{id}`
- `UpdateProductReq $req` гидратируется из JSON-body
- application handler и его зависимости приходят из DI-контейнера

## Ответы

Результат обработчика нормализуется так:

- `HttpResponse` и его наследники возвращаются как есть
- `array` и `object` превращаются в JSON-ответ
- scalar-значения и `null` становятся текстовым содержимым ответа

Для объектов управляемая сериализация лучше всего работает, если объект:

- реализует `JsonSerializable`
- реализует `Bitrix\Main\Type\Contract\Arrayable`
- или помечен атрибутом `#[Oz\Router\Attribute\JsonResource]`

Практический паттерн из `oz.router.sample`:

```php
#[JsonResource]
final class ProductCreatedRes
{
    public function __construct(
        public readonly int $id
    ) {}
}
```

Контроллер может вернуть такой DTO напрямую, а роутер сам сериализует его в JSON.

## OpenAPI и реальные статусы

`oz.router.sample` показывает важный нюанс: OpenAPI-атрибуты описывают контракт, но не управляют runtime-поведением.

Например:

- `createProduct()` в sample задокументирован как `201`
- фактически метод возвращает DTO-объект без `setStatus(201)`
- значит runtime-ответ останется стандартным `200`

То же касается `void`-методов `updateProduct()` и `deleteProduct()`: без явного `HttpResponse` они завершатся обычным ответом со статусом `200`.

Если нужен точный HTTP status, возвращайте `HttpResponse` вручную.

Ещё один практический caveat из sample: OpenAPI `path` в аннотациях должен синхронизироваться с реальными group-prefixes вручную. Роутер не сверяет их между собой.

## Ответы по умолчанию

- `404 Not Found` - путь не найден
- `405 Method Not Allowed` - путь найден, но для другого метода

## See Also

- [Установка и запуск](getting-started.md) - bootstrap, компонент и сервисный вход
- [Валидация](validation.md) - правила валидации и ответы `422`
- [Middleware](middleware.md) - выполнение middleware вокруг обработчика
