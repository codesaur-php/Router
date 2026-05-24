# codesaur/router  

Хөнгөн, хурдан, объект-суурьтай маршрутчиллын (routing) компонент

`codesaur/router` нь **codesaur ecosystem**-ийн нэг хэсэг боловч бие даасан байдлаар ашиглах боломжтой, жижиг хэмжээтэй боловч маш уян хатан Router компонент юм.

Онцлог:
- Хурдан: dynamic parameter matching + regex filtering
- Олон төрлийн параметр: `{int:id}`, `{uint:page}`, `{float:price}`, `{slug}`, `{utf8:text}`
- Route name -> URL generate (reverse routing)
- Controller болон Closure callback дэмжинэ
- Per-route middleware
- Standalone ашиглаж болно (framework шаардлагагүй)

---

## Installation

### Шаардлага

- PHP 8.2.1 эсвэл дээш хувилбар
- Composer

### Composer ашиглан суулгах

```bash
composer require codesaur/router
```

### Autoload ашиглах

Composer autoload-ийг ашиглах:

```php
require 'vendor/autoload.php';

use codesaur\Router\Router;

$router = new Router();
// ...
```

### Шууд ашиглах (standalone)

Хэрэв Composer ашиглахгүй бол файлуудыг шууд татаж авч ашиглаж болно:

```php
require_once 'src/RouterInterface.php';
require_once 'src/Route.php';
require_once 'src/Router.php';

use codesaur\Router\Router;
// ...
```

---

## Quick Start

### Энгийн маршрут

```php
use codesaur\Router\Router;

$router = new Router();

// GET маршрут бүртгэх
$router->GET('/hello/{firstname}', function ($firstname) {
    echo "Hello $firstname!";
});

// Маршрут тааруулах
// match() буцаах: [callable, params, middleware] тогтмол 3-tuple эсвэл null
$result = $router->match('/hello/Narankhuu', 'GET');

if ($result !== null) {
    [$callable, $params, $middleware] = $result;
    call_user_func_array($callable, $params);
}
```

**Request:**
```http
GET /hello/Narankhuu
```

**Output:**
```text
Hello Narankhuu!
```

### Controller ашиглах

```php
class UserController {
    public function show(int $id) {
        echo "User ID: $id";
    }
}

$router->GET('/user/{int:id}', [UserController::class, 'show'])
    ->name('user.show');
```

---

## Dynamic Route Parameters

Маршрутын параметрүүдийг төрөлтэй хамт тодорхойлж болно:

| Төрөл | Жишээ | Тайлбар |
|------|--------|----------|
| `{int:id}` | `/post/{int:id}` | Сөрөг тоо зөвшөөрнө |
| `{uint:page}` | `/users/{uint:page}` | Зөвхөн эерэг бүхэл тоо |
| `{float:num}` | `/price/{float:num}` | 1.4, -2.56 гэх мэт |
| `{word}` | `/tag/{word}` | A-z0-9 болон URL-safe тэмдэгтүүд |
| `{utf8:text}` | `/search/{utf8:query}` | UTF-8 multibyte тэмдэгтүүд (Кирилл, Хятад, Араб гэх мэт) |

Жишээ:

```php
$router->GET('/sum/{int:a}/{uint:b}', function (int $a, int $b) {
    echo "$a + $b = " . ($a + $b);
});
```

---

## Named Routes & URL Generation

Route-д нэр өгнө:

```php
$router->GET('/profile/{int:id}', 'UserController@view')->name('profile');
```

URL generate хийх:

```php
$url = $router->generate('profile', ['id' => 25]);
// -> /profile/25
```

Буруу төрлийн параметр дамжуулбал:

```php
$router->generate('profile', ['id' => 'abc']);
```

Үр дүн -> `InvalidArgumentException`

### Route value object - `$router->GET(...)->name(...)`

`Router::__call()` нь route бүртгэх үед **immutable `Route` value object** буцаадаг. Fluent `->name(...)` API нь буцсан object дээр шууд ажилладаг тул Router-ийн дотоод state-аас үл хамаарна.

```php
$route = $router->GET('/news/{int:id}', $handler);
// $route нь Route instance, $route->pattern = '/news/{int:id}'

$router->GET('/about', $handler)->name('about');
// chain ажиллах нь Route::name() буцаах нь Route өөрөө байгаа учраас
```

**Post-hoc registration** - route бүртгэсний дараа нэр оноох:

```php
$router->GET('/foo', $handler);
$router->registerName('foo', '/foo');
```

---

### Route::middleware() - Per-route middleware

Тухайн route-ыг ажиллуулахын өмнө дуудах **middleware жагсаалтыг** route-д наах:

