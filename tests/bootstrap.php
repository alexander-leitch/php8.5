<?php

declare(strict_types=1);

// Set the database path to in-memory for tests
$_ENV['DB_PATH'] = ':memory:';

// Load all source files
require_once __DIR__ . '/../html/Database.php';
require_once __DIR__ . '/../html/Todo.php';
require_once __DIR__ . '/../html/TodoService.php';
