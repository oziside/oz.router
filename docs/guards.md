# Guards

## Обзор

Guards запускаются после match маршрута и после сборки request-scoped контейнера, но до middleware и handler. Их задача проста: решить, можно ли продолжать выполнение.

Если вам нужно:

- проверить токен,
- проверить роль,
- запретить доступ к части API,

то это обычно задача guard, а не middleware.

## Контракт guard

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

Если `canActivate()` возвращает `false`, роутер выбрасывает `403 Forbidden`.

## Что доступно внутри GuardContext

Guard получает `GuardContext`, в котором уже собраны:

- `HttpApplication`
- `HttpContext`
- `HttpRequest`
- текущий `Route`
- `routeParams`
- request-scoped DI container

Это делает guard удобной точкой для правил доступа, которым одновременно нужны request, параметры маршрута и сервисы контейнера.

## Глобальные guards

Если правило должно применяться ко многим маршрутам, подключите guard глобально:

```php
$router->guard(AdminGuard::class);
```

Можно передавать и массив:

```php
$router->guard([
    AuthGuard::class,
    AdminGuard::class,
]);
```

## Guards на группах

Группа полезна, когда нужно защитить целый сегмент API:

```php
$router->group('/api', static function (Router $router): void {
    $router->guard(AuthGuard::class);
    $router->get('/profile', [ProfileController::class, 'show']);
});
```

Такой стиль особенно удобен для `/api`, `/admin`, `/internal` и других префиксных зон.

## Guards на отдельном маршруте

Если правило нужно только одному endpoint:

```php
$router
    ->get('/admin/users', [AdminController::class, 'users'])
    ->guard(AdminGuard::class);
```

Это самый читаемый вариант, когда ограничение относится к конкретному route definition.

## Исключение guards

Если guard включён глобально или на группе, его можно убрать точечно через `exceptGuard()`:

```php
$router->guard(AuthGuard::class);

$router
    ->get('/login', [AuthController::class, 'login'])
    ->exceptGuard(AuthGuard::class);
```

Практически policy-модель работает так:

1. собираются guards из global policy и route policy;
2. exclusions удаляют ненужные классы;
3. итоговый список резолвится через контейнер;
4. guards запускаются по порядку.

## Как выбирать между guard и middleware

Полезное разделение ролей:

- guard отвечает на вопрос “можно ли идти дальше?”
- middleware отвечает за оборачивание выполнения и модификацию response

Если вы не меняете response, не логируете время и не хотите оборачивать execution flow, а просто проверяете доступ, начинайте с guard.

## Поведение при ошибках

Важные сценарии:

- если класс не реализует `CanActivateInterface`, будет `500`
- если `canActivate()` вернул `false`, будет `403 Forbidden`
- если guard сам выбросил исключение, его обработает `ExceptionHandler`

Сообщение по умолчанию:

```text
Access denied by guard <ClassName>.
```

## Практические рекомендации

Обычно удобно идти так:

1. сначала собрать базовый routing flow без guards;
2. затем добавить один guard для auth;
3. после этого выносить более специфичные access rules в отдельные guard-классы;
4. исключения оформлять через `exceptGuard()`, а не через условные конструкции внутри handler.

Так правила доступа остаются видны прямо на уровне route declaration.
