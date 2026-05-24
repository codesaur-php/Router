# codesaur/router  

Lightweight, fast, object-oriented routing component

`codesaur/router` is part of the **codesaur ecosystem** but can be used independently as a small yet very flexible Router component.

**Features:**
- Fast: dynamic parameter matching + regex filtering
- Multiple parameter types: `{int:id}`, `{uint:page}`, `{float:price}`, `{slug}`, `{utf8:text}`
- Route name -> URL generation (reverse routing)
- Supports Controller and Closure callbacks
- Per-route middleware
- Can be used standalone (no framework required)

---

## Installation

### Requirements

- PHP 8.2.1 or higher
- Composer

### Install via Composer

```bash
composer require codesaur/router
```

### Using Autoload

Use Composer autoload:

```php
require 'vendor/autoload.php';

use codesaur\Router\Router;

$router = new Router();
// ...
```

### Direct Usage (standalone)

If you don't use Composer, you can download the files directly and use them:

```php
require_once 'src/RouterInterface.php';
require_once 'src/Route.php';
require_once 'src/Router.php';

use codesaur\Router\Router;
// ...
```

---

## Quick Start

### Simple Route

```php
use codesaur\Router\Router;

$router = new Router();

// Register GET route
$router->GET('/hello/{firstname}', function ($firstname) {
    echo "Hello $firstname!";
});

// Match route
// match() returns: [callable, params, middleware] fixed 3-tuple OR null
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

### Using Controller

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

Route parameters can be defined with types:

| Type | Example | Description |
|------|--------|----------|
| `{int:id}` | `/post/{int:id}` | Allows negative numbers |
| `{uint:page}` | `/users/{uint:page}` | Only positive integers |
| `{float:num}` | `/price/{float:num}` | 1.4, -2.56, etc. |
| `{word}` | `/tag/{word}` | A-z0-9 and URL-safe characters |
| `{utf8:text}` | `/search/{utf8:query}` | UTF-8 multibyte characters (Cyrillic, CJK, Arabic, etc.) |

Example:

```php
$router->GET('/sum/{int:a}/{uint:b}', function (int $a, int $b) {
    echo "$a + $b = " . ($a + $b);
});
```

---

## Named Routes & URL Generation

Name a route:

```php
$router->GET('/profile/{int:id}', 'UserController@view')->name('profile');
```

Generate URL:

```php
$url = $router->generate('profile', ['id' => 25]);
// -> /profile/25
```

If wrong parameter type is passed:

```php
$router->generate('profile', ['id' => 'abc']);
```

Result -> `InvalidArgumentException`

### Route value object - `$router->GET(...)->name(...)`

`Router::__call()` returns an **immutable `Route` value object** when registering a route. The fluent `->name(...)` API operates on the returned object directly, so it does not depend on hidden Router state.

```php
$route = $router->GET('/news/{int:id}', $handler);
// $route is a Route instance; $route->pattern === '/news/{int:id}'

$router->GET('/about', $handler)->name('about');
// chains work because Route::name() returns the Route itself
```

**Post-hoc registration** - assign a name after registering the route:

```php
$router->GET('/foo', $handler);
$router->registerName('foo', '/foo');
```

---

### Route::middleware() - Per-route middleware

Attach a **list of middleware** to be executed before the route handler:

```php
$router->POST('/api/users', [UserController::class, 'create'])
    ->middleware([
        AuthMiddleware::class,
        CsrfMiddleware::class,
        RBACPermissionMiddleware::class,
    ]);
```

**Scope is bound to the (pattern, method) pair** - middleware only runs on the method it was attached to. This matches the behaviour of Express, Laravel, Slim, and other mainstream routers:

```php
$router->GET('/api/users', $list);                                 // public read
$router->POST('/api/users', $create)->middleware([Auth::class]);   // protected write

$router->match('/api/users', 'GET');   // [2] -> []
$router->match('/api/users', 'POST');  // [2] -> [Auth::class]
```

**Compound methods (`GET_POST`)** fan the middleware out to each constituent method:

```php
$router->GET_POST('/foo', $handler)->middleware([Auth::class]);
// GET /foo  -> [Auth::class]
// POST /foo -> [Auth::class]
```

**Append semantics** - chained `->middleware()` calls accumulate:

```php
$router->GET('/admin', $handler)
    ->middleware([AuthMiddleware::class])
    ->middleware([AdminOnlyMiddleware::class, RateLimitMiddleware::class]);
