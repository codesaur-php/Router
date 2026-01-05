# API Documentation

This documentation provides detailed information about all public APIs of the `codesaur/router` package.

---

## Table of Contents

- [RouterInterface](#routerinterface)
- [Router](#router)
- [Callback](#callback)

---

## RouterInterface

**Namespace:** `codesaur\Router`

Core interface that must be implemented by Router.

### Methods

#### `getRoutes(): array`

Returns a list of all registered routes.

**Returns:** `array<string, array<string, Callback>>`  
Route array. Structure: `['pattern' => ['METHOD' => Callback object]]`

**Example:**
```php
$routes = $router->getRoutes();
// [
//     '/news/{int:id}' => [
//         'GET' => Callback object
//     ]
// ]
```

---

#### `merge(RouterInterface $router): void`

Merges routes from another Router into this Router.

**Parameters:**
- `RouterInterface $router` - Additional router (routes to merge)

**Returns:** `void`

**Notes:**
- Usually used to merge module routes.php files with the main router
- Route names are also merged
- If routes with the same name exist, the first router's route takes precedence

**Example:**
```php
$moduleRouter = new Router();
$moduleRouter->GET('/module/route', function() { ... });

$mainRouter->merge($moduleRouter);
```

---

#### `match(string $pattern, string $method): Callback|null`

Finds a matching route based on incoming URL pattern and HTTP method.

**Parameters:**
- `string $pattern` - URL path to search (example: `/news/123`)
- `string $method` - HTTP method (GET, POST, PUT, DELETE, PATCH...)

**Returns:** `Callback|null`  
Matching route (Callback object with dynamic parameters already set), or null

**Example:**
```php
$callback = $router->match('/news/10', 'GET');
if ($callback instanceof Callback) {
    $params = $callback->getParameters(); // ['id' => 10]
    $callable = $callback->getCallable();
    call_user_func_array($callable, $params);
}
```

---

#### `generate(string $routeName, array $params): string`

Generates URL based on route name (reverse routing).

**Parameters:**
- `string $routeName` - Route name (registered with name() method)
- `array<string, mixed> $params` - Parameters to pass (example: `['id' => 10, 'slug' => 'test']`)

**Returns:** `string` - Generated URL path

**Throws:**
- `\OutOfRangeException` - If route name is not found
- `\InvalidArgumentException` - If parameter type is wrong

**Example:**
```php
$router->GET('/news/{int:id}', ...)->name('news-view');
$url = $router->generate('news-view', ['id' => 10]);
// → "/news/10"
```

---

## Router

**Namespace:** `codesaur\Router`

Core Router class for codesaur Framework's lightweight routing solution.

**Implements:** `RouterInterface`

### Description

This Router performs the following operations:
- Register routes (using dynamic `__call`: `$router->GET('/news', ...)`)
- Process routes with parameters like `{int:id}`, `{float:price}`, `{uint:page}`, `{slug}`
- Find routes matching request path and HTTP method using `match()`
- Generate URLs from route names
- Merge other Router instances using `merge()`

Small, stable, can be used standalone without a framework.

### Constants

#### `FILTERS_REGEX`

Regex pattern to detect routes with parameters.

**Value:** `'/\{(int:|uint:|float:)?(\w+)}/'`

This regex detects all types of parameters like `{param}`, `{int:id}`, `{uint:page}`, `{float:price}`.

**Example:** `/news/{int:id}/{slug}`

---

#### `INT_REGEX`

Regex pattern for INTEGER type parameters. Allows negative and positive integers.

**Value:** `'(-?\d+)'`

---

#### `UNSIGNED_INT_REGEX`

Regex pattern for UNSIGNED INTEGER type parameters. Only allows positive integers (0 and above).

**Value:** `'(\d+)'`

---

#### `FLOAT_REGEX`

Regex pattern for FLOAT type parameters. Allows negative and positive decimal numbers.

**Value:** `'(-?\d+|-?\d*\.\d+)'`

---

#### `DEFAULT_REGEX`

Regex pattern for DEFAULT string type parameters. Allows URL-safe characters and some special characters.

**Value:** `'([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@\-]+)'`

---

### Methods

#### `__call(string $method, array $properties): static`

Magic method - registers routes like GET, POST, PUT, DELETE, etc.

**Parameters:**
- `string $method` - HTTP method name (GET, POST, PUT, DELETE, PATCH, etc.)
- `array<mixed> $properties` - 
  - `[0]` => route pattern (string) - route pattern
  - `[1]` => callback (callable|array) - callback to execute

**Returns:** `static` - Returns router object for method chaining

**Throws:**
- `\InvalidArgumentException` - When route configuration is wrong (pattern or callback is empty/wrong)

**Notes:**
- This method allows dynamically calling HTTP methods
- Method must be written in uppercase (GET, POST, PUT, DELETE, PATCH)

**Example:**
```php
$router->GET('/news/{int:id}', [NewsController::class, 'view'])->name('news-view');
$router->POST('/users', function() { ... });
$router->PUT('/users/{int:id}', [UserController::class, 'update']);
```

---

#### `name(string $ruleName): void`

Assigns a name to the last registered route.

**Parameters:**
- `string $ruleName` - Route name (must be unique)

**Returns:** `void`

**Notes:**
- Named routes are used to generate URLs with `generate()` method
- Only one name can be assigned per route
- If `name()` is called again, it updates the name of the last registered route

**Example:**
```php
$router->GET('/news/{int:id}', ...)->name('news-view');
$url = $router->generate('news-view', ['id' => 10]); // → /news/10
```

---

#### `match(string $path, string $method): Callback|null`

Finds a route matching request path and HTTP method.

**Parameters:**
- `string $path` - Incoming URL path (example: `/news/10`)
- `string $method` - HTTP method (GET, POST, PUT, DELETE, PATCH, etc.)

**Returns:** `Callback|null` - Matching route (Callback object), or null

**Notes:**
- This method checks registered routes in order and returns the first match
- Dynamic parameters are automatically set in the Callback object if found

**Example:**
```php
$callback = $router->match('/news/10', 'GET');
if ($callback instanceof Callback) {
    $params = $callback->getParameters(); // ['id' => 10]
    $callable = $callback->getCallable();
    call_user_func_array($callable, $params);
}
```

---

#### `merge(RouterInterface $router): void`

Merges routes from another router into this router.

**Parameters:**
- `RouterInterface $router` - Additional router (routes to merge)

**Returns:** `void`

**Notes:**
- This method is used to merge module routes.php files with the main router
- Route names are also merged
- If routes with the same name exist, the first router's route takes precedence

**Example:**
```php
$moduleRouter = new Router();
$moduleRouter->GET('/module/route', function() { ... });
$mainRouter->merge($moduleRouter);
```

---

#### `generate(string $ruleName, array $params = []): string`

Generates URL from route name (reverse routing).

**Parameters:**
- `string $ruleName` - Route name (registered with name() method)
- `array<string, mixed> $params` - Parameters (example: `['id' => 10, 'slug' => 'test']`)

**Returns:** `string` - Generated URL path

**Throws:**
- `\OutOfRangeException` - If named route is not found
- `\InvalidArgumentException` - If parameter type is wrong (example: int required but string passed)

**Notes:**
- Inserts parameters into named route pattern to generate actual URL
- Parameter types (int, uint, float) are validated, throws exception if wrong

**Example:**
```php
$router->GET('/news/{int:id}', ...)->name('news-view');
$url = $router->generate('news-view', ['id' => 10]); // → /news/10
```

---

#### `getRoutes(): array`

Returns a list of all registered routes.

**Returns:** `array<string, array<string, Callback>>`

**Example:**
```php
$routes = $router->getRoutes();
```

---

## Callback

**Namespace:** `codesaur\Router`

Small wrapper class that stores callable (function, method, closure, etc.) and parameters to pass to that callable for each router route.

### Description

This class is used to send dynamic parameters during routing.

### Constructor

#### `__construct(callable|array $callable)`

**Parameters:**
- `callable|array{class-string, string} $callable` - Callable object to execute
  - Function: `'function_name'`
  - Closure: `function() { ... }`
  - Static method: `[ClassName::class, 'methodName']`
  - Instance method: `[$object, 'methodName']`

**Example:**
```php
// Closure
$callback = new Callback(function($id) {
    return "ID: $id";
});

// Controller method
$callback = new Callback([UserController::class, 'view']);

// Function
$callback = new Callback('my_function');
```

---

### Methods

#### `getCallable(): callable|array`

Returns the registered callable.

**Returns:** `callable|array{class-string, string}` - Callable object to execute

**Example:**
```php
$callable = $callback->getCallable();

if ($callable instanceof \Closure) {
    // Closure
    call_user_func_array($callable, $params);
} else if (is_array($callable)) {
    // Controller method
    [$class, $method] = $callable;
    $controller = new $class();
    call_user_func_array([$controller, $method], $params);
} else {
    // Function
    call_user_func_array($callable, $params);
}
```

---

#### `getParameters(): array`

Returns parameters to pass from the route.

**Returns:** `array<string, mixed>` - Parameter array (key is parameter name)

**Notes:**
- Parameters are values extracted from dynamic parts of route pattern
- Example: For pattern `/news/{int:id}`, if path `/news/10` matches, returns `['id' => 10]`

**Example:**
```php
$params = $callback->getParameters();
// ['id' => 10, 'slug' => 'test-article']
```

---

#### `setParameters(array $parameters): void`

Sets parameters to pass to callable.

**Parameters:**
- `array<string, mixed> $parameters` - Parameters (key is parameter name)

**Returns:** `void`

**Notes:**
- This method is usually called by `Router::match()` method to store parameters extracted from route pattern in Callback object

**Example:**
```php
$callback->setParameters(['id' => 10, 'slug' => 'test']);
```

---

## Route Parameter Types

Router supports the following parameter types:

| Type | Pattern | Example | Description | Regex |
|------|---------|-------|---------|-------|
| Integer | `{int:id}` | `/post/{int:id}` | Allows negative numbers | `(-?\d+)` |
| Unsigned Integer | `{uint:page}` | `/users/{uint:page}` | Only positive integers (0 and above) | `(\d+)` |
| Float | `{float:num}` | `/price/{float:num}` | 1.4, -2.56, etc. | `(-?\d+|-?\d*\.\d+)` |
| String (default) | `{slug}` | `/tag/{slug}` | A-z0-9 and URL-safe characters | `([A-Za-z0-9%_,!~&)(=;'$.*\[\]@-]+)` |

**Example:**
```php
// Using multiple parameter types
$router->GET('/sum/{int:a}/{uint:b}', function (int $a, int $b) {
    echo "$a + $b = " . ($a + $b);
});

// Float parameter
$router->GET('/price/{float:amount}', function (float $amount) {
    echo "Price: $amount";
});

// String parameter (default)
$router->GET('/tag/{slug}', function (string $slug) {
    echo "Tag: $slug";
});
```

**Note:**
- Parameter name must match between route pattern and callback function parameter name
- Parameter type is validated when using `generate()` method

---

## HTTP Methods

Router supports the following HTTP methods:
- **GET** - Read data
- **POST** - Create new data
- **PUT** - Full update
- **DELETE** - Delete
- **PATCH** - Partial update

**Example:**
```php
// GET - Get data
$router->GET('/users', function() {
    return getAllUsers();
});

// POST - Create new user
$router->POST('/users', function() {
    return createUser($_POST);
});

// PUT - Update user
$router->PUT('/users/{int:id}', function(int $id) {
    return updateUser($id, $_POST);
});

// DELETE - Delete user
$router->DELETE('/users/{int:id}', function(int $id) {
    return deleteUser($id);
});

// PATCH - Partial update
$router->PATCH('/users/{int:id}', function(int $id) {
    return partialUpdateUser($id, $_POST);
});
```

**RESTful API example:**
```php
// Users resource
$router->GET('/users', [UserController::class, 'index'])->name('users.index');
$router->GET('/users/{int:id}', [UserController::class, 'show'])->name('users.show');
$router->POST('/users', [UserController::class, 'store'])->name('users.store');
$router->PUT('/users/{int:id}', [UserController::class, 'update'])->name('users.update');
$router->DELETE('/users/{int:id}', [UserController::class, 'destroy'])->name('users.destroy');
```

---

## Exceptions

### `\InvalidArgumentException`

Thrown when route configuration is wrong or parameter type is incorrect.

**Example:**
```php
try {
    $router->generate('profile', ['id' => 'abc']); // int required
} catch (\InvalidArgumentException $e) {
    // Parameter type is wrong
}
```

### `\OutOfRangeException`

Thrown when named route is not found.

**Example:**
```php
try {
    $router->generate('non-existent-route', []);
} catch (\OutOfRangeException $e) {
    // Route not found
}
```
