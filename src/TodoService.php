<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Todo.php';

/**
 * Todo Service Layer
 * Demonstrates PHP 8.5's pipe operator and new array functions
 */
class TodoService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all todos
     * Demonstrates PHP 8.5: Pipe operator for data transformation
     */
    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM todos ORDER BY created_at DESC');
        $rows = $stmt->fetchAll();

        // PHP 8.5: Pipe operator - chain transformations
        return $rows
            |> (fn($data) => array_map(fn($row) => Todo::fromArray($row), $data))
            |> (fn($todos) => array_map(fn($todo) => $todo->toArray(), $todos));
    }

    /**
     * Get filtered todos
     * Demonstrates PHP 8.5: array_first() and array_last()
     */
    public function getByStatus(bool $completed): array
    {
        $stmt = $this->db->prepare('SELECT * FROM todos WHERE completed = ? ORDER BY created_at DESC');
        $stmt->execute([(int) $completed]);
        $rows = $stmt->fetchAll();

        return $rows
            |> (fn($data) => array_map(fn($row) => Todo::fromArray($row), $data))
            |> (fn($todos) => array_map(fn($todo) => $todo->toArray(), $todos));
    }

    /**
     * Get statistics about todos
     * Demonstrates PHP 8.5: array_first() and array_last()
     */
    public function getStats(): array
    {
        $todos = $this->getAll();

        return [
            'total' => count($todos),
            'completed' => count(array_filter($todos, fn($t) => $t['completed'])),
            'pending' => count(array_filter($todos, fn($t) => !$t['completed'])),
            // PHP 8.5: array_first() - get first element
            'first_todo' => array_first($todos),
            // PHP 8.5: array_last() - get last element
            'last_todo' => array_last($todos),
        ];
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM todos WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return Todo::fromArray($row)->toArray();
    }

    public function create(array $data): array
    {
        $todo = Todo::fromArray([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'completed' => false,
            'priority' => $data['priority'] ?? 'medium',
            'due_date' => $data['due_date'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $sql = 'INSERT INTO todos (title, description, completed, priority, due_date, created_at, updated_at) 
                VALUES (:title, :description, :completed, :priority, :due_date, :created_at, :updated_at)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':title' => $todo->title,
            ':description' => $todo->description,
            ':completed' => (int) $todo->completed,
            ':priority' => $todo->priority,
            ':due_date' => $todo->due_date,
            ':created_at' => $todo->created_at,
            ':updated_at' => $todo->updated_at,
        ]);

        $id = (int) $this->db->lastInsertId();
        return $this->getById($id);
    }

    public function update(int $id, array $data): ?array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return null;
        }

        $sql = 'UPDATE todos SET title = :title, description = :description, 
                completed = :completed, priority = :priority, due_date = :due_date, updated_at = :updated_at
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'] ?? $existing['title'],
            ':description' => $data['description'] ?? $existing['description'],
            ':completed' => (int) ($data['completed'] ?? $existing['completed']),
            ':priority' => $data['priority'] ?? $existing['priority'],
            ':due_date' => $data['due_date'] ?? $existing['due_date'],
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->getById($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM todos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get todos grouped by priority
     * Demonstrates pipe operator for complex transformations
     */
    public function getByPriority(): array
    {
        $todos = $this->getAll();

        return $todos
            |> (fn($data) => array_reduce($data, function ($carry, $todo) {
                $priority = $todo['priority'];
                $carry[$priority] = $carry[$priority] ?? [];
                $carry[$priority][] = $todo;
                return $carry;
            }, []))
            |> (fn($grouped) => array_map(fn($group) => [
                'count' => count($group),
                'items' => $group
            ], $grouped));
    }
}
