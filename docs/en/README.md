# 🦖 codesaur/router  

Lightweight, fast, object-oriented routing component

`codesaur/router` is part of the **codesaur ecosystem** but can be used independently as a small yet very flexible Router component.

**Features:**
- ⚡ Fast: dynamic parameter matching + regex filtering 
- 🔧 Multiple parameter types: `{int:id}`, `{uint:page}`, `{float:price}`, `{slug}`
- 🎯 Route name → URL generation (reverse routing)
- 🧩 Supports Controller and Closure callbacks
- 🔀 Router merge (combining module routes.php files)
- 🌙 Can be used standalone (no framework required)

---

## Installation

### Requirements

- PHP 8.2.1 or higher
- Composer

### Install via Composer

```bash
composer require codesaur/router
```

Or add directly to `composer.json`:

```json
{
    "require": {
        "codesaur/router": "^5.0.0"
    }
}
```

Then:

```bash
composer install
```

### Using Autoload

Use Composer autoload:

```php
require 'vendor/autoload.php';

use codesaur\Router\Router;
use codesaur\Router\Callback;

$router = new Router();
// ...
```

### Direct Usage (standalone)

If you don't use Composer, you can download the files directly and use them:

```php
require_once 'src/Router.php';
require_once 'src/Callback.php';
require_once 'src/RouterInterface.php';

use codesaur\Router\Router;
// ...
```

---

## Quick Start

### Simple Route

```php
use codesaur\Router\Router;
use codesaur\Router\Callback;

$router = new Router();

// Register GET route
$router->GET('/hello/{firstname}', function ($firstname) {
    echo "Hello $firstname!";
});

// Match route
$callback = $router->match('/hello/Narankhuu', 'GET');

if ($callback instanceof Callback) {
    $callable = $callback->getCallable();
    $params = $callback->getParameters();
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
// → /profile/25
```

If wrong parameter type is passed:

```php
$router->generate('profile', ['id' => 'abc']);
```

Result → `InvalidArgumentException`

---

## Matching & Dispatching

Process incoming requests:

```php
// Find route matching URL and HTTP method
$callback = $router->match("/insert/data", "POST");

if ($callback instanceof Callback) {
    // Get callable and parameters
    $callable = $callback->getCallable();
    $params = $callback->getParameters();
    
    // Execute callback
    call_user_func_array($callable, $params);
} else {
    // Route not found - return 404
    http_response_code(404);
    echo "Page not found";
}
```

**Complete Example:**
```php
// Process request
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$callback = $router->match($path, $method);

if ($callback instanceof Callback) {
    $callable = $callback->getCallable();
    $params = $callback->getParameters();
    
    if ($callable instanceof \Closure) {
        call_user_func_array($callable, $params);
    } else {
        // Controller method
        [$class, $method] = $callable;
        $controller = new $class();
        call_user_func_array([$controller, $method], $params);
    }
}
```

---

## Example Project

The `example/index.php` file demonstrates all features:

- ✅ GET/POST route registration  
- ✅ Working with Controller classes  
- ✅ Parameter type checking (int, uint, float, string)  
- ✅ URL generation test (reverse routing)  
- ✅ Performance test (10,000 iterations)
- ✅ Automatic base-path support
- ✅ Unicode character support

Run the example file:
```bash
php -S localhost:8000 -t example
# Then in browser: http://localhost:8000
```  

---

## Router Merge

Merge module routes:

```php
// Create module router
$moduleRouter = new Router();
$moduleRouter->GET('/module/users', function() {
    echo "Module users";
})->name('module.users');

// Merge with main router
$mainRouter = new Router();
$mainRouter->merge($moduleRouter);

// Now /module/users route works
$callback = $mainRouter->match('/module/users', 'GET');
```

**Note:**
- Route names are also merged
- If routes with the same name exist, the first router's route takes precedence

---

## CI/CD

This project uses GitHub Actions for automated CI/CD:

- ✅ Tests on multiple PHP versions (8.2, 8.3, 8.4)
- ✅ Tests on Ubuntu and Windows
- ✅ Install Composer dependencies
- ✅ Run PHPUnit tests
- ✅ Measure code coverage

CI/CD workflow runs automatically on push or pull request to `main`, `master`, `develop` branches.

---

## Documentation

Detailed documentation for this package:

- 📚 **[API](api.md)** - Detailed documentation of all public APIs, methods, parameters, exceptions (auto-generated from PHPDoc using Cursor AI)
- 🔍 **[REVIEW](review.md)** - Code review report, strengths, improvement opportunities (generated using Cursor AI)
- 📋 **[CHANGELOG](../../CHANGELOG.md)** - History of all package version changes

---

## Running Tests

This project includes unit tests and integration tests using PHPUnit.

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
vendor/bin/phpunit                                    # Run all tests
vendor/bin/phpunit tests/RouterTest.php              # Run specific test file
vendor/bin/phpunit tests/CallbackTest.php            # Run callback test
vendor/bin/phpunit --coverage-text                   # View test coverage
vendor/bin/phpunit --filter testMatch tests/RouterTest.php  # Run specific method
```

**Windows users:** Replace `vendor/bin/phpunit` with `vendor\bin\phpunit.bat`

---

## 📄 License

This project is licensed under MIT License.

---

## 👨‍💻 Author

Narankhuu  
https://github.com/codesaur  