```php
$router->POST('/api/users', [UserController::class, 'create'])
    ->middleware([
        AuthMiddleware::class,
        CsrfMiddleware::class,
        RBACPermissionMiddleware::class,
    ]);
```

**Scope нь (pattern, method) хосоор хязгаарлагдана** - middleware нь зөвхөн бүртгэсэн method-д ажиллана. Express, Laravel, Slim зэрэг mainstream router-уудтай нийцэж байна:

```php
$router->GET('/api/users', $list);                                 // public read
$router->POST('/api/users', $create)->middleware([Auth::class]);   // protected write

$router->match('/api/users', 'GET');   // [2] -> []
$router->match('/api/users', 'POST');  // [2] -> [Auth::class]
```

**Compound method (`GET_POST`)** - middleware нь дотоод method бүрд хувилагдан хэрэгжинэ:

```php
$router->GET_POST('/foo', $handler)->middleware([Auth::class]);
// GET /foo  -> [Auth::class]
// POST /foo -> [Auth::class]
```

**Append semantics** - олон `->middleware()` chain дуудвал middleware-ууд цуглуулагдана:

```php
$router->GET('/admin', $handler)
    ->middleware([AuthMiddleware::class])
    ->middleware([AdminOnlyMiddleware::class, RateLimitMiddleware::class]);
// Бүртгэгдсэн: [Auth, AdminOnly, RateLimit]
```

**Middleware-ийн төрлүүд:**
- `class-string` - PSR-15 MiddlewareInterface хэрэгжүүлсэн класс (HTTP-Application instance үүсгэнэ)
- `callable / Closure` - `function($request, $handler)` бичлэг
- `MiddlewareInterface instance` - урьдчилан үүсгэсэн object

**match()-ээс middleware унших:**

```php
$result = $router->match('/api/users', 'POST');
// $result === [
//     [UserController::class, 'create'],      // [0] callable
//     [],                                     // [1] params
//     [Auth::class, Csrf::class, RBAC::class] // [2] middleware
// ]

[$callable, $params, $middleware] = $result;
```

**`codesaur/http-application`-тай интеграц:**
HTTP-Application нь match() үр дүнгээс middleware-уудыг автоматаар pipeline-руу нэмж execute хийнэ. Дэлгэрэнгүй http-application-ийн docs-аас үзнэ үү.

---

### Inheritance-аар автомат middleware (хэрэглэгчийн зүгээс бичих жишээ pattern)

> **Тэмдэглэл:** Доорх `AuthenticatedRouter` нь codesaur/router пакетийн нэг хэсэг **биш** - зүгээр л хэрэглэгчийн өөрийн application дотор бичих нэрлэсэн жишээ class юм. Та өөрийн төсөлдөө ийм base class бичиж, нэрийг нь дур мэдэн өгч болно (жишээ нь `AdminRouter`, `ApiRouter` гэх мэт).

Олон route-д ижил middleware шаардлагатай бол **өөрийн base class дотроос автоматаар наах** боломж:

```php
// Жишээ - өөрийн application дотор бичих base class
abstract class AuthenticatedRouter extends Router
{
    /** @var list<class-string> */
    protected array $autoMiddleware = [
        AuthMiddleware::class,
        CsrfMiddleware::class,
    ];

    public function __call(string $method, array $properties): Route
    {
        return parent::__call($method, $properties)->middleware($this->autoMiddleware);
    }
}

// Хэрэглээ:
class UsersRouter extends AuthenticatedRouter
{
    public function __construct()
    {
        $this->GET('/users', [...]);       // Auth + Csrf автоматаар наагдсан
        $this->POST('/users', [...]);
        $this->DELETE('/users/{int:id}', [...])
            ->middleware([AdminOnlyMiddleware::class]);  // нэмэлт
    }
}
```

Энэ pattern нь route-уудыг ангилж, нийтлэг middleware-уудыг **inheritance-аар тарааж өгөх** боломжтой - Laravel-ийн route group analog ч илүү цэвэр.

---

### Client-side URL Pattern

Параметрийн утга нь зөвхөн client дээр мэдэгдэх (жишээ нь, fetch хийсэн жагсаалтаас сонгосон row id) динамик UI-д `pattern()` ашиглан JavaScript-д орлуулахад бэлэн placeholder pattern-ыг буцаана:

```php
$pattern = $router->pattern('profile'); // -> /profile/{id}
```

Filter prefix-үүд (`int:`, `uint:`, `float:`, `utf8:`) хасагдаж, зөвхөн параметрийн нэр үлдэнэ. Static хэсгүүд хэвээр хадгалагдана.

