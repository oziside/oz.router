[← Previous Page](validation.md) · [Back to README](../README.md) · [Next Page →](configuration.md)

# Middleware

## Интерфейс

Middleware должны реализовывать `Oz\Router\Interface\MiddlewareInterface`:

```php
use Bitrix\Main\HttpRequest;
use Bitrix\Main\HttpResponse;
use Oz\Router\Interface\MiddlewareInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(HttpRequest $request, \Closure $next): ?HttpResponse
    {
        if (!$request->getHeader('X-Token'))
        {
            return (new HttpResponse())
                ->setStatus(401)
                ->setContent('Unauthorized');
        }

        return $next($request);
    }
}
```

Экземпляры middleware резолвятся из того же PHP-DI контейнера, что и обработчики маршрутов, поэтому зависимости в конструкторе можно автосвязывать.

## Порядок выполнения

- middleware оборачиваются в обратном порядке регистрации
- код до `$next($request)` идёт снаружи внутрь
- код после `$next($request)` идёт обратно изнутри наружу

Пример:

```php
final class TraceMiddleware implements MiddlewareInterface
{
    public function handle(HttpRequest $request, \Closure $next): ?HttpResponse
    {
        $response = $next($request);
        $response->addHeader('X-App', 'oz.router');

        return $response;
    }
}
```

## Семантика возврата

- если middleware возвращает `HttpResponse`, этот ответ сразу уходит дальше по цепочке
- если middleware возвращает `null` после вызова `$next`, роутер использует уже полученный downstream-ответ
- если middleware возвращает `null` и вообще не вызывает `$next`, роутер всё равно сам вызовет следующий middleware или обработчик

Из-за последнего правила безопасный базовый шаблон всё равно такой:

```php
return $next($request);
```

## Подключение

Глобально:

```php
$router->withMiddleware([
    AuthMiddleware::class,
    TraceMiddleware::class,
]);
```

В группе:

```php
$router->group('/api', static function (Router $router): void {
    $router->withMiddleware(AuthMiddleware::class);
    $router->get('/users', UserController::class . '@index');
});
```

На маршруте:

```php
$router
    ->get('/users', UserController::class . '@index')
    ->withMiddleware(TraceMiddleware::class)
    ->withoutMiddleware(AuthMiddleware::class);
```

Практически в `oz.router.sample` используется глобальное подключение post-response middleware:

```php
$router->withMiddleware([
    Middleware\ResponseMetaMiddleware::class,
]);
```

Этот middleware:

- пропускает запрос дальше
- проверяет, что ответ имеет `Content-Type: application/json`
- декодирует JSON body
- добавляет служебный блок `_meta`
- собирает новый JSON-ответ с тем же HTTP status

## Исключение middleware

- `Router::withoutMiddleware()` работает только внутри `group()`
- `Route::withoutMiddleware()` исключает middleware для одного маршрута

Порядок отбора такой:

1. глобальные middleware
2. middleware из активных групп
3. middleware самого маршрута
4. исключения на уровне группы и маршрута

## Паттерн авторизации из sample

В `oz.router.sample` есть `AuthorizationMiddleware`, который:

- получает `HttpContext` из DI
- читает `X-Auth-Token`
- при невалидном токене возвращает JSON `401`

Также он реализует `CanActivateInterface`, но важно понимать: сам роутер этот интерфейс не вызывает автоматически.

То есть `CanActivateInterface` сейчас можно использовать как внутренний паттерн организации кода внутри middleware, а не как отдельный встроенный lifecycle hook.

## See Also

- [Валидация](validation.md) - обработчик уже после резолва и проверки аргументов
- [Настройки](configuration.md) - параметры встроенных точек входа
- [Установка и запуск](getting-started.md) - bootstrap и компонент
