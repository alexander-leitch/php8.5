# PHP 8.5 Todo List - Testing Documentation

## Test Suite Overview

The project includes a comprehensive PHPUnit test suite with **46 tests** covering all core functionality and PHP 8.5 features.

---

## Test Statistics

- **Total Tests**: 46
- **Total Assertions**: 127
- **Test Suites**: 2 (Unit + Integration)
- **Code Coverage**: Database, Todo model, TodoService
- **Status**: ✅ All Passing

---

## Running Tests

### Prerequisites
```bash
# Tests run inside the Docker container
docker compose up -d
docker compose exec php composer install
```

### Run All Tests
```bash
docker compose exec php ./vendor/bin/phpunit
```

### Run Tests with Detailed Output
```bash
docker compose exec php ./vendor/bin/phpunit --testdox
```

### Run Tests with Colors
```bash
docker compose exec php ./vendor/bin/phpunit --testdox --colors=always
```

### Run Specific Test Suite
```bash
# Unit tests only
docker compose exec php ./vendor/bin/phpunit --testsuite "Unit Tests"

# Integration tests only
docker compose exec php ./vendor/bin/phpunit --testsuite "Integration Tests"
```

---

## Test Suites

### Unit Tests (26 tests)

#### DatabaseTest (9 tests)
Tests the database singleton implementation and initialization:

- ✅ Returns PDO instance
- ✅ Implements singleton pattern
- ✅ Uses in-memory database in test environment
- ✅ Sets error mode to exception
- ✅ Sets default fetch mode to associative array
- ✅ Creates todos table on initialization
- ✅ Creates todos table with correct columns
- ✅ Demonstrates asymmetric visibility on dbPath property
- ✅ Adds due_date column if not exists (migration)

**PHP 8.5 Features Tested:**
- Asymmetric visibility properties (`public private(set)`)
- `#[\NoDiscard]` attribute on getInstance()

#### TodoTest (17 tests)
Tests the Todo model's business logic and immutability:

- ✅ Can be created with constructor (named parameters)
- ✅ Can be created from array
- ✅ Uses default values when creating from minimal array
- ✅ Can be converted to array
- ✅ Detects overdue tasks correctly
- ✅ Does not mark future tasks as overdue
- ✅ Does not mark completed tasks as overdue
- ✅ Does not mark tasks without due date as overdue
- ✅ Can be marked as completed (immutable clone)
- ✅ Can be marked as incomplete (immutable clone)
- ✅ Can update title (immutable clone)
- ✅ Can update priority (immutable clone)
- ✅ Validates title correctly
- ✅ Formats priority correctly (with data provider)

**PHP 8.5 Features Tested:**
- Constructor property promotion
- Named parameters
- Immutability patterns (clone with modifications)

---

### Integration Tests (20 tests)

#### TodoServiceTest (20 tests)
Tests CRUD operations and PHP 8.5 pipe operator functionality:

- ✅ Can create a todo
- ✅ Can get all todos (ordered by created_at DESC)
- ✅ Can get todo by ID
- ✅ Returns null for non-existent ID
- ✅ Can update a todo
- ✅ Returns null when updating non-existent todo
- ✅ Can delete a  todo
- ✅ Returns false when deleting non-existent todo
- ✅ Can get todos by completion status
- ✅ Calculates statistics correctly
- ✅ Uses array_first() to get newest todo
- ✅ Uses array_last() to get oldest todo
- ✅ Returns empty stats for empty database
- ✅ Groups todos by priority using pipe operator
- ✅ Uses pipe operator for data transformations
- ✅ Preserves overdue status in transformations
- ✅ Handles partial updates
- ✅ Updates timestamp on update
- ✅ Handles null due dates
- ✅ Handles empty descriptions

**PHP 8.5 Features Tested:**
- Pipe operator (`|>`) for data transformations
- `array_first()` function
- `array_last()` function
- Functional programming patterns

---

## Test Configuration

### PHPUnit Configuration (`phpunit.xml`)
```xml
- Bootstrap: tests/bootstrap.php
- Test Suites: Unit Tests, Integration Tests
- Source Directory: src/
- Cache Directory: .phpunit.cache/
- Database: In-memory SQLite (:memory:)
```

### Test Environment
- **PHP Version**: 8.5.0
- **PHPUnit Version**: 11.5.44
- **Database**: SQLite (in-memory for tests)
- **Isolation**: Each test gets a fresh database instance

---

## Code Coverage

The test suite covers:

### Database.php
- ✅ Singleton pattern implementation
- ✅ PDO configuration
- ✅ Table initialization
- ✅ Schema migrations
- ✅ Asymmetric visibility

### Todo.php
- ✅ Constructor and factory methods
- ✅ Overdue detection logic
- ✅ Immutable update methods
- ✅ Validation methods
- ✅ Array conversion

### TodoService.php
- ✅ CRUD operations (Create, Read, Update, Delete)
- ✅ Filtering by status
- ✅ Statistics generation
- ✅ Grouping by priority
- ✅ Pipe operator transformations
- ✅ PHP 8.5 array functions

---

## Writing New Tests

### Unit Test Example
```php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MyTest extends TestCase
{
    #[Test]
    public function it_does_something(): void
    {
        $result = someFunction();
        $this->assertTrue($result);
    }
}
```

### Integration Test Example
```php
class ServiceTest extends TestCase
{
    private \TodoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset database singleton
        $reflection = new \ReflectionClass(\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);
        
        $_ENV['DB_PATH'] = ':memory:';
        $this->service = new \TodoService();
    }

    #[Test]
    public function it_tests_service(): void
    {
        $result = $this->service->getAll();
        $this->assertIsArray($result);
    }
}
```

---

## Continuous Integration

To run tests in CI/CD:

```bash
# GitHub Actions example
docker compose up -d
docker compose exec -T php composer install
docker compose exec -T php ./vendor/bin/phpunit --testdox
```

---

## Test Best Practices

1. **Isolation**: Each test resets the database singleton
2. **Descriptive Names**: Test names describe what they test
3. **Single Responsibility**: Each test focuses on one behavior
4. **Arrange-Act-Assert**: Clear test structure
5. **In-Memory Database**: Fast test execution
6. **Data Providers**: Parametrized tests for multiple scenarios

---

## Troubleshooting

### Tests Fail with "Database not found"
```bash
# Ensure $_ENV['DB_PATH'] = ':memory:' in bootstrap.php
# Check that singleton is reset in setUp()
```

### Tests Pass Individually but Fail Together
```bash
# Database state is persisting between tests
# Add proper tearDown() to clean singleton
```

### Composer Autoload Issues
```bash
docker compose exec php composer dump-autoload
```

---

## Future Test Enhancements

Potential additions to the test suite:

- [ ] API endpoint tests (HTTP requests)
- [ ] Performance benchmarks
- [ ] Code coverage reports
- [ ] Mutation testing
- [ ] Browser-based E2E tests

---

*Last Updated: November 24, 2025*
