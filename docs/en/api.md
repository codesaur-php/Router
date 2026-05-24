# API Documentation

This document provides a detailed reference for the public API of the `codesaur/router` package.

---

## Table of Contents

- [RouterInterface](#routerinterface)
- [Router](#router)
- [Route](#route)

---

## RouterInterface

**Namespace:** `codesaur\Router`

The **full router contract** for the codesaur ecosystem. Requires 4 methods:
- `match()` - request matching
- `generate()` - reverse routing (route name -> URL)
- `pattern()` - client-side substitution pattern
- `getRoutes()` - introspection

Purpose:
- Provides an **implementation-independent boundary** so HTTP applications and Raptor controllers stay decoupled from any specific router
- Allows third-party routers (FastRoute, Symfony Routing, AltoRouter, etc.) to be adapted and used with `codesaur/http-application`
- Keeps concrete Router's internal API (`registerName()`, `registerMiddleware()`) out of the interface

### Methods

#### `match(string $path, string $method): ?array`

Find a route matching the incoming URL path and HTTP method.

**Parameters:**
- `string $path` - The URL path to match (e.g. `/news/123`)
- `string $method` - HTTP method (GET, POST, PUT, DELETE, PATCH...)

**Returns:** `array|null`

On a match, returns a **fixed 3-element tuple array**:
- `[0]` - the callable to execute (Closure or `[Class, 'method']`)
- `[1]` - parameters extracted from the pattern (`['id' => 10]`, etc.)
- `[2]` - middleware list (`[]` if none)

Returns `null` when no route matches.

**Example - basic usage:**
```php
$result = $router->match('/news/10', 'GET');
if ($result === null) {
    http_response_code(404);
    return;
}

[$callable, $params, $middleware] = $result;
call_user_func_array($callable, $params);
```

**Example - custom router emitting middleware:**
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

> **Integration with `codesaur/http-application`:**
> HTTP-Application forwards the **entire** match() result as the request's `match` attribute. Additional contextual data should be attached as request attributes from middleware, not as tuple extras.

---

#### `generate(string $routeName, array $params = []): string`

Reverse routing - build a URL from a route name.

**Returns:** `string` - Generated URL path (raw, not URL-encoded)

**Throws:**
- `\OutOfRangeException` - if route name not found
- `\InvalidArgumentException` - if a parameter type does not match

NOTE: **Encoding contract:** parameter values are substituted **raw** - no percent-encoding. This is intentional and avoids double-encoding when handed to PSR-7 `UriInterface::withPath()`.

**Example:**
```php
$url = $router->generate('news-view', ['id' => 10]);  // '/news/10'
```

---

#### `pattern(string $routeName): string`

Returns a URL pattern with filter prefixes stripped - ready for client-side substitution.

**Returns:** `string` - Pattern with filter prefixes stripped

**Throws:** `\OutOfRangeException` - if route name not found

**Example:**
```php
$pattern = $router->pattern('news-view');  // '/news/{id}/{slug}'
```

---

#### `getRoutes(): array`

Returns the list of all registered routes (for introspection).

**Structure - 2-tuple `[callable, middleware]` per (pattern, method):**
```
[
    pattern => [
        method => [
            [0] callable,    // the handler
            [1] middleware,  // middleware list registered on this route
        ]
    ]
]
```

The pattern is the outer key (it already carries the params info) - no params placeholder is needed inside the entry. Match-time params are only computed by `match()`.

**Use cases:**
- Listing all routes in an admin panel
- Auto-generating a sitemap
- Auto-generating API documentation

**Example:**
```php
foreach ($router->getRoutes() as $pattern => $methods) {
    foreach ($methods as $method => [$callable, $middleware]) {
        echo "$method $pattern\n";
    }
}
```

**Route names:** Naming is a separate concern - it's not returned by `getRoutes()`. For name introspection, use `generate($name)` to build a URL or `pattern($name)` to get a client-side pattern.

---

## Router

**Namespace:** `codesaur\Router`

The lightweight Router class of the codesaur ecosystem.

**Implements:** `RouterInterface`

### Description

This Router supports:
- Registering routes via dynamic `__call` (`$router->GET('/news', ...)` syntax)
- Typed parameters: `{int:id}`, `{float:price}`, `{uint:page}`, `{slug}`, `{utf8:text}`
- Matching request path + HTTP method via `match()`
- Reverse routing via `generate()` (name -> URL)
- Per-route middleware via `Route::middleware()`

Small, stable, framework-independent, usable standalone.

### Constants

| Name | Value | Purpose |
|---|---|---|
| `FILTERS_REGEX` | `'/\{(int:|uint:|float:|utf8:)?(\w+)}/'` | Detects all `{param}`, `{int:id}`, etc. tokens |
| `INT_REGEX` | `'(-?\d+)'` | Negative and positive integers |
| `UNSIGNED_INT_REGEX` | `'(\d+)'` | Positive integers only (0 and up) |
| `FLOAT_REGEX` | `'(-?\d+|-?\d*\.\d+)'` | Negative and positive decimal numbers |
| `DEFAULT_REGEX` | `'([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@\-]+)'` | URL-safe characters (no spaces) |
| `UTF8_REGEX` | `'([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@ \x80-\xFF\-]+)'` | UTF-8 multibyte (Cyrillic, CJK, etc.) + spaces |

### Methods

#### `__call(string $method, array $properties): Route`

Magic method - registers `GET`, `POST`, `PUT`, `DELETE`, etc. routes.

**Parameters:**
- `string $method` - HTTP method name (`GET`, `POST`, ..., or compound `GET_POST`)
- `array<mixed> $properties` - 
 - `[0]` => route pattern (string)
 - `[1]` => callable (Closure or `[Class, 'method']`)

**Returns:** `Route` - Immutable Route object representing the registered route. Used for fluent APIs like `->name(...)`.

**Throws:**
- `\InvalidArgumentException` - if pattern or callback is empty/invalid

**Example:**
```php
$router->GET('/news/{int:id}', [NewsController::class, 'view'])->name('news-view');
$router->POST('/users', function() { ... });
$router->GET_POST('/api/data', [ApiController::class, 'data']);  // multiple methods
```

---

#### `registerName(string $ruleName, string $pattern): void`

**Internal API** for registering a route name -> pattern mapping. Typical usage goes through `Route::name()` automatically.

**Parameters:**
- `string $ruleName` - Route name (must be unique)
- `string $pattern` - The route pattern

**Notes:**
- Normal flow: `$router->GET(...)->name('foo')` - `Route::name()` calls this internally
- Direct usage: only when you want to attach a name post-hoc, after the route is registered
- Re-registering the same name overrides the previous pattern

**Example:**
```php
// Standard usage (via Route::name() automatically):
$router->GET('/news/{int:id}', ...)->name('news-view');

// Post-hoc registration:
$router->GET('/foo', $handler);
$router->registerName('foo', '/foo');
```

---

#### `registerMiddleware(string $pattern, string $method, array $middleware): void`

**Internal API** for attaching middleware to a (pattern, method) route. Typical usage goes through `Route::middleware([...])` automatically.

**Parameters:**
- `string $pattern` - The route pattern
- `string $method` - HTTP method (supports compound `'GET_POST'`)
- `list<class-string|callable|\Psr\Http\Server\MiddlewareInterface> $middleware`

**Notes:**
- Scope is bound to the **(pattern, method)** pair - isolated from middleware on other methods
- Compound methods (`GET_POST`) fan out to each constituent method
- Append semantics - multiple calls accumulate the middleware list
- Returned in match() tuple at position `[2]`
- Normal flow: `Route::middleware([...])` calls this internally
- Direct usage: base class auto-attach, or post-hoc additions

**Example:**
```php
// Standard flow:
$router->POST('/api/users', $handler)
    ->middleware([CsrfMiddleware::class]);

// Post-hoc:
$router->GET('/foo', $handler);
$router->registerMiddleware('/foo', 'GET', [AuthMiddleware::class]);
```

---

#### `match(string $path, string $method): ?array`

Implementation of `RouterInterface::match()`. See the RouterInterface::match section above for the tuple shape and return contract.

**codesaur Router implementation additions:**
- **HEAD -> GET auto-fallback** (RFC 7231 sec. 4.3.2):
  If no explicit HEAD route is registered, HEAD requests automatically dispatch to the GET handler.
  The consumer is responsible for stripping the response body for HEAD requests.

---

#### `generate(string $ruleName, array $params = []): string`

Implementation of `RouterInterface::generate()`. See the RouterInterface::generate section above for full details.

---

#### `pattern(string $ruleName): string`

Implementation of `RouterInterface::pattern()`. See the RouterInterface::pattern section above for full details.

**Template + JS usage example:**
```html
<script>
const URL = '<?= $router->pattern('news-view') ?>';
fetch(URL.replace('{id}', 42).replace('{slug}', 'hello'));
</script>
```

---

## Route

**Namespace:** `codesaur\Router`

Immutable value object returned by `Router::__call()` when registering a route. Provides the `->name(...)` fluent API.

### Description

The Route class exists to keep the Router **stateless**:
- A registered route carries its own pattern context, so `name()` and `middleware()` fluent methods operate without ambiguity
- PHP 8.2 `readonly` class - immutable

### Properties

#### `public readonly string $pattern`

The pattern of this route (e.g. `/news/{int:id}`). Read-only, cannot be modified.

### Methods

#### `name(string $ruleName): self`

Attach a name to this route.

**Parameters:**
- `string $ruleName` - Route name (must be unique)

**Returns:** `self` - This Route object (for chaining)

**Notes:**
- Strict mode - reassigning the same name to a different pattern throws `\LogicException`
- Internally calls `Router::registerName()`

**Example:**
```php
$router->GET('/news/{int:id}', $handler)->name('news.view');
```

#### `middleware(array $middleware): self`

Attach a **list of middleware** to this route. They run before the route handler.

**Parameters:**
- `list<class-string|callable|\Psr\Http\Server\MiddlewareInterface> $middleware` - Middleware list

**Returns:** `self` - This Route object (for chaining)

**Supported middleware types:**
- **class-string** - PSR-15 MiddlewareInterface class (HTTP-Application instantiates at runtime)
- **callable / Closure** - function with signature `function($request, $handler)`
- **MiddlewareInterface instance** - pre-instantiated object

**Notes:**
- Append semantics - multiple calls accumulate the middleware list
- Internally calls `Router::registerMiddleware()`
- Returned in match() tuple at position `[2]`

**Example:**
```php
// Simple usage
$router->POST('/api/users', $handler)
    ->middleware([CsrfMiddleware::class, RBACMiddleware::class]);

// Append semantics
$router->GET('/admin', $handler)
    ->middleware([AuthMiddleware::class])
    ->middleware([AdminOnlyMiddleware::class]);
// Registered: [Auth, AdminOnly]

// Closure middleware
$router->GET('/test', $handler)
    ->middleware([
        function($req, $handler) {
            return $handler->handle($req->withAttribute('test', true));
        }
    ]);
```
