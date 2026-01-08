# Changelog

This file contains all changes for all versions of the `codesaur/router` package.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [5.0.0] - 2026-01-08
[5.0.0]: https://github.com/codesaur-php/Router/compare/v4.0...v5.0.0

### ✨ Added

- **CI/CD workflow** - Automated testing using GitHub Actions
  - Tests on PHP 8.2, 8.3, 8.4 versions
  - Tests on Ubuntu and Windows
  - Code coverage measurement
- **API documentation** - API.md file (auto-generated from PHPDoc)
- **Code review report** - REVIEW.md file
- **Comprehensive PHPDoc** - Full documentation for all classes, methods, and properties
  - `@const` annotation on all constants
  - Method return types more specific (`@return static`)
  - Callable types more detailed (`callable|array{class-string, string}`)
  - Parameter type hints with array syntax (`array<string, mixed>`)
- **Return type hints** - Added to all methods
  - `match()` returns `Callback|null`
  - `generate()` returns `string` (throws exception instead of returning null)
- **Type safety improvements**
  - Property type declarations (`protected array $routes = []`)
  - Return type declarations on all methods
  - Better type checking in method signatures
- **Enhanced merge() method** - Now also merges `name_patterns` from Router instances
- **Example file improvements**
  - PHPDoc added to all methods
  - Comments made more detailed
- **README.md improvements**
  - Installation guide made more detailed
  - More example code added
  - Router merge, Matching & Dispatching sections made more detailed
  - CI/CD badges added

### 🔧 Improved

- **PHP version requirement** - Upgraded from PHP 7.2+ to **PHP 8.2.1+**
- **Modern PHP syntax** - Switched from `array()` to `[]` array syntax
- **PHPDoc standard** - Fully compliant with PSR-5 standard
- **Error handling** - `generate()` now throws `OutOfRangeException` instead of returning null
- **Code structure** - Better organization and readability
- **Documentation** - All documentation made more detailed and clear
- **Type safety** - Callable types made more specific
- **Pattern matching** - Direct pattern comparison for exact matches (performance improvement)

### 🗑️ Removed

- **UTF8 parameter support** - Removed `utf8:` parameter type that was in v4.0
- **Legacy code** - Removed old PHP 7.2 compatible syntax

---

## [4.0] - 2021-10-06
[4.0]: https://github.com/codesaur-php/Router/compare/v1.0...v4.0

### ✨ Added

- **Callback class** - Introduced separate `Callback` class to wrap callable and parameters
  - Replaces Route class approach from v1.0
  - Stores callable and route parameters separately
- **Simplified routing structure** - Routes stored as associative array with pattern as key
  - Structure: `[pattern => [method => Callback]]`
  - More efficient route lookup
- **UTF8 parameter support** - Added `utf8:` parameter type for UTF-8 encoded strings
  - Example: `/news/{utf8:title}`
  - Automatically URL decodes UTF-8 parameters
- **RouterInterface improvements** - Expanded interface with new methods
  - Added `getRoutes()` method requirement
  - Added `merge()` method requirement
- **Route naming system** - Enhanced name-based routing
  - `name_patterns` array maps route names to patterns
  - Better reverse routing support

### 🔧 Improved

- **Architecture simplification** - Removed Route class, simplified to Callback-based approach
- **Route matching** - Returns `Callback` object directly instead of `Route`
- **Parameter parsing** - Better type conversion for int, uint, and float parameters
- **Pattern regex generation** - Improved `getPatternRegex()` method
  - URL encodes static path parts
  - Better regex pattern generation
- **Method chaining** - `__call()` returns `&$this` for fluent interface
- **Error handling** - Better exception messages with class context

### 🗑️ Removed

- **Route class** - Completely removed Route class
- **Pipe property** - Removed `_pipe` property for route prefix (present in v1.0)
- **Strict types** - Removed `declare(strict_types=1)` (was in v1.0)
- **HTTP method constants** - Removed `HTTP_REQUEST_METHODS` constant
- **Complex route configuration** - Simplified route registration

### 🔄 Changed

- **match() return type** - Now returns `Callback|null` instead of `Route|null`
- **generate() behavior** - Now throws `OutOfRangeException` instead of returning null
- **Route storage** - Changed from Route objects array to pattern-based associative array
- **Interface methods** - `RouterInterface` methods changed signature

---

## [1.0] - 2021-03-02
[1.0]: https://github.com/codesaur-php/Router/releases/tag/v1.0

### ✨ Added

- **Initial release** - First stable version of codesaur/router
- **Router class** - Main routing class with full routing capabilities
- **Route class** - Separate Route class to encapsulate route information
  - Stores methods, pattern, callback, name, params, and filters
  - Has getter/setter methods for all properties
- **RouterInterface** - Interface defining routing contract
- **Route prefix support** - `_pipe` property for route prefixes
  - Allows setting base path prefix for all routes
- **Dynamic parameter support** - Support for typed route parameters
  - `{int:id}` - Integer parameters (supports negative numbers)
  - `{uint:page}` - Unsigned integer parameters (0 and positive)
  - `{float:price}` - Float parameters
  - `{string:slug}` - String parameters (default)
- **HTTP method support** - Support for all standard HTTP methods
  - GET, POST, PUT, DELETE, PATCH, HEAD, OPTIONS
  - `ANY` method for all HTTP methods
  - Multiple methods per route support
- **Route matching** - `match()` method finds routes by path and method
  - Returns Route object with parameters set
  - Automatic parameter type conversion
- **Reverse routing** - `generate()` method creates URLs from route names
  - Parameter validation and type checking
  - Returns null if route not found (logs error in development mode)
- **Route naming** - `name()` method for naming routes
  - Allows finding routes by name
  - Enables reverse routing
- **Route merging** - `merge()` method to combine multiple routers
  - Useful for modular applications
- **Parameter filters** - Automatic filter assignment based on parameter type
  - Type-specific regex patterns
  - Parameter validation during generation
- **Strict types** - Uses `declare(strict_types=1)` for type safety

### 📋 Technical Details

- **PHP version**: PHP 7.2+ required
- **Array syntax**: Uses `array()` syntax (pre-PHP 5.4 style)
- **Type declarations**: Basic type hints, no return types
- **Route storage**: Array of Route objects
- **Pattern matching**: Regex-based pattern matching with parameter extraction

### 🏗️ Architecture

- **Object-oriented design** - Full OOP with classes and interfaces
- **Separation of concerns** - Route class separate from Router class
- **Extensible** - Interface-based design allows custom implementations
