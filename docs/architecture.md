# Архитектура и runtime

## Зачем читать эту страницу

Эта страница даёт общую картину того, как устроен модуль целиком:

- какие зоны ответственности есть внутри `lib/`
- как проходит запрос через runtime
- где заканчивается reusable core и начинается Bitrix-specific glue
- какие ограничения важно знать до интеграции в проект

Если вы уже умеете регистрировать маршруты, но хотите понять внутреннее устройство модуля, начните отсюда.

## Карта модулей

```text
lib/
├── Routing/       # маршруты, match, группы, policy
├── Http/          # резолв аргументов, исключения, нормализация ответа
├── Guard/         # выбор, резолв и выполнение guards
├── Middleware/    # цепочка middleware вокруг handler
├── Validation/    # ошибки и валидация входных данных
├── Attribute/     # вспомогательные атрибуты
└── Interface/     # контракты middleware и guards

classes/           # Bitrix module-facing classes
install/           # установка модуля и поставляемые assets
services/          # service bootstrap scripts
admin/             # admin-side entrypoints
```

Практически это означает следующее:

- `lib/` — ядро, которое должно оставаться максимально переиспользуемым;
- `classes/`, `install/`, `services/`, `admin/` — внешняя интеграция с Bitrix runtime;
- документация и onboarding живут отдельно и не должны просачиваться в core-логику.

## Lifecycle запроса

Запрос проходит через модуль примерно так:

```text
HttpRequest
   |
   v
RouterRunner
   |
   v
Router::dispatch()
   |
   +--> match route
   +--> build request-scoped container
   +--> run guards
   +--> run middleware chain
   +--> invoke handler
   +--> normalize result to HttpResponse
   |
   v
ExceptionHandler
   |
   v
HTML or JSON response
```

## Основные runtime-классы

| Класс | Роль |
|------|------|
| `Router` | Регистрирует маршруты и координирует dispatch |
| `RouterRunner` | Берёт текущий `HttpApplication`, вызывает роутер и обрабатывает исключения |
| `RequestContainerFactory` | Собирает request-scoped DI-контейнер |
| `HandlerInvoker` | Вызывает handler после резолва аргументов |
| `ResponseNormalizer` | Приводит результат handler к `HttpResponse` |
| `ExceptionHandler` | Решает, вернуть HTML или JSON и как представить ошибку |

## Где проходит граница между core и Bitrix glue

У модуля есть несколько точек входа:

1. ручной bootstrap через `Router` + `RouterRunner`
2. компонент `oz:router.provider`
3. service endpoint `/bitrix/services/oz.api/`

Идея границы такая:

- entrypoint собирает окружение;
- core runtime в `lib/` делает всю основную работу;
- Bitrix-specific код не должен разрастаться внутри маршрутизации, validation, middleware или guards.

## Встроенные точки входа

### Ручной bootstrap

Самый прозрачный способ интеграции: вы сами создаёте `Router`, передаёте definitions и запускаете `RouterRunner`.

Это лучший вариант, если нужен нестандартный runtime layout или собственная сборка DI.

### `oz:router.provider`

Компонент:

1. подключает модуль;
2. получает `ROUTES_FILE_PATH`;
3. вычисляет `di.php` как `dirname(dirname(routesFile)) . '/di.php'`;
4. если файл найден, загружает definitions;
5. создаёт `Router`;
6. выполняет `RouterRunner` и завершает приложение.

Это удобно, но важно понимать, что здесь есть layout assumption про расположение `di.php`.

### `/bitrix/services/oz.api/`

Service entrypoint:

1. создаёт `Oz\Router\Module\Config`;
2. берёт из него путь к routes file;
3. пробрасывает этот путь в `oz:router.provider`.

Поэтому service runtime опирается на настройки модуля, но реальное подключение `di.php` по-прежнему делает provider по своей layout-логике.

## Ключевые caveats

### 1. Путь к DI-файлу в настройках не является полноправным runtime source of truth

`Module\Config` умеет хранить и путь к routes file, и путь к DI file. Но встроенный provider не использует сохранённый `configDIFilePath()` напрямую.

Фактическое поведение такое:

- routes path берётся из конфигурации;
- `di.php` угадывается относительно routes file;
- значит стабильнее всего использовать layout `config/routes/*.php` + `config/di.php`.

### 2. JSON-ошибки зависят от `Accept`

`ExceptionHandler` возвращает JSON не “по умолчанию для API”, а только если в `Accept` есть `application/json`.

Иначе даже при API-сценарии вы получите обычный HTML response с текстом ошибки.

Для интеграции это означает:

- API-клиентам нужно явно отправлять `Accept: application/json`;
- если этого не сделать, поведение ошибок может выглядеть неожиданно.

### 3. Core runtime лучше расширять через существующие модули, а не через shortcuts

Если нужна новая возможность:

- маршрутизация → расширяйте `Routing`
- проверка доступа → через guards
- cross-cutting обёртка → через middleware
- приведение результата → через `ResponseNormalizer`
- формат ошибок → через `ExceptionHandler`

Так меньше риск размазать логику по `Router` и entrypoint-скриптам.

## Когда какой entrypoint выбирать

| Сценарий | Что использовать |
|----------|------------------|
| Нужен полный контроль над bootstrap и DI | ручной `Router` + `RouterRunner` |
| Нужна интеграция через Bitrix component | `oz:router.provider` |
| Нужен встроенный service endpoint | `/bitrix/services/oz.api/` |
