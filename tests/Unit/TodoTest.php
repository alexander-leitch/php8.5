<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for Todo model
 * Tests PHP 8.5 features and business logic
 */
class TodoTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_constructor(): void
    {
        $todo = new \Todo(
            id: 1,
            title: 'Test Task',
            description: 'Test Description',
            completed: false,
            priority: 'high',
            due_date: '2025-12-31',
            created_at: '2025-11-24 10:00:00',
            updated_at: '2025-11-24 10:00:00'
        );

        $this->assertSame(1, $todo->id);
        $this->assertSame('Test Task', $todo->title);
        $this->assertSame('Test Description', $todo->description);
        $this->assertFalse($todo->completed);
        $this->assertSame('high', $todo->priority);
        $this->assertSame('2025-12-31', $todo->due_date);
    }

    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Task',
            'description' => 'Test Description',
            'completed' => true,
            'priority' => 'medium',
            'due_date' => '2025-12-31',
            'created_at' => '2025-11-24 10:00:00',
            'updated_at' => '2025-11-24 10:00:00',
        ];

        $todo = \Todo::fromArray($data);

        $this->assertInstanceOf(\Todo::class, $todo);
        $this->assertSame(1, $todo->id);
        $this->assertSame('Test Task', $todo->title);
        $this->assertTrue($todo->completed);
    }

    #[Test]
    public function it_uses_default_values_when_creating_from_minimal_array(): void
    {
        $data = [
            'title' => 'Minimal Task',
        ];

        $todo = \Todo::fromArray($data);

        $this->assertNull($todo->id);
        $this->assertNull($todo->description);
        $this->assertFalse($todo->completed);
        $this->assertSame('medium', $todo->priority);
        $this->assertNull($todo->due_date);
        $this->assertNotEmpty($todo->created_at);
        $this->assertNotEmpty($todo->updated_at);
    }

    #[Test]
    public function it_can_be_converted_to_array(): void
    {
        $todo = new \Todo(
            id: 1,
            title: 'Test Task',
            description: 'Test Description',
            completed: false,
            priority: 'high',
            due_date: null,
            created_at: '2025-11-24 10:00:00',
            updated_at: '2025-11-24 10:00:00'
        );

        $array = $todo->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('is_overdue', $array);
        $this->assertSame(1, $array['id']);
        $this->assertSame('Test Task', $array['title']);
        $this->assertFalse($array['is_overdue']);
    }

    #[Test]
    public function it_detects_overdue_tasks(): void
    {
        $overdueTodo = new \Todo(
            id: 1,
            title: 'Overdue Task',
            description: null,
            completed: false,
            priority: 'high',
            due_date: '2020-01-01',
            created_at: '2025-11-24 10:00:00',
            updated_at: '2025-11-24 10:00:00'
        );

        $this->assertTrue($overdueTodo->isOverdue());
    }

    #[Test]
    public function it_does_not_mark_future_tasks_as_overdue(): void
    {
        $futureTodo = new \Todo(
            id: 1,
            title: 'Future Task',
            description: null,
            completed: false,
            priority: 'high',
            due_date: '2030-12-31',
            created_at: '2025-11-24 10:00:00',
            updated_at: '2025-11-24 10:00:00'
        );

        $this->assertFalse($futureTodo->isOverdue());
    }

    #[Test]
    public function it_does_not_mark_completed_tasks_as_overdue(): void
    {
        $completedTodo = new \Todo(
            id: 1,
            title: 'Completed Task',
            description: null,
            completed: true,
            priority: 'high',
            due_date: '2020-01-01',
            created_at: '2025-11-24 10:00:00',
            updated_at: '2025-11-24 10:00:00'
        );

        $this->assertFalse($completedTodo->isOverdue());
    }

    #[Test]
    public function it_does_not_mark_tasks_without_due_date_as_overdue(): void
    {
        $noDueDateTodo = new \Todo(
            id: 1,
            title: 'No Due Date',
            description: null,
            completed: false,
            priority: 'high',
            due_date: null,
            created_at: '2025-11-24 10:00:00',
            updated_at: '2025-11-24 10:00:00'
        );

        $this->assertFalse($noDueDateTodo->isOverdue());
    }

    #[Test]
    public function it_can_be_marked_as_completed(): void
    {
        $todo = new \Todo(
            id: 1,
            title: 'Task',
            description: null,
            completed: false,
            priority: 'medium',
            due_date: null,
            created_at: '2025-11-24 10:00:00',
            updated_at: '2025-11-24 10:00:00'
        );

        $completedTodo = $todo->markAsCompleted();

        // Original should be unchanged (immutability)
        $this->assertFalse($todo->completed);

        // New instance should be completed
        $this->assertTrue($completedTodo->completed);
        $this->assertNotSame($todo->updated_at, $completedTodo->updated_at);
    }

    #[Test]
    public function it_can_be_marked_as_incomplete(): void
    {
        $todo = new \Todo(
            id: 1,
            title: 'Task',
            description: null,
            completed: true,
            priority: 'medium',
            due_date: null,
            created_at: '2025-11-24 10:00:00',
            updated_at: '2025-11-24 10:00:00'
        );

        $incompleteTodo = $todo->markAsIncomplete();

        // Original should be unchanged
        $this->assertTrue($todo->completed);

        // New instance should be incomplete
        $this->assertFalse($incompleteTodo->completed);
    }

    #[Test]
    public function it_can_update_title(): void
    {
        $todo = new \Todo(
            id: 1,
            title: 'Old Title',
            description: null,
            completed: false,
            priority: 'medium',
            due_date: null,
            created_at: '2025-11-24 10:00:00',
            updated_at: '2025-11-24 10:00:00'
        );

        $updatedTodo = $todo->updateTitle('New Title');

        $this->assertSame('Old Title', $todo->title);
        $this->assertSame('New Title', $updatedTodo->title);
    }

    #[Test]
    public function it_can_update_priority(): void
    {
        $todo = new \Todo(
            id: 1,
            title: 'Task',
            description: null,
            completed: false,
            priority: 'low',
            due_date: null,
            created_at: '2025-11-24 10:00:00',
            updated_at: '2025-11-24 10:00:00'
        );

        $updatedTodo = $todo->updatePriority('high');

        $this->assertSame('low', $todo->priority);
        $this->assertSame('high', $updatedTodo->priority);
    }

    #[Test]
    public function it_validates_title_correctly(): void
    {
        $this->assertTrue(\Todo::validateTitle('Valid Title'));
        $this->assertTrue(\Todo::validateTitle('  Valid Title  '));
        $this->assertFalse(\Todo::validateTitle(''));
        $this->assertFalse(\Todo::validateTitle('   '));
    }

    #[Test]
    #[DataProvider('priorityFormattingProvider')]
    public function it_formats_priority_correctly(string $input, string $expected): void
    {
        $this->assertSame($expected, \Todo::formatPriority($input));
    }

    public static function priorityFormattingProvider(): array
    {
        return [
            ['high', 'High'],
            ['medium', 'Medium'],
            ['low', 'Low'],
            ['urgent', 'Urgent'],
        ];
    }
}
