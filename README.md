# 🦖 codesaur/router  
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
| `{word}` | `/tag/{word}` | A-z0–9 болон URL-safe тэмдэгтүүд |

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

## Requirements

- PHP 8.2.1+  
- Composer

---

## Credits

**Narankhuu**  
<codesaur@gmail.com>  
+976 99000287 

---

## License

MIT License
