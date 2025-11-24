<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for Database class
 * Tests singleton pattern and database initialization
 */
class DatabaseTest extends TestCase
{
    private static ?\PDO $originalInstance = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the Database singleton between tests
        $reflection = new \ReflectionClass(\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        self::$originalInstance = $instance->getValue();
        $instance->setValue(null);
    }

    protected function tearDown(): void
    {
        // Restore original instance
        $reflection = new \ReflectionClass(\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(self::$originalInstance);

        parent::tearDown();
    }

    #[Test]
    public function it_returns_pdo_instance(): void
    {
        $db = \Database::getInstance();

        $this->assertInstanceOf(\PDO::class, $db);
    }

    #[Test]
    public function it_implements_singleton_pattern(): void
    {
        $db1 = \Database::getInstance();
        $db2 = \Database::getInstance();

        $this->assertSame($db1, $db2);
    }

    #[Test]
    public function it_uses_memory_database_in_test_environment(): void
    {
        $_ENV['DB_PATH'] = ':memory:';

        $db = \Database::getInstance();

        $this->assertInstanceOf(\PDO::class, $db);
        $this->assertSame(':memory:', \Database::$dbPath);
    }

    #[Test]
    public function it_sets_error_mode_to_exception(): void
    {
        $db = \Database::getInstance();

        $errorMode = $db->getAttribute(\PDO::ATTR_ERRMODE);

        $this->assertSame(\PDO::ERRMODE_EXCEPTION, $errorMode);
    }

    #[Test]
    public function it_sets_default_fetch_mode_to_associative(): void
    {
        $db = \Database::getInstance();

        $fetchMode = $db->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE);

        $this->assertSame(\PDO::FETCH_ASSOC, $fetchMode);
    }

    #[Test]
    public function it_creates_todos_table(): void
    {
        $db = \Database::getInstance();

        // Check if table exists
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='todos'");
        $tables = $result->fetchAll();

        $this->assertCount(1, $tables);
        $this->assertSame('todos', $tables[0]['name']);
    }

    #[Test]
    public function it_creates_todos_table_with_correct_columns(): void
    {
        $db = \Database::getInstance();

        $result = $db->query("PRAGMA table_info(todos)");
        $columns = $result->fetchAll();

        $columnNames = array_column($columns, 'name');

        $this->assertContains('id', $columnNames);
        $this->assertContains('title', $columnNames);
        $this->assertContains('description', $columnNames);
        $this->assertContains('completed', $columnNames);
        $this->assertContains('priority', $columnNames);
        $this->assertContains('due_date', $columnNames);
        $this->assertContains('created_at', $columnNames);
        $this->assertContains('updated_at', $columnNames);
    }

    #[Test]
    public function it_has_asymmetric_visibility_on_db_path(): void
    {
        $db = \Database::getInstance();

        // Should be able to read the static property
        $this->assertIsString(\Database::$dbPath);

        // Test that it's publicly readable
        $reflection = new \ReflectionClass(\Database::class);
        $property = $reflection->getProperty('dbPath');
        $this->assertTrue($property->isPublic());
    }

    #[Test]
    public function it_adds_due_date_column_if_not_exists(): void
    {
        $db = \Database::getInstance();

        // The column should exist after initialization
        $result = $db->query("PRAGMA table_info(todos)");
        $columns = $result->fetchAll();

        $hasDueDate = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'due_date') {
                $hasDueDate = true;
                break;
            }
        }

        $this->assertTrue($hasDueDate);
    }
}