#### Template engine-д холбох

`pattern()` нь Router instance-н энгийн PHP method. Доор үзүүлсэн `{{ "route-name"|pattern }}` shorthand хэрэглэхийн тулд та өөрийн template engine-д filter/function болгож **бүртгэсэн байх ёстой** - Router package өөрөө template integration агуулдаггүй.

Жишээ: [`codesaur/template`](https://github.com/codesaur-php/Template) -д filter бүртгэх:

```php
$template->addFilter('pattern', fn(string $name) => $router->pattern($name));
```

Filter бүртгэгдсэний дараа template-д pattern-ийг гаргаж, JS дээр утгыг орлуулна:

```html
<script>
const URL_PATTERN = '{{ "profile"|pattern }}';
fetch(URL_PATTERN.replace('{id}', selectedId));
</script>
```

Filter бүртгэхийг хүсэхгүй бол PHP-аар шууд дуудаж болно:

```html
<script>
const URL_PATTERN = '<?= $router->pattern('profile') ?>';
fetch(URL_PATTERN.replace('{id}', selectedId));
</script>
```

| Метод | Хэзээ ашиглах |
|-------|---------------|
| `generate($name, $params)` | Утга мэдэгдсэн server-side URL - validation хийнэ, буруу төрлийг татгалзана |
| `pattern($name)` | JS-д утга орлуулах client-side template - validation хийхгүй |

---

## Matching & Dispatching

`match()` нь **үргэлж тогтмол 3 элементтэй tuple array** буцаана (route олдоогүй бол `null`):

| Position | Утга | Төрөл |
|---|---|---|
| `[0]` | callable | Closure эсвэл `[Class, 'method']` |
| `[1]` | params | `array<string, mixed>` - pattern-аас гаргасан параметрүүд |
| `[2]` | middleware | `list<class-string\|callable\|MiddlewareInterface>` - хоосон `[]` ч байж болно |

**Concrete contract-ийн давуу тал:**
- Бүх 3 position заавал байна - middleware байхгүй route-д ч `[2]` нь хоосон `[]`
- Positional access (`$result[2]`) нь хамгийн хурдан - hash lookup байхгүй
- Шууд destructuring: `[$callable, $params, $middleware] = $result;`
- Consumer-д `?? []` check шаардлагагүй

### Орж ирсэн request-ийг боловсруулах

```php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$result = $router->match($path, $method);

if ($result === null) {
    http_response_code(404);
    exit;
}

[$callable, $params, $middleware] = $result;

if ($callable instanceof \Closure) {
    call_user_func_array($callable, $params);
} else {
    // Controller method - [Class, 'method'] хэлбэр
    [$class, $action] = $callable;
    $controller = new $class();
    call_user_func_array([$controller, $action], $params);
}
```

### Жишээ - Custom router middleware дамжуулах

```php
use codesaur\Router\RouterInterface;

class MyRouter implements RouterInterface
{
    public function match(string $path, string $method): ?array
    {
        // ... matching логик ...

        return [
            $callable,                                     // [0] callable
            ['id' => 10],                                  // [1] params
            [AuthMiddleware::class, RBACMiddleware::class] // [2] middleware
        ];
    }
}
```

### HTTP-Application талд хэрэглэх

`codesaur/http-application` нь match() үр дүнг бүхэлд нь request-ийн `match` attribute болгож дамжуулна. Шаардлагатай нэмэлт мэдээлэл байвал middleware-ээс request attribute-аар дамжуулна:

```php
class SomeMiddleware implements MiddlewareInterface
{
    public function process($request, $handler): ResponseInterface
    {
        $match = $request->getAttribute('match');  // бүхэл tuple
        [$callable, $params, $middleware] = $match;

        // Аливаа custom logic ...
        return $handler->handle($request);
    }
}
```

---

## Example Project

`example/index.php` файл нь бүх функцүүдийг нэг дор харуулна:

- GET/POST маршрут бүртгэх  
- Controller класстай ажиллах  
- Параметрийн төрөл шалгах (int, uint, float, string)  
- URL generate тест (reverse routing)
- Per-route middleware demo (Logging, Auth, Timing 3 жишээ middleware + onion model pipeline)
- Гүйцэтгэл тест (Performance Test - 10,000 удаа)
- Автомат base-path support
- Монгол үсэг дэмжлэг

Жишээ файлыг ажиллуулах:
```bash
php -S localhost:8000 -t example
# Дараа нь browser дээр: http://localhost:8000
```  

---

## HEAD -> GET авто fallback (RFC 7231 sec. 4.3.2)

HTTP HEAD method нь GET-тэй яг адил - ялгаа нь зөвхөн response body буцаахгүй (зөвхөн headers). Browser cache validation (`ETag`/`Last-Modified`), link checker, monitoring tool-ууд HEAD ашигладаг.

codesaur Router нь HEAD request-д ирэхэд **автоматаар GET handler руу очдог**:

```php
// Зөвхөн GET бүртгэсэн ч HEAD ажиллана
$router->GET('/news/{int:id}', [NewsController::class, 'view']);

$result = $router->match('/news/10', 'HEAD');  // GET handler буцаана
```

### Explicit HEAD route нь давуу талтай

Хэрэв HEAD-д тусгай үйлдэл хэрэгтэй бол explicit HEAD route бүртгэж болно - энэ нь GET fallback-аас илүү давуу талтай:

```php
$router->GET('/api/items', $getHandler);
$router->HEAD('/api/items', $headHandler);  // <- энэ нь HEAD request-д таарна

$result = $router->match('/api/items', 'HEAD');  // $headHandler-ыг буцаана
```

### NOTE: Consumer тал хариуцлага

Router нь зөвхөн route таарагдах асуудлыг шийднэ. **Response body цэвэрлэх нь consumer-ийн (HTTP-Application эсвэл өөрийн dispatch код) хариуцлага**:

```php
$result = $router->match($path, $method);
if ($result !== null) {
    [$callable, $params] = $result;
    \call_user_func_array($callable, $params);

    // HEAD response-д body байх ёсгүй
    if ($method === 'HEAD') {
        // output buffer-ыг цэвэрлэх (PHP-ийн ob_clean) эсвэл
        // PSR-7 Response-ийн body-г хоосон stream болгож сольж байх
    }
}
```

### Бусад method-аас fallback байхгүй

HEAD нь зөвхөн **GET**-ээс fallback хийгдэнэ. POST/PUT/DELETE-аас огт fallback байхгүй:

```php
$router->POST('/data', $handler);

$router->match('/data', 'HEAD');  // -> null (taarna ugui)
```

**Анхаарах зүйл:**
- Route name-ууд мөн нэгтгэгдэнэ
- Хэрэв ижил нэртэй route байвал эхний router-ийнх нь давуу тал болно

---

## CI/CD

Энэ проект нь GitHub Actions ашиглан автоматаар CI/CD хийгддэг:

- Олон PHP хувилбарууд дээр тест (8.2, 8.3, 8.4)
- Ubuntu болон Windows дээр тест
- Composer dependencies суулгах
- PHPUnit тестүүд ажиллуулах
- Code coverage хэмжих

CI/CD workflow нь `main`, `master`, `develop` салбарууд дээр push эсвэл pull request хийхэд автоматаар ажиллана.

---

## Documentation

Энэ пакетийн дэлгэрэнгүй баримт бичгүүд:

- **[API](api.md)** - Бүх public API-ийн дэлгэрэнгүй тайлбар, method-ууд, parameter-ууд, exception-ууд (PHPDoc-уудаас Cursor AI ашиглан автоматаар үүсгэсэн)
- **[REVIEW](review.md)** - Код шалгалтын тайлан, давуу талууд, сайжруулах боломжууд  (Cursor AI ашиглан үүсгэсэн)
- **[CHANGELOG](../../CHANGELOG.md)** - Пакетийн бүх хувилбаруудын өөрчлөлтийн түүх

---

## Running Tests

Энэ проект нь PHPUnit ашиглан unit test-үүд агуулдаг (нийт **71 тест, 161 assertion** - `RouterTest` ба `AdapterPatternTest`).

### Dependencies суулгах

```bash
composer install
```

### Тест ажиллуулах

#### Composer Script ашиглах

```bash
composer test              # Бүх тестүүдийг ажиллуулах
composer test:coverage     # Coverage-тэй тест ажиллуулах
```

#### PHPUnit-ийг шууд ажиллуулах

```bash
vendor/bin/phpunit                                    # Бүх тестүүдийг ажиллуулах
vendor/bin/phpunit tests/RouterTest.php              # Тодорхой тест файл ажиллуулах
vendor/bin/phpunit --coverage-text                   # Test coverage харах
vendor/bin/phpunit --filter testMatch tests/RouterTest.php  # Тодорхой method ажиллуулах
```

**Windows хэрэглэгчид:** `vendor/bin/phpunit`-ийг `vendor\bin\phpunit.bat`-аар солино уу

---

## Лиценз

Энэ төсөл MIT лицензтэй.

---

## Зохиогч

Narankhuu  
https://github.com/codesaur  
