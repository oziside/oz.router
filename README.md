# oz.router


> [!WARNING]
> Этот проект находится в активной разработке. Между релизами могут происходить критические изменения.



> Роутер для Bitrix D7 с PHP-DI, middleware, гидратацией аргументов и автоматическими JSON-ответами.

`oz.router` сопоставляет текущий `HttpRequest` с маршрутом по методу и пути, резолвит обработчик через DI-контейнер, валидирует входные аргументы и возвращает `HttpResponse`.

## Быстрый старт

Модуль зависит от пакетов из `composer.json`. В этом проекте они устанавливаются через `local/composer.json` в `/bitrix/vendor`, а автозагрузка подключается в `local/php_interface/init.php`.

```php
<?php

use Bitrix\Main\Loader;
use Oz\Router\RouterRunner;
use Oz\Router\Router;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loader::includeModule('oz.router');

$router = new Router();

$router->get('/ping', static function (): array {
    return ['status' => 'ok'];
});

$response = (new RouterRunner($router))->run();
$response->send();
```

## Возможности

- Маршруты по HTTP-методу и пути, включая динамические сегменты и regex-ограничения
- Обработчики в форматах `Class@method`, `[ClassName::class, 'method']` и любой `callable`
- Request-scoped PHP-DI контейнер с `HttpApplication`, `HttpContext`, `HttpRequest` и `route_params`
- Резолв scalar-типов, enum, DTO-объектов и сервисов в аргументы обработчика
- Middleware на глобальном уровне, в группах и на отдельных маршрутах
- RFC 7807 `application/problem+json` для API-ошибок роутера, валидации и пользовательских problem-исключений
- Генерация OpenAPI-схемы по аннотациям и просмотр в админке Bitrix

## Точки входа

- Ручной запуск: создать `Router`, зарегистрировать маршруты и выполнить `RouterRunner`
- Компонент Bitrix: `oz:router.provider`
- Сервис Bitrix: `/bitrix/services/oz.api/`, который проксирует в `services/api/index.php`

## Практический пример

В репозитории есть рабочий пример модуля `oz.router.sample`. Он показывает не абстрактный "hello world", а реальный сценарий:

- versioned API через группы `/api/v1`
- routes в `config/routes/api.php`
- DI-определения в `config/di.php`
- CRUD-контроллер с use case-хендлерами
- request DTO на `Bitrix\Main\Validation\Rule`
- response DTO с `#[JsonResource]`
- OpenAPI-атрибуты на контроллере и DTO
- middleware для авторизации и постобработки JSON-ответа

## Документация

| Раздел | Описание |
|--------|----------|
| [Установка и запуск](docs/getting-started.md) | Требования, установка, первый маршрут, компонент и сервисный вход |
| [Маршрутизация](docs/routing.md) | Регистрация маршрутов, форматы обработчиков, резолв аргументов и ответы |
| [Валидация](docs/validation.md) | Валидация запроса, гидратация DTO и формат ответа `422` |
| [Middleware](docs/middleware.md) | Интерфейс, порядок выполнения, подключение и исключение middleware |
| [Настройки](docs/configuration.md) | Опции модуля, runtime-поведение и нюансы OpenAPI/Swagger |

## Важно

- Встроенный Swagger UI читает JSON-схему, поэтому для вкладки `Swagger` безопаснее указывать файл `.json`
- Сохранённый путь к DI-конфигу доступен через `Oz\Router\Module\Config`, но встроенные точки входа не подключают его автоматически
- OpenAPI-аннотации не влияют на runtime-роутинг и HTTP-статусы: это видно в `oz.router.sample`, где документация и фактическое поведение нужно держать синхронно вручную

## Лицензия

Лицензия не указана.
