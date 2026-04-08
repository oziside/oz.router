[← Previous Page](routing.md) · [Back to README](../README.md) · [Next Page →](middleware.md)

# Валидация

## Что валидируется

`oz.router` валидирует уже резолвленные аргументы обработчика через `Bitrix\Main\Validation\ValidationService`.

Проверка выполняется для:

- scalar- и enum-параметров, пришедших из запроса
- DTO-подобных объектов, гидратированных из входных данных
- вложенных аргументов конструктора таких DTO

Если валидация не проходит, роутер перехватывает `RequestValidationException` и возвращает RFC 7807 JSON со статусом `422`.

## Правила гидратации DTO

Класс считается request DTO, если он:

- существует и может быть создан
- имеет конструктор
- содержит только поддерживаемые типы аргументов конструктора

Поддерживаемые типы аргументов конструктора:

- `string`, `int`, `float`, `bool`, `array`
- `?type` и union-типы
- backed enum
- `HttpRequest`
- вложенные DTO, которые подчиняются тем же правилам

Intersection-типы не поддерживаются.

## Маппинг входных данных

Если во входных данных есть ключ с именем DTO-параметра и в нём лежит массив, именно этот массив используется как payload DTO.

Иначе для DTO используется весь объединённый input запроса.

Пример:

```json
{
  "payload": {
    "name": "Alice",
    "email": "alice@example.com"
  }
}
```

```php
final class CreateUserPayload
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {
    }
}

$router->post('/users', static function (CreateUserPayload $payload): array {
    return ['created' => true, 'email' => $payload->email];
});
```

## Пример с ограничениями

```php
use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserPayload
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $name,
        #[Assert\Email]
        public readonly string $email,
    ) {
    }
}

$router->post('/users', static function (CreateUserPayload $payload): array {
    return ['created' => true];
});
```

Практический sample использует этот же механизм через `Bitrix\Main\Validation\Rule`:

```php
final class CreateProductReq
{
    public function __construct(
        #[Rule\NotEmpty]
        #[Rule\Length(max: 255)]
        public readonly string $name,

        #[Rule\NotEmpty]
        #[Rule\Length(min: 3, max: 100)]
        #[Rule\RegExp(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public readonly string $code,

        #[Rule\Min(0)]
        public readonly int $sort,

        #[Rule\Min(0)]
        public readonly float $price
    ) {}
}
```

Можно валидировать и аргументы самого обработчика:

```php
use Symfony\Component\Validator\Constraints as Assert;

$router->get('/users/{id}', static function (
    #[Assert\Positive]
    int $id
): array {
    return ['id' => $id];
});
```

Для PATCH/PUT-подобных сценариев sample показывает ещё один полезный паттерн:

```php
#[Rule\AtLeastOnePropertyNotEmpty(
    propertyNames: ['name', 'code', 'sort', 'price'],
    allowZero: true,
    showPropertyNames: true
)]
final class UpdateProductReq
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $code = null,
        public readonly ?int $sort = null,
        public readonly ?float $price = null
    ) {}
}
```

Это удобно для частичного обновления, когда нужно разрешить nullable-поля, но запретить пустой payload целиком.

## Формат ошибки валидации

Ответ имеет вид:

```json
{
  "type": "urn:oz-router:problem:validation-failed",
  "title": "Unprocessable Content",
  "status": 422,
  "detail": "Request validation failed.",
  "errors": [
    {
      "message": "This value should not be blank.",
      "field": "payload.name"
    }
  ]
}
```

Нюансы:

- ошибки валидации объекта получают префикс имени аргумента, например `payload.email`
- ключ `field` присутствует всегда; если путь к полю определить нельзя, он будет `null`
- пользовательские данные ошибки сохраняются в `customData`, если они есть
- статус ответа всегда `422`
- `Content-Type` ответа: `application/problem+json`

Практический нюанс из sample: DTO-параметр необязательно должен получать вложенный payload вроде `{ "req": { ... } }`.

Если ключа с именем аргумента нет, резолвер использует весь объединённый input запроса. Поэтому сигнатура:

```php
public function createProduct(CreateProductReq $req): ProductCreatedRes
```

корректно работает с JSON body такого вида:

```json
{
  "name": "Teddy Bear",
  "code": "teddy-bear",
  "sort": 10,
  "price": 19.99
}
```

## Когда ошибка возникает раньше валидации

Резолвер бросает исключение ещё до валидации, если вход нельзя замапить в сигнатуру обработчика. Например:

- отсутствует обязательное значение
- payload для DTO не является массивом
- передано некорректное значение enum
- класс нельзя инстанцировать

Такие ошибки теперь превращаются в RFC 7807 ответ со статусом `400 Bad Request`, потому что это ошибка маппинга HTTP-входа в сигнатуру обработчика, а не ошибка бизнес-валидации.

## See Also

- [Маршрутизация](routing.md) - как именно собираются аргументы из запроса
- [Middleware](middleware.md) - выполнение цепочки вокруг валидируемого обработчика
- [Настройки](configuration.md) - настройки модуля и OpenAPI
