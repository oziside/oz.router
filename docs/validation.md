# Валидация

## Обзор

В `oz.router` валидация идёт после маппинга входных данных в handler arguments. Сначала `HandlerArgumentsResolver` пытается собрать значения и DTO, затем `RequestValidator` проверяет их через `Bitrix\Main\Validation\ValidationService`.

Это означает, что в runtime есть две отдельные стадии:

- маппинг входа к ожидаемым типам;
- валидация уже собранных аргументов.

Именно поэтому ошибки маппинга и ошибки валидации имеют разные HTTP-статусы.

## Что валидируется

Из коробки валидация применяется к:

- scalar-аргументам handler
- enum-аргументам
- DTO-объектам, гидратированным из входа
- вложенным полям DTO, если объект попадает под object validation

На практике чаще всего вы будете валидировать либо отдельные route/query аргументы, либо DTO request objects.

## Откуда берётся input

Input собирается в таком порядке:

1. query string
2. `POST`-данные
3. JSON body
4. route params

Поздние источники перекрывают ранние. Если для DTO существует одноимённый ключ и он содержит массив, гидратор возьмёт именно этот массив. Иначе используется весь собранный input.

Это важно учитывать, если имена параметров повторяются между body и route params.

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

Самый типичный сценарий:

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

Такой стиль удобен тем, что:

- структура request видна в одном классе;
- правила валидации лежат рядом с полями;
- handler получает уже нормализованный объект, а не сырой массив.

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

Это удобно, когда DTO не нужен, а правило относится к одному конкретному аргументу.

## Ошибки маппинга против ошибок валидации

Это две разные категории:

- ошибка маппинга -> `400 Bad Request`
- ошибка валидации -> `422 Unprocessable Content`

К ошибкам маппинга относятся:

- отсутствует обязательное значение;
- payload нельзя привести к ожидаемому типу;
- передан некорректный enum value;
- DTO нельзя корректно собрать.

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
- без `Accept: application/json` вы получите HTML response с текстом ошибки

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

## Практические рекомендации

Обычно удобно идти так:

1. сначала заставить handler arguments корректно маппиться;
2. затем вынести request shape в DTO;
3. после этого добавить validation rules;
4. отдельно проверить, что API-клиент всегда отправляет `Accept: application/json`.

Так проще различать проблемы со сборкой аргументов и реальные validation failures.
