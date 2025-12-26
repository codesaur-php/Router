# API Documentation

**Хэл:** [🇲🇳 Монгол](API.md) | [🇬🇧 English](API.EN.md)

Энэхүү баримт бичиг нь `codesaur/router` пакетийн бүх public API-г дэлгэрэнгүй тайлбарлана.

---

## Table of Contents

- [RouterInterface](#routerinterface)
- [Router](#router)
- [Callback](#callback)

---

## RouterInterface

**Namespace:** `codesaur\Router`

Router хэрэгжүүлэх ёстой үндсэн интерфэйс.

### Methods

#### `getRoutes(): array`

Бүртгэлтэй бүх маршрутын жагсаалтыг буцаана.

**Returns:** `array<string, array<string, Callback>>`  
Маршрутын массив. Структур: `['pattern' => ['METHOD' => Callback объект]]`

**Example:**
```php
$routes = $router->getRoutes();
// [
//     '/news/{int:id}' => [
//         'GET' => Callback объект
//     ]
// ]
```

---

#### `merge(RouterInterface $router): void`

Өөр Router-ийн маршрутуудыг энэ Router-тэй нэгтгэнэ.

**Parameters:**
- `RouterInterface $router` - Нэмэлт router (маршрутуудыг нэгтгэх)

**Returns:** `void`

**Notes:**
- Ихэвчлэн модулиудын routes.php-г үндсэн router-тэй нэгтгэхэд ашиглагдана
- Нэгтгэхдээ route name-ууд мөн нэгтгэгдэнэ
- Хэрэв ижил нэртэй route байвал эхний router-ийнх нь давуу тал болно

**Example:**
```php
$moduleRouter = new Router();
$moduleRouter->GET('/module/route', function() { ... });

$mainRouter->merge($moduleRouter);
```

---

#### `match(string $pattern, string $method): Callback|null`

Орж ирсэн URL pattern болон HTTP method дээр үндэслэн тохирох маршрутыг хайж буцаана.

**Parameters:**
- `string $pattern` - Хайлтын URL path (жишээ: `/news/123`)
- `string $method` - HTTP method (GET, POST, PUT, DELETE, PATCH...)

**Returns:** `Callback|null`  
Таарсан маршрут (Callback объект, динамик параметрүүд аль хэдийн set хийгдсэн байна), эсвэл null

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

Route name дээр үндэслэн URL үүсгэнэ (reverse routing).

**Parameters:**
- `string $routeName` - Маршрутын нэр (name() методоор бүртгэсэн)
- `array<string, mixed> $params` - Дамжуулах параметрүүд (жишээ: `['id' => 10, 'slug' => 'test']`)

**Returns:** `string` - Үүсгэсэн URL path

**Throws:**
- `\OutOfRangeException` - Хэрэв route name олдохгүй бол
- `\InvalidArgumentException` - Хэрэв параметрийн төрөл буруу бол

**Example:**
```php
$router->GET('/news/{int:id}', ...)->name('news-view');
$url = $router->generate('news-view', ['id' => 10]);
// → "/news/10"
```

---

## Router

**Namespace:** `codesaur\Router`

codesaur Framework-ийн хөнгөн жинтэй маршрутчилал (routing) шийдлийн үндсэн Router класс.

**Implements:** `RouterInterface`

### Description

Энэхүү Router нь дараах үйлдлүүдийг гүйцэтгэнэ:
- Маршрут бүртгэх (динамик `__call` ашиглан: `$router->GET('/news', ...)` хэлбэрээр)
- `{int:id}`, `{float:price}`, `{uint:page}`, `{slug}` гэх мэт параметртэй маршрут боловсруулах
- Request path болон HTTP method-д тохирох маршрутыг `match()` ашиглан олох
- Route name → URL generate хийх
- Модулийн бусад Router-уудыг `merge()` ашиглан нэгтгэх

Жижиг, тогтвортой, фрэймворкоос үл хамааран standalone байдлаар ашиглаж болно.

### Constants

#### `FILTERS_REGEX`

Параметертэй маршрутыг илрүүлэх regex pattern.

**Value:** `'/\{(int:|uint:|float:)?(\w+)}/'`

Энэ regex нь `{param}`, `{int:id}`, `{uint:page}`, `{float:price}` гэх мэт бүх төрлийн параметрийг илрүүлнэ.

**Example:** `/news/{int:id}/{slug}`

---

#### `INT_REGEX`

INTEGER төрлийн параметрийн regex pattern. Сөрөг болон эерэг бүхэл тоонуудыг зөвшөөрнө.

**Value:** `'(-?\d+)'`

---

#### `UNSIGNED_INT_REGEX`

UNSIGNED INTEGER төрлийн параметрийн regex pattern. Зөвхөн эерэг бүхэл тоонуудыг зөвшөөрнө (0 ба түүнээс дээш).

**Value:** `'(\d+)'`

---

#### `FLOAT_REGEX`

FLOAT төрлийн параметрийн regex pattern. Сөрөг болон эерэг бутархай тоонуудыг зөвшөөрнө.

**Value:** `'(-?\d+|-?\d*\.\d+)'`

---

#### `DEFAULT_REGEX`

DEFAULT string төрлийн параметрийн regex pattern. URL-safe тэмдэгтүүд болон зарим тусгай тэмдэгтүүдийг зөвшөөрнө.

**Value:** `'([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@\-]+)'`

---

### Methods

#### `__call(string $method, array $properties): static`

Магик метод - GET, POST, PUT, DELETE гэх мэт маршрут бүртгэнэ.

**Parameters:**
- `string $method` - HTTP method нэр (GET, POST, PUT, DELETE, PATCH гэх мэт)
- `array<mixed> $properties` - 
  - `[0]` => route pattern (string) - маршрутын pattern
  - `[1]` => callback (callable|array) - гүйцэтгэх callback

**Returns:** `static` - Method chaining-д зориулж router объектыг буцаана

**Throws:**
- `\InvalidArgumentException` - Буруу маршрут тохиргоо үед (pattern эсвэл callback хоосон/буруу байвал)

**Notes:**
- Энэ метод нь динамик аргаар HTTP method-уудыг дуудаж болох болгодог
- Method нь том үсгээр бичигдсэн байх ёстой (GET, POST, PUT, DELETE, PATCH)

**Example:**
```php
$router->GET('/news/{int:id}', [NewsController::class, 'view'])->name('news-view');
$router->POST('/users', function() { ... });
$router->PUT('/users/{int:id}', [UserController::class, 'update']);
```

---

#### `name(string $ruleName): void`

Сүүлд бүртгэгдсэн маршрутад нэр онооно.

**Parameters:**
- `string $ruleName` - Маршрутын нэр (уникаль байх ёстой)

**Returns:** `void`

**Notes:**
- Нэртэй маршрутуудыг `generate()` метод ашиглан URL үүсгэхэд ашиглана
- Нэг маршрутад зөвхөн нэг нэр оноож болно
- Хэрэв дахин `name()` дуудвал сүүлд бүртгэгдсэн маршрутын нэрийг шинэчилнэ

**Example:**
```php
$router->GET('/news/{int:id}', ...)->name('news-view');
$url = $router->generate('news-view', ['id' => 10]); // → /news/10
```

---

#### `match(string $path, string $method): Callback|null`

Request path болон HTTP method-д тохирох маршрутыг хайж олно.

**Parameters:**
- `string $path` - Орж ирсэн URL path (жишээ: `/news/10`)
- `string $method` - HTTP method (GET, POST, PUT, DELETE, PATCH гэх мэт)

**Returns:** `Callback|null` - Таарсан маршрут (Callback объект), эсвэл null

**Notes:**
- Энэ метод нь бүртгэлтэй маршрутуудыг дарааллаар шалгаж, таарах эхний маршрутыг буцаана
- Динамик параметрүүд олдвол Callback объектод автоматаар set хийгдэнэ

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

Өөр router-ийн маршрутыг энэ router-т нэгтгэнэ.

**Parameters:**
- `RouterInterface $router` - Нэмэлт router (маршрутуудыг нэгтгэх)

**Returns:** `void`

**Notes:**
- Энэ метод нь модулиудын routes.php файлуудыг үндсэн router-тэй нэгтгэхэд ашиглагдана
- Route name-ууд мөн нэгтгэгдэнэ
- Хэрэв ижил нэртэй route байвал эхний router-ийнх нь давуу тал болно

**Example:**
```php
$moduleRouter = new Router();
$moduleRouter->GET('/module/route', function() { ... });
$mainRouter->merge($moduleRouter);
```

---

#### `generate(string $ruleName, array $params = []): string`

Route name → URL generate хийнэ (reverse routing).

**Parameters:**
- `string $ruleName` - Route name (name() методоор бүртгэсэн)
- `array<string, mixed> $params` - Параметрүүд (жишээ: `['id' => 10, 'slug' => 'test']`)

**Returns:** `string` - Үүсгэсэн URL path

**Throws:**
- `\OutOfRangeException` - Нэртэй маршрут олдохгүй бол
- `\InvalidArgumentException` - Параметрийн төрөл буруу бол (жишээ: int шаардлагатай боловч string дамжуулсан)

**Notes:**
- Нэртэй маршрутын pattern-д параметрүүдийг суулгаж, бодит URL үүсгэнэ
- Параметрийн төрөл (int, uint, float) шалгагдаж, буруу бол exception шиднэ

**Example:**
```php
$router->GET('/news/{int:id}', ...)->name('news-view');
$url = $router->generate('news-view', ['id' => 10]); // → /news/10
```

---

#### `getRoutes(): array`

Бүртгэлтэй маршрутуудын жагсаалтыг буцаана.

**Returns:** `array<string, array<string, Callback>>`

**Example:**
```php
$routes = $router->getRoutes();
```

---

## Callback

**Namespace:** `codesaur\Router`

Router-ийн маршрут бүрт тохирох callable (function, method, closure гэх мэт) болон тухайн callable-д дамжуулах параметрүүдийг хадгалах жижиг wrapper класс.

### Description

Энэ класс нь маршрутын үед динамик параметрүүдийг илгээхэд ашиглагдана.

### Constructor

#### `__construct(callable|array $callable)`

**Parameters:**
- `callable|array{class-string, string} $callable` - Гүйцэтгэх callable объект
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

Бүртгэлтэй callable-г буцаана.

**Returns:** `callable|array{class-string, string}` - Гүйцэтгэх callable объект

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

Маршрутаас дамжуулагдах параметрүүдийг буцаана.

**Returns:** `array<string, mixed>` - Параметрийн массив (түлхүүр нь параметрийн нэр)

**Notes:**
- Параметрүүд нь route pattern-ийн динамик хэсгүүдээс гаргаж авсан утгууд юм
- Жишээ: `/news/{int:id}` pattern-д `/news/10` path таарвал `['id' => 10]` буцаана

**Example:**
```php
$params = $callback->getParameters();
// ['id' => 10, 'slug' => 'test-article']
```

---

#### `setParameters(array $parameters): void`

Callable-д дамжуулах параметрүүдийг онооно.

**Parameters:**
- `array<string, mixed> $parameters` - Параметрүүд (түлхүүр нь параметрийн нэр)

**Returns:** `void`

**Notes:**
- Энэ метод нь ихэвчлэн `Router::match()` методоор дуудагдаж, route pattern-аас гаргаж авсан параметрүүдийг Callback объектод хадгална

**Example:**
```php
$callback->setParameters(['id' => 10, 'slug' => 'test']);
```

---

## Route Parameter Types

Router нь дараах төрлийн параметрүүдийг дэмжинэ:

| Төрөл | Pattern | Жишээ | Тайлбар | Regex |
|------|---------|-------|---------|-------|
| Integer | `{int:id}` | `/post/{int:id}` | Сөрөг тоо зөвшөөрнө | `(-?\d+)` |
| Unsigned Integer | `{uint:page}` | `/users/{uint:page}` | Зөвхөн эерэг бүхэл тоо (0 ба түүнээс дээш) | `(\d+)` |
| Float | `{float:num}` | `/price/{float:num}` | 1.4, -2.56 гэх мэт | `(-?\d+|-?\d*\.\d+)` |
| String (default) | `{slug}` | `/tag/{slug}` | A-z0-9 болон URL-safe тэмдэгтүүд | `([A-Za-z0-9%_,!~&)(=;'$.*\[\]@-]+)` |

**Example:**
```php
// Олон төрлийн параметр ашиглах
$router->GET('/sum/{int:a}/{uint:b}', function (int $a, int $b) {
    echo "$a + $b = " . ($a + $b);
});

// Float параметр
$router->GET('/price/{float:amount}', function (float $amount) {
    echo "Price: $amount";
});

// String параметр (default)
$router->GET('/tag/{slug}', function (string $slug) {
    echo "Tag: $slug";
});
```

**Анхаарах зүйл:**
- Параметрийн нэр нь route pattern болон callback function-ийн parameter name-тэй ижил байх ёстой
- `generate()` method ашиглах үед параметрийн төрөл шалгагдана

---

## HTTP Methods

Router нь дараах HTTP method-уудыг дэмжинэ:
- **GET** - Өгөгдөл унших
- **POST** - Шинэ өгөгдөл үүсгэх
- **PUT** - Бүхэлд нь шинэчлэх
- **DELETE** - Устгах
- **PATCH** - Хэсэгчлэн шинэчлэх

**Example:**
```php
// GET - Өгөгдөл авах
$router->GET('/users', function() {
    return getAllUsers();
});

// POST - Шинэ хэрэглэгч үүсгэх
$router->POST('/users', function() {
    return createUser($_POST);
});

// PUT - Хэрэглэгч шинэчлэх
$router->PUT('/users/{int:id}', function(int $id) {
    return updateUser($id, $_POST);
});

// DELETE - Хэрэглэгч устгах
$router->DELETE('/users/{int:id}', function(int $id) {
    return deleteUser($id);
});

// PATCH - Хэсэгчлэн шинэчлэх
$router->PATCH('/users/{int:id}', function(int $id) {
    return partialUpdateUser($id, $_POST);
});
```

**RESTful API жишээ:**
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

Буруу маршрут тохиргоо эсвэл параметрийн төрөл буруу байх үед шидэгдэнэ.

**Example:**
```php
try {
    $router->generate('profile', ['id' => 'abc']); // int шаардлагатай
} catch (\InvalidArgumentException $e) {
    // Параметрийн төрөл буруу
}
```

### `\OutOfRangeException`

Нэртэй маршрут олдохгүй байх үед шидэгдэнэ.

**Example:**
```php
try {
    $router->generate('non-existent-route', []);
} catch (\OutOfRangeException $e) {
    // Маршрут олдсонгүй
}
```
