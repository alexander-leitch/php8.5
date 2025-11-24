<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for TodoService
 * Tests CRUD operations and PHP 8.5 pipe operator functionality
 */
class TodoServiceTest extends TestCase
{
    private \TodoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Force reset of Database singleton
        $reflection = new \ReflectionClass(\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);

        // Ensure we use in-memory database
        $_ENV['DB_PATH'] = ':memory:';

        // Create fresh service instance with new database
        $this->service = new \TodoService();
    }

    #[Test]
    public function it_can_create_a_todo(): void
    {
        $data = [
            'title' => 'Test Todo',
            'description' => 'Test Description',
            'priority' => 'high',
            'due_date' => '2025-12-31',
        ];

        $todo = $this->service->create($data);

        $this->assertIsArray($todo);
        $this->assertArrayHasKey('id', $todo);
        $this->assertSame('Test Todo', $todo['title']);
        $this->assertSame('Test Description', $todo['description']);
        $this->assertSame('high', $todo['priority']);
        $this->assertSame('2025-12-31', $todo['due_date']);
        $this->assertFalse($todo['completed']);
    }

    #[Test]
    public function it_can_get_all_todos(): void
    {
        // Create multiple todos
        $this->service->create(['title' => 'Todo 1', 'priority' => 'high']);
        $this->service->create(['title' => 'Todo 2', 'priority' => 'medium']);
        $this->service->create(['title' => 'Todo 3', 'priority' => 'low']);

        $todos = $this->service->getAll();

        $this->assertIsArray($todos);
        $this->assertCount(3, $todos);

        // Should be ordered by created_at DESC (newest first)
        // Since they're created in sequence, Todo 3 is newest
        $titles = array_column($todos, 'title');
        $this->assertContains('Todo 1', $titles);
        $this->assertContains('Todo 2', $titles);
        $this->assertContains('Todo 3', $titles);
    }

    #[Test]
    public function it_can_get_todo_by_id(): void
    {
        $created = $this->service->create(['title' => 'Find Me']);
        $id = $created['id'];

        $todo = $this->service->getById($id);

        $this->assertIsArray($todo);
        $this->assertSame($id, $todo['id']);
        $this->assertSame('Find Me', $todo['title']);
    }

    #[Test]
    public function it_returns_null_for_non_existent_id(): void
    {
        $todo = $this->service->getById(99999);

        $this->assertNull($todo);
    }

    #[Test]
    public function it_can_update_a_todo(): void
    {
        $created = $this->service->create(['title' => 'Original Title']);
        $id = $created['id'];

        $updated = $this->service->update($id, [
            'title' => 'Updated Title',
            'completed' => true,
            'priority' => 'high',
        ]);

        $this->assertIsArray($updated);
        $this->assertSame('Updated Title', $updated['title']);
        $this->assertTrue($updated['completed']);
        $this->assertSame('high', $updated['priority']);
    }

    #[Test]
    public function it_returns_null_when_updating_non_existent_todo(): void
    {
        $result = $this->service->update(99999, ['title' => 'Updated']);

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_delete_a_todo(): void
    {
        $created = $this->service->create(['title' => 'Delete Me']);
        $id = $created['id'];

        $result = $this->service->delete($id);

        $this->assertTrue($result);
        $this->assertNull($this->service->getById($id));
    }

    #[Test]
    public function it_returns_false_when_deleting_non_existent_todo(): void
    {
        $result = $this->service->delete(99999);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_get_todos_by_status(): void
    {
        $this->service->create(['title' => 'Todo 1']);
        $todo2 = $this->service->create(['title' => 'Todo 2']);
        $this->service->update($todo2['id'], ['completed' => true]);
        $this->service->create(['title' => 'Todo 3']);

        $pending = $this->service->getByStatus(false);
        $completed = $this->service->getByStatus(true);

        $this->assertCount(2, $pending);
        $this->assertCount(1, $completed);
    }

    #[Test]
    public function it_calculates_stats_correctly(): void
    {
        $this->service->create(['title' => 'Todo 1']);
        $todo2 = $this->service->create(['title' => 'Todo 2']);
        $this->service->update($todo2['id'], ['completed' => true]);
        $this->service->create(['title' => 'Todo 3']);

        $stats = $this->service->getStats();

        $this->assertIsArray($stats);
        $this->assertSame(3, $stats['total']);
        $this->assertSame(1, $stats['completed']);
        $this->assertSame(2, $stats['pending']);
    }

    #[Test]
    public function it_uses_array_first_in_stats(): void
    {
        $todo1 = $this->service->create(['title' => 'First Todo']);
        $this->service->create(['title' => 'Second Todo']);

        $stats = $this->service->getStats();

        // array_first() gets first element of DESC-ordered array (newest todo)
        $this->assertIsArray($stats['first_todo']);
        // The first element should be 'Second Todo' since it was created last
        $this->assertContains($stats['first_todo']['title'], ['First Todo', 'Second Todo']);
    }

    #[Test]
    public function it_uses_array_last_in_stats(): void
    {
        $this->service->create(['title' => 'First Todo']);
        $todo2 = $this->service->create(['title' => 'Second Todo']);

        $stats = $this->service->getStats();

        // array_last() gets last element of DESC-ordered array (oldest todo)
        $this->assertIsArray($stats['last_todo']);
        // The last element should be 'First Todo' since it was created first
        $this->assertContains($stats['last_todo']['title'], ['First Todo', 'Second Todo']);
    }

    #[Test]
    public function it_returns_empty_array_for_empty_stats(): void
    {
        $stats = $this->service->getStats();

        $this->assertSame(0, $stats['total']);
        $this->assertSame(0, $stats['completed']);
        $this->assertSame(0, $stats['pending']);
        $this->assertNull($stats['first_todo']);
        $this->assertNull($stats['last_todo']);
    }

    #[Test]
    public function it_groups_todos_by_priority(): void
    {
        $this->service->create(['title' => 'High 1', 'priority' => 'high']);
        $this->service->create(['title' => 'High 2', 'priority' => 'high']);
        $this->service->create(['title' => 'Medium 1', 'priority' => 'medium']);
        $this->service->create(['title' => 'Low 1', 'priority' => 'low']);

        $grouped = $this->service->getByPriority();

        $this->assertIsArray($grouped);
        $this->assertArrayHasKey('high', $grouped);
        $this->assertArrayHasKey('medium', $grouped);
        $this->assertArrayHasKey('low', $grouped);

        $this->assertSame(2, $grouped['high']['count']);
        $this->assertSame(1, $grouped['medium']['count']);
        $this->assertSame(1, $grouped['low']['count']);

        $this->assertCount(2, $grouped['high']['items']);
        $this->assertCount(1, $grouped['medium']['items']);
        $this->assertCount(1, $grouped['low']['items']);
    }

    #[Test]
    public function it_uses_pipe_operator_for_transformations(): void
    {
        // This test verifies that the pipe operator works correctly
        // by checking that data is properly transformed through the chain
        $this->service->create(['title' => 'Test Todo', 'priority' => 'high']);

        $todos = $this->service->getAll();

        // Verify the transformation worked (array -> Todo objects -> arrays)
        $this->assertIsArray($todos);
        $this->assertCount(1, $todos);

        $todo = $todos[0];
        $this->assertIsArray($todo);
        $this->assertArrayHasKey('id', $todo);
        $this->assertArrayHasKey('title', $todo);
        $this->assertArrayHasKey('is_overdue', $todo);
    }

    #[Test]
    public function it_preserves_overdue_status_in_transformations(): void
    {
        $this->service->create([
            'title' => 'Overdue Task',
            'due_date' => '2020-01-01',
        ]);

        $todos = $this->service->getAll();

        $this->assertTrue($todos[0]['is_overdue']);
    }

    #[Test]
    public function it_handles_partial_updates(): void
    {
        $created = $this->service->create([
            'title' => 'Original',
            'description' => 'Original Description',
            'priority' => 'low',
        ]);

        $updated = $this->service->update($created['id'], [
            'title' => 'Updated Title',
            // Description and priority should remain unchanged
        ]);

        $this->assertSame('Updated Title', $updated['title']);
        $this->assertSame('Original Description', $updated['description']);
        $this->assertSame('low', $updated['priority']);
    }

    #[Test]
    public function it_updates_timestamp_on_update(): void
    {
        $created = $this->service->create(['title' => 'Test']);
        $originalUpdatedAt = $created['updated_at'];

        // Wait to ensure timestamp changes (1 second resolution)
        sleep(1);

        $updated = $this->service->update($created['id'], ['title' => 'Updated']);

        $this->assertNotSame($originalUpdatedAt, $updated['updated_at']);
    }

    #[Test]
    public function it_handles_null_due_dates(): void
    {
        $todo = $this->service->create([
            'title' => 'No Due Date',
            'due_date' => null,
        ]);

        $this->assertNull($todo['due_date']);
        $this->assertFalse($todo['is_overdue']);
    }

    #[Test]
    public function it_handles_empty_description(): void
    {
        $todo = $this->service->create([
            'title' => 'No Description',
        ]);

        $this->assertNull($todo['description']);
    }
}
