# Changelog

This file contains all changes for all versions of the `codesaur/router` package.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [5.0.4] - 2026-01-06
[5.0.4]: https://github.com/codesaur-php/Router/compare/v5.0.3...v5.0.4

### Added
- ✅ Added initial release (v1.0) information to changelog
  
### Fixed
- ✅ Fixed small documentation errors and inconsistencies

---

## [5.0.3] - 2026-01-05
[5.0.3]: https://github.com/codesaur-php/Router/compare/v5.0.2...v5.0.3

### ✨ Added

- **Composer test scripts** - Added `composer test` and `composer test:coverage` commands to composer.json
  - `composer test` - Run all PHPUnit tests
  - `composer test:coverage` - Run tests with code coverage
- **Contributing guide** - `.github/CONTRIBUTING.md` and `.github/SECURITY.md` added

### 🔧 Improved

- **README.md refactoring**
  - Updated project title and description
  - Updated core classes documentation
  - Updated examples to show routing usage
  - Updated installation and quick start guide
- **Documentation improvements**
  - `docs/en/README.md` - Testing section simplified and streamlined
  - `docs/mn/README.md` - Testing section simplified and streamlined
  - Fixed documentation links (api.md, review.md, CHANGELOG.md)
  - Updated ecosystem references

---

## [5.0.2] - 2025-12-26
[5.0.2]: https://github.com/codesaur-php/Router/compare/v5.0.1...v5.0.2

### 🔧 Improved

- **CHANGELOG standard** - Keep a Changelog standard links updated (v1.1.0)
- **Version comparison** - GitHub compare link references added to each version
- **Documentation refactoring** - All *.md documentation files improved with custom style
  - README.md - Structure, example code, and guides improved
  - API.md - API documentation made more detailed
  - REVIEW.md - Code review report improved
  - CHANGELOG.md - Version comparison links added

---

## [5.0.1] - 2025-12-26
[5.0.1]: https://github.com/codesaur-php/Router/compare/v5.0.0...v5.0.1

### ✨ Added

- **English documentation** - English versions of all documentation files
  - README.EN.md - English README
  - API.EN.md - English API documentation
  - REVIEW.EN.md - English code review report
  - CHANGELOG.EN.md - English changelog

### 🔧 Improved

- **Bilingual support** - Links between two language versions added to all documentation files
- **Language switching** - Users can easily switch between Mongolian and English versions

---

## [5.0.0] - 2025-12-17
[5.0.0]: https://github.com/codesaur-php/Router/compare/v1.0...v5.0.0

### ✨ Added

- **CI/CD workflow** - Automated testing using GitHub Actions
  - Tests on PHP 8.2, 8.3, 8.4 versions
  - Tests on Ubuntu and Windows
  - Code coverage measurement
- **API documentation** - API.md file (auto-generated from PHPDoc)
- **Code review report** - REVIEW.md file
- **PHPDoc improvements**
  - `@const` annotation on all constants
  - Method return types more specific (`@return static`)
  - Callable types more detailed
- **Example file improvements**
  - PHPDoc added to all methods
  - Comments made more detailed
- **README.md improvements**
  - Installation guide made more detailed
  - More example code added
  - Router merge, Matching & Dispatching sections made more detailed

### 🔧 Improved

- **PHPDoc standard** - Fully compliant with PSR-5 standard
- **Type safety** - Callable types made more specific
- **Documentation** - All documentation made more detailed and clear

---

## [1.0] - 2021-03-02
[1.0]: https://github.com/codesaur-php/Router/releases/tag/v1.0

### Released
- Initial release
