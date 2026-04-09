[← Previous Page](guards.md) · [Back to README](../README.md) · [Next Page →](validation.md)

# Middleware

## Контракт

Middleware должны реализовать `Oz\Router\Interface\MiddlewareInterface`:

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

Экземпляры middleware резолвятся через тот же request-scoped контейнер, что и handler.

## Подключение middleware

Глобально:

```php
$router->middleware([
    AuthMiddleware::class,
    TraceMiddleware::class,
]);
```

Внутри группы:

```php
$router->group('/api', static function (Router $router): void {
    $router->middleware(AuthMiddleware::class);
    $router->get('/users', [UserController::class, 'index']);
});
```

На одном маршруте:

```php
$router
    ->get('/users', [UserController::class, 'index'])
    ->middleware(TraceMiddleware::class);
```

## Исключение middleware

```php
$router->middleware(AuthMiddleware::class);

$router
    ->get('/health', static fn (): array => ['ok' => true])
    ->exceptMiddleware(AuthMiddleware::class);
```

Исключения применяются на уровне route policy и удаляют соответствующие классы из итогового списка middleware.

## Порядок выполнения

Схема такая:

1. выбираются middleware из global policy и route policy
2. список разворачивается в цепочку через `array_reverse()`
3. первый зарегистрированный middleware становится внешним
4. handler вызывается в самом центре цепочки

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

## Семантика `null`

`MiddlewareRunner` поддерживает три сценария:

- middleware вернул `HttpResponse` -> ответ уходит дальше по цепочке
- middleware вызвал `$next()` и вернул `null` -> используется downstream response
- middleware не вызвал `$next()` и вернул `null` -> runner сам продолжит цепочку

Из-за этого безопасный шаблон остаётся прежним:

```php
return $next($request);
```

## Middleware и нормализация ответа

Если middleware возвращает не `HttpResponse`, результат всё равно проходит через `ResponseNormalizer`.

Это позволяет middleware вернуть:

- `array`
- DTO/object
- scalar

но на практике для middleware лучше возвращать именно `HttpResponse`, чтобы поведение было явным.

## Когда использовать middleware, а не guard

Используйте middleware, если нужно:

- модифицировать response headers
- логировать время выполнения
- обернуть handler в cross-cutting logic
- дополнять JSON-ответ служебными данными

Используйте guard, если нужно просто разрешить или запретить доступ.

## See Also

- [Guards](guards.md) - проверки доступа до middleware chain
- [Валидация](validation.md) - что происходит внутри handler arguments resolution
- [Маршрутизация](routing.md) - lifecycle dispatch и нормализация ответов
