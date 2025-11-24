# Project Context & Development Notes

**Project**: PHP 8.5 Todo List Application  
**Created**: November 24, 2025  
**AI Assistant**: Google Antigravity  
**Last Updated**: November 24, 2025

---

## Project Overview

This is a **demonstration application** showcasing PHP 8.5.0alpha1's cutting-edge features through a functional todo list with a premium glassmorphism UI. The project was built entirely through AI-assisted development using 3 simple prompts.

### Primary Goals
1. Demonstrate PHP 8.5 features in a real application
2. Showcase modern web design aesthetics
3. Provide production-ready code with comprehensive tests
4. Serve as a reference implementation for PHP 8.5 adoption

---

## Architecture Overview

### Technology Stack
- **Backend**: PHP 8.5.0alpha1 (CLI mode, built-in server)
- **Database**: SQLite 3 (file-based, single file)
- **Frontend**: Vanilla HTML/CSS/JavaScript (no frameworks)
- **Testing**: PHPUnit 11.5.44
- **Containerization**: Docker with Alpine base image
- **Dependency Management**: Composer

### Design Pattern: Service Layer Architecture

```
┌─────────────────┐
│   Frontend      │ ← HTML/CSS/JS (index.html, styles.css)
│   (Browser)     │
└────────┬────────┘
         │ HTTP Requests
         ↓
┌─────────────────┐
│   API Layer     │ ← api.php (RESTful endpoints)
│   (api.php)     │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ Service Layer   │ ← TodoService.php (Business logic)
│ (TodoService)   │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  Model Layer    │ ← Todo.php (Domain model)
│   (Todo)        │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ Data Layer      │ ← Database.php (Singleton PDO wrapper)
│  (Database)     │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│    SQLite       │ ← todos.db (Persistent storage)
└─────────────────┘
```

---

## PHP 8.5 Features Implementation

### 1. Pipe Operator (`|>`)
**Location**: `src/TodoService.php` lines 31-33, 46-48

```php
return $rows
    |> (fn($data) => array_map(fn($row) => Todo::fromArray($row), $data))
    |> (fn($todos) => array_map(fn($todo) => $todo->toArray(), $todos));
```

**Purpose**: Functional data transformation chains  
**Note**: Uses parenthesized arrow functions for proper pipeline syntax

### 2. Array Functions (`array_first()`, `array_last()`)
**Location**: `src/TodoService.php` lines 64, 66

```php
'first_todo' => array_first($todos),
'last_todo' => array_last($todos),
```

**Purpose**: Access first and last array elements elegantly  
**Note**: Works on arrays ordered DESC by created_at

### 3. Asymmetric Visibility
**Location**: `src/Database.php` line 15

```php
public private(set) static string $dbPath;
```

**Purpose**: Public read access, private write access  
**Note**: Demonstrates PHP 8.5's asymmetric property visibility

### 4. NoDiscard Attribute
**Location**: `src/Database.php` line 21

```php
#[\NoDiscard]
public static function getInstance(): PDO
```

**Purpose**: Ensures return value is used (prevents silent failures)  
**Note**: Applied to critical factory methods

### 5. Other Modern PHP Features
- Constructor property promotion (Todo.php)
- Named parameters throughout
- Readonly properties concept (via immutable methods)
- Strict types (`declare(strict_types=1)` in all files)

---

## Key Design Decisions

### 1. Immutability Pattern in Todo Model
**Decision**: All update methods return new instances (clone)  
**Rationale**: Thread-safe, predictable state changes, easier to test  
**Example**: `markAsCompleted()`, `updateTitle()`, `updatePriority()`

**Note for future**: This mirrors functional programming patterns. If adding more complex operations, consider a builder pattern.

### 2. Singleton Database Connection
**Decision**: Use singleton pattern for Database class  
**Rationale**: Single connection for simple app, easy to mock in tests  
**Trade-off**: Not thread-safe (acceptable for demo, PHP-FPM would create separate processes)

**Note for future**: If scaling, consider dependency injection with connection pooling.

### 3. File-Based SQLite
**Decision**: Use SQLite with file persistence  
**Rationale**: Zero configuration, portable, sufficient for demo  
**Location**: `/var/www/database/todos.db` in container

**Note for future**: Migration to PostgreSQL/MySQL would require:
- Update Database.php DSN
- Adjust auto-increment handling
- Update docker-compose.yml

