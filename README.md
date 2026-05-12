# codesaur/router

[![CI](https://github.com/codesaur-php/Router/actions/workflows/ci.yml/badge.svg)](https://github.com/codesaur-php/Router/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2.1-777BB4.svg?logo=php)](https://www.php.net/)
![License](https://img.shields.io/badge/License-MIT-green.svg)

## Агуулга / Table of Contents

1. [Монгол](#1-монгол-тайлбар) | 2. [English](#2-english-description) | 3. [Getting Started](#3-getting-started)

---

## 1. Монгол тайлбар

Хөнгөн, хурдан, объект-суурьтай маршрутчиллын (routing) компонент. Динамик параметрүүд, нэртэй маршрутууд, reverse routing зэрэг бүх шаардлагатай боломжуудыг дэмждэг.

`codesaur/router` нь **codesaur ecosystem**-ийн нэг хэсэг бөгөөд хөнгөн жинтэй,
фрэймворкоос үл хамааран standalone байдлаар ашиглаж болох PHP routing компонент юм.

Багц нь дараах 3 үндсэн class-аас бүрдэнэ:

- **Router** - маршрут бүртгэх, тааруулах, URL үүсгэх үндсэн класс  
- **RouterInterface** - router хэрэгжүүлэх шаардлагуудыг тодорхойлсон интерфэйс  
- **Callback** - маршрутын callback болон параметрүүдийг хадгалах wrapper класс  

### Дэлгэрэнгүй мэдээлэл

- [Бүрэн танилцуулга](docs/mn/README.md) - Суурилуулалт, хэрэглээ, жишээнүүд
- [API тайлбар](docs/mn/api.md) - Бүх метод, exception-үүдийн тайлбар
- [Шалгалтын тайлан](docs/mn/review.md) - Код шалгалтын тайлан

---

## 2. English description

A lightweight, fast, object-oriented routing component. Supports dynamic parameters, named routes, reverse routing, and all essential routing features.

`codesaur/router` is part of the **codesaur ecosystem** and is a lightweight PHP routing component that can be used standalone, independent of any framework.

The package consists of the following 3 core classes:

- **Router** - main class for registering routes, matching requests, and generating URLs  
- **RouterInterface** - interface defining the requirements for router implementations  
- **Callback** - wrapper class for storing route callbacks and their parameters  

### Documentation

- [Full Documentation](docs/en/README.md) - Installation, usage, examples
- [API Reference](docs/en/api.md) - Complete API documentation
- [Review](docs/en/review.md) - Code review report

---

## 3. Getting Started

### Requirements

- PHP **8.2.1+**
- Composer

### Installation

Composer ашиглан суулгана / Install via Composer:

```bash
composer require codesaur/router
```

### Quick Example

```php
use codesaur\Router\Router;

$router = new Router();

// Энгийн GET маршрут / Simple GET route
$router->GET('/hello', function() {
    echo 'Hello, World!';
});

// Динамик параметртэй маршрут / Route with dynamic parameters
$router->GET('/news/{int:id}', function(int $id) {
    echo "News ID: $id";
})->name('news-view');

// UTF-8 параметр / UTF-8 parameter (Cyrillic, CJK, etc.)
$router->GET('/search/{utf8:query}', function(string $query) {
    echo "Search: $query";
})->name('search');

// Маршрут тааруулах / Match route
$callback = $router->match('/news/10', 'GET');
if ($callback) {
    $callable = $callback->getCallable();
    $params = $callback->getParameters();
    \call_user_func_array($callable, $params);
}

// URL үүсгэх / Generate URL
$url = $router->generate('news-view', ['id' => 10]); // -> /news/10

// Client-side substitution-д бэлэн pattern / Pattern for client-side substitution
$pattern = $router->pattern('news-view'); // -> /news/{id}
```

### Running Tests

Тест ажиллуулах / Run tests:

```bash
# Бүх тестүүдийг ажиллуулах / Run all tests
composer test

# Coverage-тэй тест ажиллуулах / Run tests with coverage
composer test:coverage
```

---

## Changelog

- [CHANGELOG.md](CHANGELOG.md) - Full version history

## Contributing & Security

- [Contributing Guide](.github/CONTRIBUTING.md)
- [Security Policy](.github/SECURITY.md)

## License

This project is licensed under the MIT License.

## Author

**Narankhuu**  
 codesaur@gmail.com  
 https://github.com/codesaur

 **codesaur ecosystem:** https://codesaur.net
