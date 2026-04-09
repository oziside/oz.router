[← Previous Page](routing.md) · [Back to README](../README.md) · [Next Page →](middleware.md)

# Guards

## Зачем нужны guards

Guards выполняются после совпадения маршрута и сборки request container, но до middleware и handler.

Типичный сценарий:

- проверка токена
- проверка роли
- запрет доступа к части API

Если guard запрещает доступ, роутер выбрасывает `403 Forbidden`.

## Контракт

Guard должен реализовать `Oz\Router\Interface\CanActivateInterface`:

```php
use Oz\Router\Guard\GuardContext;
use Oz\Router\Interface\CanActivateInterface;

final class AdminGuard implements CanActivateInterface
{
    public function canActivate(GuardContext $context): bool
    {
        $request = $context->getRequest();

        return $request->getHeader('X-Admin-Token') === 'secret';
    }
}
```

## GuardContext

В guard доступен `GuardContext`, который содержит:

- `HttpApplication`
- `HttpContext`
- `HttpRequest`
- текущий `Route`
- `routeParams`
- request-scoped DI container

Это делает guard удобным местом для проверок, которым нужен и request, и параметры маршрута, и сервисы контейнера.

## Подключение guards

Глобально:

```php
$router->guard(AdminGuard::class);
```

С массивом:

```php
$router->guard([
    AuthGuard::class,
    AdminGuard::class,
]);
```

На уровне группы:

```php
$router->group('/api', static function (Router $router): void {
    $router->guard(AuthGuard::class);
    $router->get('/profile', [ProfileController::class, 'show']);
});
```

На уровне конкретного маршрута:

```php
$router
    ->get('/admin/users', [AdminController::class, 'users'])
    ->guard(AdminGuard::class);
```

## Исключение guards

Guard можно исключить на маршруте или в группе через `exceptGuard()`:

```php
$router->guard(AuthGuard::class);

$router
    ->get('/login', [AuthController::class, 'login'])
    ->exceptGuard(AuthGuard::class);
```

Именно так устроена policy-модель роутера:

1. собираются включённые guards из global policy и route policy
2. route exclusions удаляют ненужные классы
3. итоговый список резолвится через контейнер
4. guards запускаются по порядку

## Поведение при ошибках

- если guard-класс не реализует `CanActivateInterface`, будет `500`
- если `canActivate()` вернул `false`, будет `403 Forbidden`
- если guard сам выбросил исключение, его обработает `ExceptionHandler`

Сообщение ошибки по умолчанию:

```text
Access denied by guard <ClassName>.
```

## Guards и middleware

Guards не предназначены для постобработки ответа. Для этого нужен middleware.

Практическое разделение такое:

- guard отвечает на вопрос "можно ли идти дальше?"
- middleware отвечает за оборачивание выполнения и модификацию ответа

## See Also

- [Маршрутизация](routing.md) - общий lifecycle dispatch
- [Middleware](middleware.md) - оборачивание handler и post-processing
- [Конфигурация и OpenAPI](configuration.md) - встроенные entrypoints и настройки модуля
