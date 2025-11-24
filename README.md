# PHP 8.5 Todo List Application

A demonstration of PHP 8.5's new features through a simple todo list application.

## PHP 8.5 Features Demonstrated

1. **Pipe Operator (`|>`)** - For functional data transformations
2. **New Array Functions** - `array_first()` and `array_last()`
3. **Asymmetric Visibility** - For static properties
4. **Closures in Constants** - Using closures in constant expressions
5. **Clone With** - Modifying properties during cloning
6. **`#[\NoDiscard]` Attribute** - For methods that shouldn't have their return values discarded
7. **URI Extension** - For parsing and handling URIs

## Getting Started

### Prerequisites
- Docker
- Docker Compose

### Installation

1. Start the application:
```bash
docker-compose up --build
```

2. Access the application:
```
http://localhost:8080
```

### API Endpoints

- `GET /api/todos` - Get all todos
- `POST /api/todos` - Create a new todo
- `PUT /api/todos/{id}` - Update a todo
- `DELETE /api/todos/{id}` - Delete a todo

## Project Structure

```
php8.5/
├── docker-compose.yml
├── Dockerfile
├── database/          # SQLite database storage
└── src/              # Application source code
    ├── index.php     # Frontend
    ├── api.php       # REST API
    ├── Database.php  # Database layer
    ├── Todo.php      # Todo model
    ├── TodoService.php # Business logic
    └── styles.css    # Styling
```
