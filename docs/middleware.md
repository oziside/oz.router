# Middleware

## Обзор

Middleware оборачивают выполнение handler и работают в середине request pipeline: после guards, но до финальной нормализации ответа.

Их стоит использовать, когда нужно:

- модифицировать response headers,
- логировать время выполнения,
- добавлять cross-cutting logic,
- оборачивать handler в дополнительное поведение.

## Контракт middleware

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

## Глобальные middleware

Если обёртка нужна большинству маршрутов, подключайте её глобально:

```php
$router->middleware([
    AuthMiddleware::class,
    TraceMiddleware::class,
]);
```

Это хорошо подходит для auth wrappers, tracing, correlation headers и похожих общих задач.

## Middleware на группах

Группа помогает применять middleware к целому разделу API:

```php
$router->group('/api', static function (Router $router): void {
    $router->middleware(AuthMiddleware::class);
    $router->get('/users', [UserController::class, 'index']);
});
```

Такой подход особенно удобен, если внутри префикса уже есть общая policy.

## Middleware на отдельном маршруте

Когда логика нужна только одному endpoint:

```php
$router
    ->get('/users', [UserController::class, 'index'])
    ->middleware(TraceMiddleware::class);
```

Это делает cross-cutting behavior видимым прямо на уровне route declaration.

## Исключение middleware

Если middleware подключён шире, чем нужно, его можно исключить:

```php
$router->middleware(AuthMiddleware::class);

$router
    ->get('/health', static fn (): array => ['ok' => true])
    ->exceptMiddleware(AuthMiddleware::class);
```

Исключения применяются на уровне route policy и удаляют класс из итогового middleware chain.

## Порядок выполнения

Runtime-схема выглядит так:

1. собираются middleware из global policy и route policy;
2. список разворачивается через `array_reverse()`;
3. первый зарегистрированный middleware становится внешним;
4. handler вызывается в центре цепочки.

Пример middleware, который модифицирует response:

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

Из-за этого safest default всё равно такой:

```php
return $next($request);
```

Так меньше риск неочевидного поведения в middleware chain.

## Middleware и нормализация ответа

Если middleware возвращает не `HttpResponse`, результат всё равно проходит через `ResponseNormalizer`.

Это позволяет вернуть:

- `array`
- DTO/object
- scalar

Но на практике для middleware лучше возвращать именно `HttpResponse`, чтобы поведение было максимально явным.

## Как выбирать между middleware и guard

Полезное правило:

- если нужно разрешить или запретить доступ -> guard
- если нужно обернуть выполнение и повлиять на response -> middleware

То есть middleware не заменяет guards, а решает другой класс задач.

## Практические рекомендации

Обычно удобно идти так:

1. сначала понять базовый dispatch flow;
2. затем добавлять middleware для одного очевидного сценария, например trace header;
3. после этого выносить более сложные обёртки в отдельные классы;
4. не смешивать access control и response decoration в одном middleware без необходимости.
