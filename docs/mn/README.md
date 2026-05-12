# codesaur/router  

Хөнгөн, хурдан, объект-суурьтай маршрутчиллын (routing) компонент

`codesaur/router` нь **codesaur ecosystem**-ийн нэг хэсэг боловч бие даасан байдлаар ашиглах боломжтой, жижиг хэмжээтэй боловч маш уян хатан Router компонент юм.

Онцлог:
- Хурдан: dynamic parameter matching + regex filtering
- Олон төрлийн параметр: `{int:id}`, `{uint:page}`, `{float:price}`, `{slug}`, `{utf8:text}`
- Route name -> URL generate (reverse routing)
- Controller болон Closure callback дэмжинэ
- Router merge (модулиудын routes.php-г нэгтгэх)
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

Эсвэл `composer.json` файлд шууд нэмэх:

```json
{
    "require": {
        "codesaur/router": "^5.0.0"
    }
}
```

Дараа нь:

```bash
composer install
```

### Autoload ашиглах

Composer autoload-ийг ашиглах:

```php
require 'vendor/autoload.php';

use codesaur\Router\Router;
use codesaur\Router\Callback;

$router = new Router();
// ...
```

### Шууд ашиглах (standalone)

Хэрэв Composer ашиглахгүй бол файлуудыг шууд татаж авч ашиглаж болно:

```php
require_once 'src/Router.php';
require_once 'src/Callback.php';
require_once 'src/RouterInterface.php';

use codesaur\Router\Router;
// ...
```

---

## Quick Start

### Энгийн маршрут

```php
use codesaur\Router\Router;
use codesaur\Router\Callback;

$router = new Router();

// GET маршрут бүртгэх
$router->GET('/hello/{firstname}', function ($firstname) {
    echo "Hello $firstname!";
});

// Маршрут тааруулах
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

### Client-side URL Pattern

Параметрийн утга нь зөвхөн client дээр мэдэгдэх (жишээ нь, fetch хийсэн жагсаалтаас сонгосон row id) динамик UI-д `pattern()` ашиглан JavaScript-д орлуулахад бэлэн placeholder pattern-ыг буцаана:

```php
$pattern = $router->pattern('profile'); // -> /profile/{id}
```

Filter prefix-үүд (`int:`, `uint:`, `float:`, `utf8:`) хасагдаж, зөвхөн параметрийн нэр үлдэнэ. Static хэсгүүд хэвээр хадгалагдана.

#### Template engine-д холбох

`pattern()` нь Router instance-н энгийн PHP method. Доор үзүүлсэн `{{ "route-name"|pattern }}` shorthand хэрэглэхийн тулд та өөрийн template engine-д filter/function болгож **гараар бүртгэх ёстой** - Router package өөрөө template integration агуулдаггүй.

Жишээ: [`codesaur/template`](https://github.com/codesaur-php/Template)-д filter бүртгэх:

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

Орж ирсэн request-ийг боловсруулах:

```php
// URL болон HTTP method-д тохирох маршрутыг олох
$callback = $router->match("/insert/data", "POST");

if ($callback instanceof Callback) {
    // Callable болон параметрүүдийг авах
    $callable = $callback->getCallable();
    $params = $callback->getParameters();

    // Callback гүйцэтгэх
    call_user_func_array($callable, $params);
} else {
    // Маршрут олдсонгүй - 404 буцаах
    http_response_code(404);
    echo "Page not found";
}
```

**Бүтэн жишээ:**
```php
// Request-ийг боловсруулах
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

`example/index.php` файл нь бүх функцүүдийг нэг дор харуулна:

- GET/POST маршрут бүртгэх  
- Controller класстай ажиллах  
- Параметрийн төрөл шалгах (int, uint, float, string)  
- URL generate тест (reverse routing)  
- Гүйцэтгэл тест (Performance Test - 10,000 удаа)
- Автомат base-path support
- Монгол үсэг дэмжлэг

Жишээ файлыг ажиллуулах:
```bash
php -S localhost:8000 -t example
# Дараа нь browser дээр: http://localhost:8000
```  

---

## Router Merge

Модулиудын маршрутуудыг нэгтгэх:

```php
// Модулийн router үүсгэх
$moduleRouter = new Router();
$moduleRouter->GET('/module/users', function() {
    echo "Module users";
})->name('module.users');

// Үндсэн router-т нэгтгэх
$mainRouter = new Router();
$mainRouter->merge($moduleRouter);

// Одоо /module/users маршрут ажиллана
$callback = $mainRouter->match('/module/users', 'GET');
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

Энэ проект нь PHPUnit ашиглан unit test-үүд агуулдаг (нийт **54 тест, 103 assertion** — `RouterTest` ба `CallbackTest`).

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
vendor/bin/phpunit tests/CallbackTest.php            # Callback тест ажиллуулах
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