// Registered: [Auth, AdminOnly, RateLimit]
```

**Supported middleware types:**
- `class-string` - PSR-15 MiddlewareInterface class (HTTP-Application instantiates at runtime)
- `callable / Closure` - function with signature `function($request, $handler)`
- `MiddlewareInterface instance` - pre-instantiated object

**Reading middleware from match():**

```php
$result = $router->match('/api/users', 'POST');
// $result === [
//     [UserController::class, 'create'],      // [0] callable
//     [],                                     // [1] params
//     [Auth::class, Csrf::class, RBAC::class] // [2] middleware
// ]

[$callable, $params, $middleware] = $result;
```

**Integration with `codesaur/http-application`:**
HTTP-Application automatically reads middleware from match() and appends them to the pipeline. See the http-application docs for details.

---

### Automatic middleware via inheritance (user-defined example pattern)

> **Note:** `AuthenticatedRouter` below is **not** part of the codesaur/router package - it's an example base class you would write in your own application. You can pick any name (e.g. `AdminRouter`, `ApiRouter`); the pattern is what matters, not the name.

When many routes share the same middleware, attach it automatically inside **your own base class**:

```php
// Example - a base class you write in your own application
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

// Usage:
class UsersRouter extends AuthenticatedRouter
{
    public function __construct()
    {
        $this->GET('/users', [...]);       // Auth + Csrf auto-attached
        $this->POST('/users', [...]);
        $this->DELETE('/users/{int:id}', [...])
            ->middleware([AdminOnlyMiddleware::class]);  // additional
    }
}
```

This pattern classifies routes and shares common middleware via **inheritance** - analogous to Laravel route groups but cleaner.

---

### Client-side URL Patterns

For dynamic UIs where the parameter value is only known on the client (e.g. row id from a fetched list), use `pattern()` to emit a placeholder pattern that JavaScript can substitute:

```php
$pattern = $router->pattern('profile'); // -> /profile/{id}
```

Filter prefixes (`int:`, `uint:`, `float:`, `utf8:`) are stripped, leaving only the parameter name. Static segments are preserved unchanged.

#### Exposing it to your template engine

`pattern()` is a plain PHP method on the Router instance. To use the `{{ "route-name"|pattern }}` shorthand shown below, register it as a filter/function in your template engine - the Router package itself does not ship with any template integration.

Example with [`codesaur/template`](https://github.com/codesaur-php/Template):

```php
$template->addFilter('pattern', fn(string $name) => $router->pattern($name));
```

Once the filter is registered, the template emits the pattern and JS substitutes the value:

```html
<script>
const URL_PATTERN = '{{ "profile"|pattern }}';
fetch(URL_PATTERN.replace('{id}', selectedId));
</script>
```

If you prefer not to register a filter, call the method directly when rendering:

```html
<script>
const URL_PATTERN = '<?= $router->pattern('profile') ?>';
fetch(URL_PATTERN.replace('{id}', selectedId));
</script>
```

| Method | When to use |
|--------|-------------|
| `generate($name, $params)` | Server-side URL with known values - validates and rejects wrong types |
| `pattern($name)` | Client-side template where JS substitutes the value - no validation |

---

## Matching & Dispatching

`match()` **always returns a fixed 3-element tuple array** (or `null` when no route matches):

| Position | Value | Type |
|---|---|---|
| `[0]` | callable | Closure or `[Class, 'method']` |
| `[1]` | params | `array<string, mixed>` - parameters extracted from the pattern |
| `[2]` | middleware | `list<class-string\|callable\|MiddlewareInterface>` - may be `[]` |

**Concrete contract benefits:**
- All 3 positions are always present - routes without middleware return `[2] => []`
- Positional access (`$result[2]`) is the fastest - no hash lookup
- Direct destructuring: `[$callable, $params, $middleware] = $result;`
- No `?? []` checks required by consumers

### Processing incoming requests

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
    // Controller method - [Class, 'method'] form
    [$class, $action] = $callable;
    $controller = new $class();
    call_user_func_array([$controller, $action], $params);
}
```

### Example - custom router emitting middleware

```php
use codesaur\Router\RouterInterface;

class MyRouter implements RouterInterface
{
    public function match(string $path, string $method): ?array
    {
        // ... matching logic ...

        return [
            $callable,                                    // [0] callable
            ['id' => 10],                                 // [1] params
            [AuthMiddleware::class, RBACMiddleware::class] // [2] middleware
        ];
    }
}
```