### 4. RESTful API Design
**Endpoints**:
- `GET /api/todos` - List all
- `POST /api/todos` - Create
- `PUT /api/todos/{id}` - Update
- `DELETE /api/todos/{id}` - Delete
- `GET /api/todos/stats` - Statistics

**Decision**: Simple routing via URL parsing  
**Note for future**: Consider Slim/Laravel if adding auth or complex routing

### 5. No Create with Completed=true
**Implementation**: TodoService::create() always sets completed=false  
**Rationale**: Business rule - todos start incomplete  
**Workaround**: Use create() then update() to mark as completed

**Note for future**: If this becomes common, add createCompleted() method

---

## Testing Strategy

### Test Isolation
**Critical**: Each test resets the Database singleton with in-memory SQLite

```php
protected function setUp(): void
{
    // Force reset singleton
    $reflection = new \ReflectionClass(\Database::class);
    $instance = $reflection->getProperty('instance');
    $instance->setAccessible(true);
    $instance->setValue(null);
    
    $_ENV['DB_PATH'] = ':memory:';
    $this->service = new \TodoService();
}
```

**Gotcha**: Without this, tests share state and fail randomly!

### Test Naming Convention
Pattern: `it_<describes_behavior_in_plain_english>()`  
Example: `it_can_create_a_todo()`, `it_detects_overdue_tasks()`

### Data Providers
Used for testing multiple scenarios with same logic  
Example: `priorityFormattingProvider()` in TodoTest

### Ordering Assumptions
**Important**: `getAll()` returns todos in DESC order by created_at  
- `array_first()` = newest todo
- `array_last()` = oldest todo

Tests need to account for this when checking order.

---

## Docker & Environment

### Container Structure
```
php85-todo-app (container)
├── /var/www/
│   ├── html/          ← Mapped from ./src
│   ├── database/      ← Mapped from ./database
│   ├── tests/         ← Mapped from ./tests
│   ├── composer.json  ← Mapped from ./
│   ├── phpunit.xml    ← Mapped from ./
│   └── vendor/        ← Generated by composer install
```

### Port Mapping
- Host `8080` → Container `8080`
- Access: http://localhost:8080

### Environment Variables
- `DB_PATH`: Default `/var/www/database/todos.db`, override in tests to `:memory:`

### Composer in Container
The Dockerfile installs Composer globally at `/usr/local/bin/composer`

To install dependencies:
```bash
docker compose exec php composer install
```

---

## Database Schema

### todos Table
```sql
CREATE TABLE todos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    completed INTEGER DEFAULT 0,      -- Boolean as 0/1
    priority TEXT DEFAULT 'medium',   -- 'low', 'medium', 'high'
    due_date TEXT,                    -- ISO 8601 format YYYY-MM-DD
    created_at TEXT NOT NULL,         -- DateTime string
    updated_at TEXT NOT NULL          -- DateTime string
)
```

### Schema Migration
The `addDueDateColumnIfNeeded()` method in Database.php handles the due_date migration.  
This runs on every getInstance() call - safe but could be optimized with a migration flag.

**Note for future**: Consider a proper migration system (Phinx, Doctrine Migrations) if schema evolves further.

---

## UI/UX Design

### Glassmorphism Theme
**Characteristics**:
- Frosted glass effect (`backdrop-filter: blur()`)
- Semi-transparent backgrounds (`rgba()`)
- Gradient backdrops
- Soft shadows
- Smooth animations

**Key CSS Classes**:
- `.todo-container` - Main glassmorphism card
- `.todo-item` - Individual todo cards
- `.glass-button` - Styled buttons
- `.overdue` - Red highlight for overdue items

