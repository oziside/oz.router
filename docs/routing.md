[← Previous Page](getting-started.md) · [Back to README](../README.md) · [Next Page →](guards.md)

# Маршрутизация

## Регистрация маршрутов

```php
$router->get('/ping', static fn (): array => ['status' => 'ok']);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', UserController::class . '@update');
$router->patch('/users/{id}', UserController::class . '@patch');
$router->delete('/users/{id}', UserController::class . '@delete');
$router->option('/options', static fn (): string => 'ok');
$router->any('/health', static fn (): string => 'ok');

$router->add(['GET', 'POST'], '/import', ImportController::class . '@run');
```

Поддерживаются handler-форматы:

- `Class@method`
- `[ClassName::class, 'method']`
- любой `callable`

## Нормализация path и method

- пути приводятся к каноническому виду с ведущим `/`
- `users`, `/users` и `/users/` считаются одним маршрутом
- HTTP method нормализуется через `strtoupper(trim(...))`
- пустой или невалидный method приводит к `400 Bad Request`

## Динамические сегменты

Поддерживаются:

- `/products/{id}`
- `/users/{id:\d+}`

Пример:

```php
$router->get('/users/{id:\d+}', [UserController::class, 'show']);
```

После совпадения path-параметры URL-декодируются и попадают в handler arguments и в DI под ключом `'route_params'`.

## Группы

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

`group()` объединяет не только path prefix, но и накопленную policy для guards и middleware.

## Routes из файла

```php
$router->loadRoutesFromFile(__DIR__ . '/routes/api.php');
```

`RoutesFileLoader` проверяет:

- путь не пустой
- файл существует
- файл читается
- каждая запись в массиве loaders является `callable`

## Request-scoped container

На каждый совпавший маршрут создаётся новый контейнер через `RequestContainerFactory`.

В контейнер автоматически регистрируются:

- `Bitrix\Main\HttpApplication`
- `Bitrix\Main\HttpContext`
- `Bitrix\Main\HttpRequest`
- `'route_params'`
- пользовательские definitions из `new Router($definitions)`

Это позволяет автосвязывать:

- контроллеры
- сервисы
- middleware
- guards

## Резолв аргументов handler

Входные данные собираются в таком порядке:

1. query-параметры
2. `POST`-данные
3. JSON body
4. параметры маршрута

Поздние источники перекрывают ранние, поэтому path params имеют максимальный приоритет.

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

## Порядок dispatch

`Router::dispatch()` работает так:

1. извлекает method и path
2. находит совпавший `Route`
3. создаёт request container
4. выполняет guards
5. выполняет middleware chain
6. вызывает handler
7. нормализует результат в `HttpResponse`

Если маршрут не найден:

- `404 Not Found`, если path неизвестен
- `405 Method Not Allowed`, если path найден, но method не совпал

## Нормализация ответа

`ResponseNormalizer` обрабатывает результат handler или middleware так:

- `HttpResponse` возвращается как есть
- `array` и `object` превращаются в `Bitrix\Main\Engine\Response\Json`
- scalar и `null` становятся телом обычного `HttpResponse`

Для объектов сериализация работает лучше всего, если объект:

- реализует `JsonSerializable`
- реализует `Bitrix\Main\Type\Contract\Arrayable`
- или помечен `#[Oz\Router\Attribute\JsonResource]`

Пример DTO-ответа:

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

## Формат ошибок

`RouterRunner` перехватывает все исключения и передаёт их в `ExceptionHandler`.

Поведение зависит от `Accept`:

- `application/json` -> JSON c `statusCode`, `message` и при необходимости `errors`
- всё остальное -> HTML-ответ с текстом ошибки

Это важно для API: чтобы получать JSON-ошибки стабильно, клиент должен отправлять `Accept: application/json`.

## See Also

- [Старт и точки входа](getting-started.md) - bootstrap и встроенные entrypoints
- [Guards](guards.md) - контроль доступа до middleware и handler
- [Валидация](validation.md) - DTO, validation rules и ответы `422`
