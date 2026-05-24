# API Documentation

Энэхүү баримт бичиг нь `codesaur/router` пакетийн бүх public API-г дэлгэрэнгүй тайлбарлана.

---

## Table of Contents

- [RouterInterface](#routerinterface)
- [Router](#router)
- [Route](#route)

---

## RouterInterface

**Namespace:** `codesaur\Router`

codesaur ecosystem-ийн router-ийн **бүрэн contract**. 4 method-ийг шаардана:
- `match()` - request matching
- `generate()` - reverse routing (route name -> URL)
- `pattern()` - client-side substitution pattern
- `getRoutes()` - introspection

Үндсэн зорилго:
- HTTP application болон Raptor controller-уудад router-ийн **хэрэгжилтээс үл хамаарах boundary** болох
- Гадны router (FastRoute, Symfony, AltoRouter гэх мэт)-ийг adapter-аар ороож ашиглах боломж олгох
- Concrete Router class-ын internal API-уудыг (`registerName()`, `registerMiddleware()`) interface-аас гадуур үлдээх

### Methods

#### `match(string $path, string $method): ?array`

Орж ирсэн URL path болон HTTP method-д тохирох маршрутыг хайна.

**Parameters:**
- `string $path` - Хайлтын URL path (`/news/123` гэх мэт)
- `string $method` - HTTP method (GET, POST, PUT, DELETE, PATCH...)

**Returns:** `array|null`

Таарах route олдвол **тогтмол 3 элементтэй tuple array** буцаана:
- `[0]` - гүйцэтгэх callable (Closure эсвэл `[Class, 'method']`)
- `[1]` - pattern-аас гаргаж авсан параметрүүд (`['id' => 10]` гэх мэт)
- `[2]` - middleware жагсаалт (хоосон бол `[]`)

Олдоогүй бол `null` буцаана.

**Example - энгийн хэрэглээ:**
```php
$result = $router->match('/news/10', 'GET');
if ($result === null) {
    http_response_code(404);
    return;
}

[$callable, $params, $middleware] = $result;
call_user_func_array($callable, $params);
```

**Example - middleware-тэй custom router:**
```php
class MyRouter implements RouterInterface
{
    public function match(string $path, string $method): ?array
    {
        return [
            $callable,                                     // [0] callable
            ['id' => 10],                                  // [1] params
            [AuthMiddleware::class, RBACMiddleware::class] // [2] middleware
        ];
    }
}
```

> **`codesaur/http-application`-тай интеграц:**
> HTTP-Application нь match() үр дүнг бүхэлд нь request-ийн `match` attribute болгож дамжуулна. Шаардлагатай нэмэлт мэдээллийг middleware-аас request attribute-аар дамжуулна.

---

#### `generate(string $routeName, array $params = []): string`

Reverse routing - route name дээр үндэслэн URL үүсгэнэ.

**Returns:** `string` - Үүсгэсэн URL path (raw, encode хийгдээгүй)

**Throws:**
- `\OutOfRangeException` - Route name олдохгүй бол
- `\InvalidArgumentException` - Параметрийн төрөл буруу бол

NOTE: **Encoding contract:** parameter утгуудыг **түүхий** substitution хийнэ - percent-encoding хийдэггүй. PSR-7 `UriInterface::withPath()`-тай double-encoding-аас зайлсхийх contract.

**Example:**
```php
$url = $router->generate('news-view', ['id' => 10]);  // '/news/10'
```

---

#### `pattern(string $routeName): string`

Client-side substitution-д бэлэн URL pattern буцаана. Filter prefix-уудыг (`int:`, `uint:`, ...) хасч цэвэр placeholder pattern өгнө.

**Returns:** `string` - Filter prefix хасагдсан pattern

**Throws:** `\OutOfRangeException` - Route name олдохгүй бол

**Example:**
```php
$pattern = $router->pattern('news-view');  // '/news/{id}/{slug}'
```

---

#### `getRoutes(): array`

Бүртгэлтэй бүх маршрутын жагсаалт (introspection-д зориулагдсан).

**Бүтэц - (pattern, method) бүрд 2-tuple `[callable, middleware]`:**
```
[
    pattern => [
        method => [
            [0] callable,    // гүйцэтгэх
            [1] middleware,  // route-д бүртгэсэн middleware жагсаалт
        ]
    ]
]
```

Pattern нь outer key учраас params мэдээллийг агуулна - entry дотор params placeholder шаардлагагүй. Match-time-ийн params нь зөвхөн `match()` дотор тооцоологдоно.

**Хэрэглээний жишээ:**
- Admin panel-д бүх routes-ыг жагсаах
- Sitemap auto-generate хийх
- API documentation auto-generate хийх

**Example:**
```php
foreach ($router->getRoutes() as $pattern => $methods) {
    foreach ($methods as $method => [$callable, $middleware]) {
        echo "$method $pattern\n";
    }
}
```

**Route name мэдээлэл:** Naming нь тусдаа concern - `getRoutes()`-д орохгүй. Name хэрэгтэй бол `generate($name)`-ээр URL үүсгэх, эсвэл `pattern($name)`-аар client-side pattern авах.

---

## Router

**Namespace:** `codesaur\Router`

codesaur ecosystem-ийн хөнгөн жинтэй маршрутчиллын үндсэн Router класс.

**Implements:** `RouterInterface`

### Description

Энэхүү Router нь дараах үйлдлүүдийг гүйцэтгэнэ:
- Маршрут бүртгэх (динамик `__call` ашиглан: `$router->GET('/news', ...)` хэлбэрээр)
- `{int:id}`, `{float:price}`, `{uint:page}`, `{slug}`, `{utf8:text}` гэх мэт параметртэй маршрут боловсруулах
- Request path болон HTTP method-д тохирох маршрутыг `match()` ашиглан олох
- Route name -> URL generate хийх (reverse routing)
- Per-route middleware дэмжих (`Route::middleware()`)

Жижиг, тогтвортой, фрэймворкоос үл хамааран standalone байдлаар ашиглаж болно.

### Constants

| Нэр | Утга | Зориулалт |
|---|---|---|
| `FILTERS_REGEX` | `'/\{(int:|uint:|float:|utf8:)?(\w+)}/'` | `{param}`, `{int:id}` гэх мэт бүх параметрийг илрүүлэх |
| `INT_REGEX` | `'(-?\d+)'` | Сөрөг ба эерэг бүхэл тоо |
| `UNSIGNED_INT_REGEX` | `'(\d+)'` | Зөвхөн эерэг бүхэл тоо (0 ба түүнээс дээш) |
| `FLOAT_REGEX` | `'(-?\d+|-?\d*\.\d+)'` | Сөрөг ба эерэг бутархай тоо |
| `DEFAULT_REGEX` | `'([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@\-]+)'` | URL-safe тэмдэгтүүд (хоосон зайгүй) |
| `UTF8_REGEX` | `'([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@ \x80-\xFF\-]+)'` | UTF-8 multibyte (Кирилл, CJK гэх мэт) + хоосон зай |

### Methods

#### `__call(string $method, array $properties): Route`

Магик метод - `GET`, `POST`, `PUT`, `DELETE` гэх мэт маршрут бүртгэнэ.

**Parameters:**
- `string $method` - HTTP method нэр (`GET`, `POST`, ..., `GET_POST`-маягийн олон method ч боломжтой)
- `array<mixed> $properties` - 
 - `[0]` => route pattern (string)
 - `[1]` => callable (Closure эсвэл `[Class, 'method']`)

**Returns:** `Route` - Бүртгэсэн route-ыг илэрхийлэх immutable Route объект. `->name(...)` гэх мэт fluent API-д ашиглагдана.

**Throws:**
- `\InvalidArgumentException` - pattern эсвэл callback хоосон/буруу байвал

**Example:**
```php
$router->GET('/news/{int:id}', [NewsController::class, 'view'])->name('news-view');
$router->POST('/users', function() { ... });
$router->GET_POST('/api/data', [ApiController::class, 'data']);  // олон method
```

---

#### `registerName(string $ruleName, string $pattern): void`

Route name -> pattern бүртгэх **internal API**. Хэвлэг хэрэглээнд `$router->GET(...)->name(...)` chain ашиглах нь зөв (Route::name()-аас автоматаар дуудагдана).

**Parameters:**
- `string $ruleName` - Маршрутын нэр (уникаль байх ёстой)
- `string $pattern` - Маршрутын pattern

**Notes:**
- Хэвийн хэрэглээ нь `Route::name()`-аар явдаг
- Шууд дуудах тохиолдол: post-hoc нэр оноох (route бүртгэсний дараа)
- Ижил нэртэй бол шинэ pattern-аар дарж бичнэ

**Example:**
```php
// Стандарт хэрэглээ (Route::name()-аар автоматаар):
$router->GET('/news/{int:id}', ...)->name('news-view');

// Post-hoc нэр оноох:
$router->GET('/foo', $handler);
$router->registerName('foo', '/foo');
```

---

#### `registerMiddleware(string $pattern, string $method, array $middleware): void`

(pattern, method) route-д middleware жагсаалт хавсаргах **internal API**. Хэвлэг хэрэглээнд `Route::middleware([...])` chain ашиглах нь зөв.

**Parameters:**
- `string $pattern` - Маршрутын pattern
- `string $method` - HTTP method (compound `'GET_POST'` дэмжинэ)
- `list<class-string|callable|\Psr\Http\Server\MiddlewareInterface> $middleware`

**Notes:**
- Scope нь **(pattern, method)** хосоор хязгаарлагдана - өөр method дээрх middleware-уудаас тусгаарлагдсан
- Compound method (`GET_POST`) ашигласан бол дотоод method бүрд middleware хувилагдана
- Append semantics - хэд хэдэн удаа дуудвал middleware-ууд цуглуулагдана
- match()-ийн tuple-д `[2]` position-оор буцаагдана
- Хэвлэг flow: `Route::middleware([...])` -> автоматаар registerMiddleware() дуудна
- Шууд дуудах тохиолдол: base class-ийн auto-attach, post-hoc нэмэлт

**Example:**
```php
// Стандарт flow:
$router->POST('/api/users', $handler)
    ->middleware([CsrfMiddleware::class]);

// Post-hoc:
$router->GET('/foo', $handler);
$router->registerMiddleware('/foo', 'GET', [AuthMiddleware::class]);
```

---

#### `match(string $path, string $method): ?array`

`RouterInterface::match()`-ийн хэрэгжилт. Tuple shape, return contract зэрэг ерөнхий зүйлийг RouterInterface::match section-аас үзнэ үү.

**codesaur Router-ийн хэрэгжилтийн нэмэлт онцлог:**
- **HEAD -> GET авто fallback** (RFC 7231 sec. 4.3.2):
  Explicit HEAD route байхгүй бол HEAD request автоматаар GET handler руу очно.
  Consumer нь HEAD response-ийн body-г цэвэрлэх ёстой.

---

#### `generate(string $ruleName, array $params = []): string`

`RouterInterface::generate()`-ийн хэрэгжилт. Дэлгэрэнгүйг RouterInterface::generate section-аас үзнэ үү.

---

#### `pattern(string $ruleName): string`

`RouterInterface::pattern()`-ийн хэрэгжилт. Дэлгэрэнгүйг RouterInterface::pattern section-аас үзнэ үү.

**Template + JS хэрэглээний жишээ:**
```html
<script>
const URL = '<?= $router->pattern('news-view') ?>';
fetch(URL.replace('{id}', 42).replace('{slug}', 'hello'));
</script>
```

---

## Route

**Namespace:** `codesaur\Router`

`Router::__call()` метод route бүртгэх үед буцаах **immutable value object**. `->name(...)` гэх мэт fluent API-г олгоно.

### Description

Route class нь Router-г **stateless** байлгахын тулд оршино:
- Бүртгэсэн route нь өөрийн pattern context-ыг агуулсан тул `name()`, `middleware()` зэрэг fluent method-ууд зөрчилгүйгээр ажиллана
- PHP 8.2 `readonly` class - immutable

### Properties

#### `public readonly string $pattern`

Энэ route-ын pattern (`/news/{int:id}` гэх мэт). Уншигдах боломжтой, өөрчлөгдөхгүй.

### Methods

#### `name(string $ruleName): self`

Энэ маршрутад нэр оноох.

**Parameters:**
- `string $ruleName` - Route name (уникаль байх ёстой)

**Returns:** `self` - Энэ Route объектыг буцаана (chaining-д)

**Notes:**
- Strict mode - ижил name өөр pattern-д оноох гэвэл `\LogicException` шиднэ
- Дотооддоо `Router::registerName()`-г дуудна

**Example:**
```php
$router->GET('/news/{int:id}', $handler)->name('news.view');
```

#### `middleware(array $middleware): self`

Энэ маршрутад **middleware жагсаалт хавсаргах**. Тухайн route ажиллахаас өмнө эдгээр middleware дуудагдана.

**Parameters:**
- `list<class-string|callable|\Psr\Http\Server\MiddlewareInterface> $middleware` - Middleware жагсаалт

**Returns:** `self` - Энэ Route объектыг буцаана (chaining-д)

**Middleware-ийн төрлүүд:**
- **class-string** - PSR-15 MiddlewareInterface хэрэгжүүлсэн класс (HTTP-Application instance үүсгэнэ)
- **callable / Closure** - `function($request, $handler)` гарын үсэг
- **MiddlewareInterface instance** - урьдчилан үүсгэсэн object

**Notes:**
- Append semantics - олон удаа дуудвал middleware-ууд цуглуулагдана
- Дотооддоо `Router::registerMiddleware()`-г дуудна
- match()-ийн tuple-д `[2]` position-оор буцаагдана

**Example:**
```php
// Энгийн хэрэглээ
$router->POST('/api/users', $handler)
    ->middleware([CsrfMiddleware::class, RBACMiddleware::class]);

// Append semantics
$router->GET('/admin', $handler)
    ->middleware([AuthMiddleware::class])
    ->middleware([AdminOnlyMiddleware::class]);
// Бүртгэгдсэн: [Auth, AdminOnly]

// Closure middleware
$router->GET('/test', $handler)
    ->middleware([
        function($req, $handler) {
            return $handler->handle($req->withAttribute('test', true));
        }
    ]);
```
