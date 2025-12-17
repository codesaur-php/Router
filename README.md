# 🦖 codesaur/router  

[![CI](https://github.com/codesaur-php/Router/actions/workflows/ci.yml/badge.svg)](https://github.com/codesaur-php/Router/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

PHP 8.2+ дээр ажиллах хөнгөн, хурдан, объект-суурьтай маршрутчиллын (routing) компонент

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

```bash
composer require codesaur/router
```

---

## Quick Start

```php
use codesaur\Router\Router;
use codesaur\Router\Callback;

$router = new Router();

$router->GET('/hello/{firstname}', function ($firstname) {
    echo "Hello $firstname!";
});
```

Request:

```http
GET /hello/Narankhuu
```

Output:

```text
Hello Narankhuu!
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

```php
$callback = $router->match("/insert/data", "POST");

if ($callback instanceof Callback) {
    $callable = $callback->getCallable();
    $params = $callback->getParameters();
    call_user_func_array($callable, $params);
}
```

---

## Example Project

`example/example.php` файл нь бүх функцүүдийг нэг дор харуулна:

- GET/POST маршрут  
- Controller класстай ажиллах  
- Параметрийн төрөл шалгах  
- URL generate тест  
- Гүйцэтгэл тест (Performance Test)
- Автомат base-path support  

---

## Router Merge

```php
$router->merge($moduleRouter);
```

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

- 📚 **[API.md](API.md)** - Бүх public API-ийн дэлгэрэнгүй тайлбар, method-ууд, parameter-ууд, exception-ууд (Cursor AI)
- 🔍 **[REVIEW.md](REVIEW.md)** - Код шалгалтын тайлан, давуу талууд, сайжруулах боломжууд  (Cursor AI)

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

Эсвэл:
```cmd
php vendor\bin\phpunit
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

## Requirements

- PHP 8.2.1+  
- Composer

---

## Credits

**Narankhuu**  
📧 codesaur@gmail.com  
📱 +976 99000287  
🌐 https://github.com/codesaur  

---

## License

MIT License