### Consuming in HTTP-Application

`codesaur/http-application` forwards the **entire** match() result as the request's `match` attribute. Any custom contextual data should travel as request attributes from middleware:

```php
class SomeMiddleware implements MiddlewareInterface
{
    public function process($request, $handler): ResponseInterface
    {
        $match = $request->getAttribute('match');  // whole tuple
        [$callable, $params, $middleware] = $match;

        // ... custom logic ...
        return $handler->handle($request);
    }
}
```

---

## Example Project

The `example/index.php` file demonstrates all features:

- GET/POST route registration  
- Working with Controller classes  
- Parameter type checking (int, uint, float, string)  
- URL generation test (reverse routing)
- Per-route middleware demo (Logging, Auth, Timing - 3 example middlewares + onion-model pipeline)
- Performance test (10,000 iterations)
- Automatic base-path support
- Unicode character support

Run the example file:
```bash
php -S localhost:8000 -t example
# Then in browser: http://localhost:8000
```  

---

## HEAD -> GET auto-fallback (RFC 7231 sec. 4.3.2)

HTTP HEAD is identical to GET except the response carries no body - only headers. Browsers use HEAD for cache validation (`ETag`/`Last-Modified`), link checkers and uptime monitors hit HEAD to verify resources without downloading them.

codesaur Router **automatically dispatches HEAD requests to the GET handler** if no explicit HEAD route is registered:

```php
// Only GET is registered, yet HEAD works
$router->GET('/news/{int:id}', [NewsController::class, 'view']);

$result = $router->match('/news/10', 'HEAD');  // returns the GET handler
```

### Explicit HEAD routes take precedence

If you need custom HEAD behaviour, register an explicit HEAD route - it wins over the GET fallback:

```php
$router->GET('/api/items', $getHandler);
$router->HEAD('/api/items', $headHandler);  // <- matched for HEAD

$result = $router->match('/api/items', 'HEAD');  // returns $headHandler
```

### NOTE: The consumer must strip the body

The Router only decides which handler to dispatch - **stripping the response body is the consumer's job** (HTTP-Application or your own dispatch code):

```php
$result = $router->match($path, $method);
if ($result !== null) {
    [$callable, $params] = $result;
    \call_user_func_array($callable, $params);

    // HEAD responses must NOT contain a body
    if ($method === 'HEAD') {
        // Either clean output buffer (ob_clean) or
        // replace the PSR-7 Response body with an empty stream
    }
}
```

### No fallback from other methods

HEAD only falls back to **GET**. POST/PUT/DELETE never fall back to HEAD:

```php
$router->POST('/data', $handler);

$router->match('/data', 'HEAD');  // -> null (no match)
```

---

## CI/CD

This project uses GitHub Actions for automated CI/CD:

- Tests on multiple PHP versions (8.2, 8.3, 8.4)
- Tests on Ubuntu and Windows
- Install Composer dependencies
- Run PHPUnit tests
- Measure code coverage

CI/CD workflow runs automatically on push or pull request to `main`, `master`, `develop` branches.

---

## Documentation

Detailed documentation for this package:

- **[API](api.md)** - Detailed documentation of all public APIs, methods, parameters, exceptions (auto-generated from PHPDoc using Cursor AI)
- **[REVIEW](review.md)** - Code review report, strengths, improvement opportunities (generated using Cursor AI)
- **[CHANGELOG](../../CHANGELOG.md)** - History of all package version changes

---

## Running Tests

This project includes unit tests using PHPUnit (**71 tests, 161 assertions** - `RouterTest` and `AdapterPatternTest`).

### Install Dependencies

```bash
composer install
```

### Run Tests

#### Using Composer Scripts

```bash
composer test              # Run all tests
composer test:coverage     # Run tests with coverage
```

#### Using PHPUnit Directly

```bash
vendor/bin/phpunit                                   # Run all tests
vendor/bin/phpunit tests/RouterTest.php              # Run specific test file
vendor/bin/phpunit --coverage-text                   # View test coverage
vendor/bin/phpunit --filter testMatch tests/RouterTest.php  # Run specific method
```

**Windows users:** Replace `vendor/bin/phpunit` with `vendor\bin\phpunit.bat`

---

## License

This project is licensed under MIT License.

---

## Author

Narankhuu  
https://github.com/codesaur  
