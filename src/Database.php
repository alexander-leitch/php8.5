<?php

declare(strict_types=1);

/**
 * Database connection and initialization
 * Demonstrates PHP 8.5's URI extension for handling database URIs
 */
class Database
{
    private static ?PDO $instance = null;

    // PHP 8.5: Asymmetric visibility for static properties
    // Public read, private write
    public private(set) static string $dbPath;

    private function __construct()
    {
    }

    #[\NoDiscard] // PHP 8.5: NoDiscard attribute - ensures return value is used
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$dbPath = $_ENV['DB_PATH'] ?? '/var/www/database/todos.db';

            self::$instance = new PDO('sqlite:' . self::$dbPath);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            self::initializeDatabase();
        }

        return self::$instance;
    }

    private static function initializeDatabase(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS todos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            completed INTEGER DEFAULT 0,
            priority TEXT DEFAULT 'medium',
            due_date TEXT,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )
        SQL;

        self::$instance->exec($sql);

        // Migration: Add due_date column if it doesn't exist
        self::addDueDateColumnIfNeeded();
    }

    private static function addDueDateColumnIfNeeded(): void
    {
        $result = self::$instance->query("PRAGMA table_info(todos)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);

        $hasDueDate = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'due_date') {
                $hasDueDate = true;
                break;
            }
        }

        if (!$hasDueDate) {
            self::$instance->exec("ALTER TABLE todos ADD COLUMN due_date TEXT");
        }
    }
}
