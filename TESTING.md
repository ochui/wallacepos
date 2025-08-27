# FreePOS Testing Guide

This document describes the testing infrastructure and practices for the FreePOS project.

## Overview

FreePOS now includes comprehensive testing infrastructure for both PHP backend and JavaScript frontend components:

- **PHP Testing**: PHPUnit 10.x with Mockery for mocking
- **JavaScript Testing**: Jest with jsdom environment for DOM testing
- **Test Coverage**: HTML and text coverage reports for both languages

## Quick Start

### Running All Tests

```bash
# Run PHP tests
composer test

# Run JavaScript tests
npm test

# Run tests with coverage
composer test-coverage
npm run test:coverage
```

### Test Structure

```
tests/
├── bootstrap.php           # PHPUnit bootstrap file
├── Unit/                  # Unit tests
│   ├── Models/           # Tests for data models
│   └── Database/         # Tests for database models
├── Feature/              # Feature/integration tests
│   └── Controllers/      # Tests for API controllers
└── JavaScript/           # JavaScript tests
    ├── setup.js         # Jest setup file
    ├── utilities.test.js # Tests for utility functions
    └── sales.test.js    # Tests for sales logic
```

## PHP Testing

### Dependencies

- **PHPUnit**: Main testing framework
- **Mockery**: Mocking library for dependencies

### Configuration

Tests are configured via `phpunit.xml`:

- Unit tests in `tests/Unit/`
- Feature tests in `tests/Feature/`
- Source code coverage from `app/` directory
- HTML coverage reports in `coverage/html/`

### Available Commands

```bash
# Run all PHP tests
composer test

# Run only unit tests
composer test-unit

# Run only feature tests
composer test-feature

# Generate coverage report
composer test-coverage
```

### Writing PHP Tests

Example unit test:

```php
<?php

namespace Tests\Unit\Models;

use App\Models\StoredItem;
use PHPUnit\Framework\TestCase;

class StoredItemTest extends TestCase
{
    public function testConstructorWithValidData()
    {
        $data = ['code' => 'TEST001', 'name' => 'Test Item'];
        $item = new StoredItem($data);
        
        $this->assertEquals('TEST001', $item->code);
        $this->assertEquals('Test Item', $item->name);
    }
}
```

Example feature test with mocking:

```php
<?php

namespace Tests\Feature\Controllers;

use PHPUnit\Framework\TestCase;
use Mockery;

class MyControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function testControllerMethod()
    {
        $mock = Mockery::mock(SomeClass::class);
        $mock->shouldReceive('method')->andReturn('result');
        
        // Test logic here
    }
}
```

## JavaScript Testing

### Dependencies

- **Jest**: Main testing framework
- **@testing-library/jest-dom**: Additional DOM matchers
- **jest-environment-jsdom**: DOM environment for tests

### Configuration

Tests are configured via `package.json` Jest configuration:

- Test files in `tests/JavaScript/`
- jsdom environment for DOM testing
- Coverage reports in `coverage/javascript/`

### Available Commands

```bash
# Run all JavaScript tests
npm test

# Run tests in watch mode
npm run test:watch

# Generate coverage report
npm run test:coverage

# Generate coverage in watch mode
npm run test:coverage-watch
```

### Writing JavaScript Tests

Example test file:

```javascript
describe('MyFunction', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('should do something', () => {
        const result = myFunction('input');
        expect(result).toBe('expected');
    });
});
```

### Mocking jQuery and POS objects

The test setup automatically mocks jQuery and common POS global objects:

```javascript
// jQuery is automatically mocked
global.$().on.toHaveBeenCalled();

// POS objects are mocked
global.POS.util.currencyFormat(123.45);
```

## Test Coverage

Both PHP and JavaScript generate coverage reports:

### PHP Coverage
- HTML: `coverage/html/index.html`
- Text: `coverage/coverage.txt`
- JUnit XML: `coverage/junit.xml`

### JavaScript Coverage
- HTML: `coverage/javascript/lcov-report/index.html`
- LCOV: `coverage/javascript/lcov.info`

## Testing Best Practices

### General Guidelines

1. **Write tests first** when adding new features
2. **Test edge cases** and error conditions
3. **Keep tests isolated** - each test should be independent
4. **Use descriptive test names** that explain what is being tested
5. **Mock external dependencies** to keep tests fast and reliable

### PHP Guidelines

1. Use Mockery for mocking dependencies
2. Test both success and failure scenarios
3. Always call `Mockery::close()` in `tearDown()`
4. Use data providers for testing multiple scenarios

### JavaScript Guidelines

1. Mock browser APIs and jQuery when testing POS code
2. Test DOM manipulation separately from business logic
3. Use `beforeEach()` to reset mocks
4. Group related tests using `describe()` blocks

## Continuous Integration

The testing infrastructure is designed to work with CI/CD pipelines:

```bash
# Install dependencies
composer install --no-dev
npm ci

# Run tests
composer test
npm test

# Generate coverage for CI
composer test-coverage
npm run test:coverage
```

## Troubleshooting

### PHP Tests

**Issue**: Tests fail with database connection errors
**Solution**: Use mocking for database-dependent tests, or create a test database

**Issue**: Coverage reports are not generated
**Solution**: Install Xdebug or enable coverage with `XDEBUG_MODE=coverage`

### JavaScript Tests

**Issue**: Tests fail with "window is not defined"
**Solution**: Ensure jest-environment-jsdom is installed and configured

**Issue**: jQuery functions are not mocked
**Solution**: Check that the setup.js file is being loaded

## Adding New Tests

1. Create test files following the naming convention: `*Test.php` or `*.test.js`
2. Place PHP tests in appropriate subdirectories under `tests/`
3. Place JavaScript tests in `tests/JavaScript/`
4. Update this README if adding new testing patterns or tools

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Mockery Documentation](http://docs.mockery.io/)
- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Testing Library Jest DOM](https://github.com/testing-library/jest-dom)