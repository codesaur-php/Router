# 🦖 codesaur/router  

[![CI](https://github.com/codesaur-php/Router/actions/workflows/ci.yml/badge.svg)](https://github.com/codesaur-php/Router/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2.1-777BB4.svg?logo=php)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**Хэл:** Монгол | [English](README.EN.md)

Хөнгөн, хурдан, объект-суурьтай маршрутчиллын (routing) компонент

`codesaur/router` нь codesaur PHP Framework-ийн нэг хэсэг боловч бие даасан байдлаар ашиглах боломжтой, жижиг хэмжээтэй боловч маш уян хатан Router компонент юм.

Онцлог:
- ⚡ Хурдан: dynamic parameter matching + regex filtering 
- 🔧 Олон төрлийн параметр: `{int:id}`, `{uint:page}`, `{float:price}`, `{slug}`
- 🎯 Route name → URL generate (reverse routing)
- 🧩 Controller болон Closure callback дэмжинэ
- 🔀 Router merge (модулиудын routes.php-г нэгтгэх)
- 🌙 Standalone ашиглаж болно (framework шаардлагагүй)

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
// → /profile/25
```

Буруу төрлийн параметр дамжуулбал:

```php
$router->generate('profile', ['id' => 'abc']);
```

Үр дүн → `InvalidArgumentException`

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

- ✅ GET/POST маршрут бүртгэх  
- ✅ Controller класстай ажиллах  
- ✅ Параметрийн төрөл шалгах (int, uint, float, string)  
- ✅ URL generate тест (reverse routing)  
- ✅ Гүйцэтгэл тест (Performance Test - 10,000 удаа)
- ✅ Автомат base-path support
- ✅ Монгол үсэг дэмжлэг

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

- ✅ Олон PHP хувилбарууд дээр тест (8.2, 8.3, 8.4)
- ✅ Ubuntu болон Windows дээр тест
- ✅ Composer dependencies суулгах
- ✅ PHPUnit тестүүд ажиллуулах
- ✅ Code coverage хэмжих

CI/CD workflow нь `main`, `master`, `develop` салбарууд дээр push эсвэл pull request хийхэд автоматаар ажиллана.

---

## Documentation

Энэ пакетийн дэлгэрэнгүй баримт бичгүүд:

- 📚 **[API](API.md)** - Бүх public API-ийн дэлгэрэнгүй тайлбар, method-ууд, parameter-ууд, exception-ууд (PHPDoc-уудаас Cursor AI ашиглан автоматаар үүсгэсэн)
- 🔍 **[REVIEW](REVIEW.md)** - Код шалгалтын тайлан, давуу талууд, сайжруулах боломжууд  (Cursor AI ашиглан үүсгэсэн)
- 📋 **[CHANGELOG](CHANGELOG.md)** - Пакетийн бүх хувилбаруудын өөрчлөлтийн түүх

---

## Testing

Энэ проект нь PHPUnit ашиглан бүрэн тест хийгдсэн байна.

### Тест ажиллуулах

Эхлээд dependencies суулгана:

```bash
composer install
```

Дараа нь тестүүдийг ажиллуулна:

**Windows дээр:**
```cmd
vendor\bin\phpunit.bat
```

**Linux/Mac дээр:**
```bash
vendor/bin/phpunit
```

Эсвэл coverage-тэй хамт:

**Windows:**
```cmd
vendor\bin\phpunit.bat --coverage-text
```

**Linux/Mac:**
```bash
vendor/bin/phpunit --coverage-text
```

### Тестүүдийн бүтэц

Тестүүд нь дараах хэсгүүдэд хуваагдсан:

- **RouterTest.php** - Router классын тестүүд:
  - Маршрут бүртгэх (GET, POST, PUT, DELETE)
  - Нэртэй маршрутууд
  - Маршрут тааруулах (match) - бүх төрлийн параметрүүдтэй
  - URL үүсгэх (generate)
  - Router нэгтгэх (merge)
  - Exception handling
  - Edge cases (trailing slashes, URL encoding, Монгол үсэг)

- **CallbackTest.php** - Callback классын тестүүд:
  - Callback үүсгэх (Closure, function, array)
  - Параметрүүд set/get хийх
  - Олон төрлийн өгөгдлийн төрөл

### Тест тохиргоо

Тест тохиргоо нь `phpunit.xml` файлд байрлана. Энэ файл нь:
- Test suite-ийг тодорхойлно
- Coverage тохиргоог агуулна
- Autoload-ийг тохируулна

---

## 📄 Лиценз

Энэ төсөл MIT лицензтэй.

---

## 👨‍💻 Зохиогч

Narankhuu  
📧 codesaur@gmail.com  
📲 [+976 99000287](https://wa.me/97699000287)  
🌐 https://github.com/codesaur  

---

## 🤝 Хөгжүүлэлтэд хувь нэмэр оруулах

Pull request буюу code засвар, сайжруулалтыг хэзээд нээлттэй хүлээж авна.  

**Хувь нэмэр оруухаас өмнө:**
- Тестүүдийг ажиллуулж бүх тест амжилттай байгаа эсэхийг шалгана
- Шинэ функц нэмсэн бол шинэ тест нэмнэ
- PHPDoc тайлбарыг шинэчлэнэ

Bug report илгээхдээ системийн орчны мэдээллээ давхар бичиж өгнө үү.
