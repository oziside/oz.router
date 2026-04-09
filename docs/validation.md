[← Previous Page](middleware.md) · [Back to README](../README.md) · [Next Page →](configuration.md)

# Валидация

## Что валидируется

`HandlerArgumentsResolver` сначала маппит вход в аргументы handler, затем `RequestValidator` проверяет каждый аргумент через `Bitrix\Main\Validation\ValidationService`.

Проверяются:

- scalar-аргументы handler
- enum-аргументы
- DTO-объекты, которые были гидратированы из входа
- вложенные поля DTO, если объект попадает под object validation

## Источники входных данных

Input собирается из:

1. query string
2. `POST`-данных
3. JSON body
4. route params

Если для DTO существует одноимённый ключ и он содержит массив, гидратор берёт именно этот массив. Иначе используется весь собранный input.

## DTO-гидратация

Класс считается гидратируемым request object, если его можно создать через конструктор и типы параметров поддерживаются резолвером.

Поддерживаются:

- `string`, `int`, `float`, `bool`, `array`
- nullable-типы
- union-типы
- backed enum
- `HttpRequest`
- вложенные DTO того же типа модели

## Пример DTO

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

## Валидация route params и scalar arguments

Можно валидировать и отдельные параметры handler:

```php
use Bitrix\Main\Validation\Rule;

$router->get('/users/{id}', static function (
    #[Rule\Min(1)]
    int $id
): array {
    return ['id' => $id];
});
```

## Ошибки маппинга и ошибки валидации

Это две разные категории:

- ошибка маппинга -> `400 Bad Request`
- ошибка валидации -> `422 Unprocessable Content`

К ошибкам маппинга относятся:

- отсутствует обязательное значение
- payload нельзя привести к ожидаемому типу
- передан некорректный enum value
- DTO нельзя корректно собрать

К ошибкам валидации относятся уже собранные аргументы, не прошедшие правила `ValidationService`.

## Формат JSON-ошибки

Если клиент отправляет `Accept: application/json`, `ExceptionHandler` вернёт JSON:

```json
{
  "statusCode": 422,
  "message": "Request validation failed.",
  "errors": [
    {
      "message": "This value should not be empty.",
      "field": "req.name"
    }
  ]
}
```

Особенности:

- поле `errors` добавляется только для `RequestValidationException`
- `field` собирается из имени аргумента и кода ошибки валидатора
- для HTML-клиентов вместо JSON вернётся текстовый ответ

## PATCH-подобные сценарии

Для частичного обновления удобно использовать nullable DTO и составные правила:

```php
use Bitrix\Main\Validation\Rule;

#[Rule\AtLeastOnePropertyNotEmpty(
    propertyNames: ['name', 'price'],
    allowZero: true,
    showPropertyNames: true
)]
final class UpdateProductReq
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?float $price = null,
    ) {}
}
```

Такой DTO допускает частичное обновление, но не разрешает полностью пустой payload.

## See Also

- [Маршрутизация](routing.md) - как input попадает в handler arguments
- [Middleware](middleware.md) - lifecycle вокруг вызова handler
- [Конфигурация и OpenAPI](configuration.md) - настройки модуля и caveats встроенных инструментов