### Color Palette
- **Background Gradient**: Purple (#667eea) to Orange (#f093fb)
- **Glass Background**: White with 10% opacity
- **Primary Accent**: Blue (#4f46e5)
- **Danger/Overdue**: Red (#ef4444)
- **Success**: Green (#10b981)

### Responsive Design
The layout adapts for mobile with flexbox. Max width 600px for todo container.

**Note for future**: Consider adding breakpoints for tablet sizes.

---

## Common Development Tasks

### Adding a New Todo Property

1. **Update Database Schema**
   ```php
   // In Database.php::initializeDatabase()
   ALTER TABLE todos ADD COLUMN new_field TYPE
   ```

2. **Update Todo Model**
   ```php
   // In Todo.php constructor
   public function __construct(
       // ... existing params
       public ?string $new_field,
   )
   ```

3. **Update TodoService**
   ```php
   // In create() and update() methods
   'new_field' => $data['new_field'] ?? null,
   ```

4. **Update Frontend**
   - Add input in index.html
   - Update createTodo() and updateTodo() in JavaScript
   - Add display logic in renderTodos()

5. **Add Tests**
   - Unit test in TodoTest
   - Integration test in TodoServiceTest

### Adding a New API Endpoint

1. **Update api.php**
   ```php
   if ($method === 'GET' && $path === '/api/new-endpoint') {
       // Implementation
   }
   ```

2. **Add Service Method** (if needed)
   ```php
   // In TodoService.php
   public function newMethod(): array { }
   ```

3. **Add Tests**
   ```php
   #[Test]
   public function it_handles_new_endpoint(): void { }
   ```

4. **Update README** with new endpoint documentation

---

## Known Limitations & Trade-offs

### 1. PHP 8.5 is Alpha
**Impact**: Not production-ready for real applications  
**Mitigation**: Containerized to ensure consistent environment  
**Future**: Update to stable PHP 8.5 when released

### 2. Built-in PHP Server
**Current**: `php -S 0.0.0.0:8080`  
**Limitation**: Single-threaded, not for production  
**Future**: Use nginx + PHP-FPM for production deployment

### 3. No Authentication
**Current**: Open API, no user management  
**Future Enhancement**: Add JWT auth, user sessions, per-user todos

### 4. No Input Validation
**Current**: Basic type checking via PHP types  
**Missing**: XSS prevention, SQL injection (PDO helps), input sanitization  
**Future**: Add validation library (Respect\Validation, Symfony Validator)

### 5. No Pagination
**Current**: All todos returned at once  
**Future**: Add limit/offset pagination when todo list grows

### 6. Frontend Framework
**Current**: Vanilla JavaScript  
**Trade-off**: Simple but verbose DOM manipulation  
**Future**: Consider Vue.js or React for complex UI

---

## Testing Gotchas

### Timestamp Tests
**Issue**: `date('Y-m-d H:i:s')` has 1-second resolution  
**Solution**: Use `sleep(1)` not `usleep(1000)` in timestamp tests  
**Example**: `it_updates_timestamp_on_update()`

### Database Deprecation Warnings
**Issue**: Reading static property in tests triggers deprecation  
**Cause**: Accessing `Database::$dbPath` in certain ways  
**Impact**: Cosmetic only, tests still pass  
**Solution**: Ignore or suppress in phpunit.xml if needed

### SQLite Auto-increment
**Issue**: Auto-increment IDs persist across tests in some scenarios  
**Solution**: Always use in-memory database (`:memory:`) in tests  
**Verification**: Check `$_ENV['DB_PATH']` in setUp()

---

## Future Enhancement Ideas

### High Priority
- [ ] User authentication and multi-user support
- [ ] Todo categories/tags
- [ ] Search and filtering
- [ ] Sorting options (priority, due date, title)
- [ ] Recurring todos

### Medium Priority
- [ ] File attachments
- [ ] Todo subtasks/checklists
- [ ] Comments/notes on todos
- [ ] Activity log/history
- [ ] Export to CSV/JSON

### Low Priority
- [ ] Email notifications for overdue items
- [ ] Calendar integration
- [ ] Dark mode toggle
- [ ] Keyboard shortcuts
- [ ] Drag-and-drop reordering
- [ ] Mobile app (PWA)

### Technical Improvements
- [ ] Upgrade to PHP 8.5 stable when released
- [ ] Add input validation library
- [ ] Implement proper migrations system
- [ ] Add API rate limiting
- [ ] Set up CI/CD pipeline
- [ ] Add code coverage reporting
- [ ] Performance benchmarking
- [ ] Docker production optimization (multi-stage build)

---

## File Structure Reference

```
php8.5/
├── .antigravity/
│   └── CONTEXT.md          ← This file
├── .git/                   ← Git repository
├── .gitignore
├── database/
│   └── todos.db            ← SQLite database file
├── screenshots/
│   └── app_screenshot.png  ← UI screenshot for documentation
├── src/                    ← Application source code
│   ├── Database.php        ← Singleton PDO wrapper
│   ├── Todo.php            ← Domain model (immutable)
│   ├── TodoService.php     ← Business logic (CRUD operations)
│   ├── api.php             ← RESTful API endpoints
│   ├── index.php           ← Frontend entry point
│   ├── index.html          ← Main UI template
│   └── styles.css          ← Glassmorphism styling
├── tests/                  ← PHPUnit test suite
│   ├── Integration/
│   │   └── TodoServiceTest.php  ← Service integration tests (20 tests)
│   ├── Unit/
│   │   ├── DatabaseTest.php     ← Database unit tests (9 tests)
│   │   └── TodoTest.php         ← Todo model unit tests (17 tests)
│   └── bootstrap.php       ← Test initialization
├── vendor/                 ← Composer dependencies (gitignored)
├── composer.json          ← Dependency definitions
├── composer.lock          ← Locked dependency versions (gitignored)
├── docker-compose.yml     ← Container orchestration
├── Dockerfile             ← PHP 8.5 container definition
├── phpunit.xml            ← PHPUnit configuration
├── README.md              ← Main documentation
├── STATUS.md              ← Current project status with metrics
└── TESTING.md             ← Testing documentation
```

---

## Important Commands Reference

### Docker
```bash
# Start application
docker compose up -d

# Stop application
docker compose down

# Rebuild containers
docker compose up --build -d

# View logs
docker compose logs -f php

# Shell access
docker compose exec php sh
```

### Testing
```bash
# Install dependencies
docker compose exec php composer install

# Run all tests
docker compose exec php ./vendor/bin/phpunit

# Run with detailed output
docker compose exec php ./vendor/bin/phpunit --testdox --colors=always

# Run specific suite
docker compose exec php ./vendor/bin/phpunit --testsuite "Unit Tests"
docker compose exec php ./vendor/bin/phpunit --testsuite "Integration Tests"
```

### Git
```bash
# Check status
git status

# View changes
git diff

# Commit changes
git add -A
git commit -m "Description"

# View history
git log --oneline
```

---

## Development History Timeline

### Session 1: Initial Implementation (Nov 24, ~2 hours)
- Created PHP 8.5 Docker environment
- Implemented Todo model with PHP 8.5 features
- Built TodoService with pipe operator
- Created Database singleton with asymmetric visibility
- Designed glassmorphism UI
- Set up RESTful API
- Created initial documentation

### Session 2: Due Date Feature (Nov 24, ~30 mins)
- Added due_date field to schema
- Implemented overdue detection in Todo model
- Updated UI to show due dates and overdue status
- Added visual indicators for overdue items
- Updated tests and documentation

### Session 3: Testing Infrastructure (Nov 24, ~1.5 hours)
- Added Composer with PHPUnit 11.5
- Created phpunit.xml configuration
- Implemented 46 comprehensive tests (9 Database, 17 Todo, 20 TodoService)
- Fixed test isolation issues
- Created TESTING.md documentation
- Generated STATUS.md with application screenshots
- Updated README with testing information

---

## Critical Notes for AI Assistants

### When Modifying This Project

1. **Always maintain PHP 8.5 feature usage** - This is a demo project
2. **Run tests after any change** - Test suite must remain at 100% pass rate
3. **Update documentation** - Keep README, TESTING.md, STATUS.md in sync
4. **Preserve immutability** - Todo model methods should clone, not mutate
5. **Maintain glassmorphism aesthetic** - UI changes should preserve the premium feel
6. **Check test isolation** - Database singleton must reset in test setUp()

### Before Making Breaking Changes

1. Review this CONTEXT.md file
2. Check existing tests for affected functionality  
3. Consider backward compatibility
4. Update both code and documentation
5. Verify all tests still pass
6. Update CONTEXT.md if architecture changes

### Code Style Guidelines

- **Strict types**: Every PHP file starts with `declare(strict_types=1);`
- **Descriptive names**: Methods and variables should be self-documenting
- **PHPDoc comments**: Add for complex logic
- **Line length**: Keep under 120 characters
- **Indentation**: 4 spaces (no tabs)
- **Braces**: K&R style (opening brace on same line)

---

## Contact & Collaboration

This project was created by **Google Antigravity** as a demonstration of AI-assisted development.

For questions about:
- **PHP 8.5 features**: Check PHP 8.5 documentation
- **Testing**: See TESTING.md
- **Architecture**: Review this CONTEXT.md file
- **Usage**: See README.md

---

**Last Updated**: November 24, 2025  
**Version**: 1.0.0  
**Status**: Stable, All Tests Passing ✅
