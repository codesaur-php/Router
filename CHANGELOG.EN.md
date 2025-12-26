# Changelog

**Language:** [🇲🇳 Монгол](CHANGELOG.md) | [🇬🇧 English](CHANGELOG.EN.md)

This file contains all changes for all versions of the `codesaur/router` package.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [5.0.1] - 2025-12-26

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

### 📝 Documentation

- README.md - More examples, detailed guide
- API.md - Detailed documentation of all public APIs
- REVIEW.md - Code review report

---

## Links

- [GitHub Repository](https://github.com/codesaur-php/Router)
- [API Documentation](API.EN.md)
- [Code Review](REVIEW.EN.md)
- [README](README.EN.md)
