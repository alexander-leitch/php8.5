<?php

declare(strict_types=1);

/**
 * Todo Model
 * Demonstrates PHP 8.5 features with focus on stable implementations
 */
class Todo
{
    // Validation and formatting as static methods instead of constant closures
    public static function validateTitle(string $title): bool
    {
        return strlen(trim($title)) > 0;
    }

    public static function formatPriority(string $priority): string
    {
        return strtoupper($priority[0]) . substr($priority, 1);
    }

    public function __construct(
        public ?int $id,
        public string $title,
        public ?string $description,
        public bool $completed,
        public string $priority,
        public ?string $due_date,
        public string $created_at,
        public string $updated_at,
    ) {
    }

    /**
     * Check if this todo is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->due_date === null || $this->completed) {
            return false;
        }

        $dueDateTime = new DateTime($this->due_date);
        $now = new DateTime();

        return $dueDateTime < $now;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            title: $data['title'],
            description: $data['description'] ?? null,
            completed: (bool) ($data['completed'] ?? false),
            priority: $data['priority'] ?? 'medium',
            due_date: $data['due_date'] ?? null,
            created_at: $data['created_at'] ?? date('Y-m-d H:i:s'),
            updated_at: $data['updated_at'] ?? date('Y-m-d H:i:s'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'completed' => $this->completed,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'is_overdue' => $this->isOverdue(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Create a new instance with updated properties
     * Note: Clone with syntax would be: clone $this with { completed: true }
     * but it's not yet stable in PHP 8.5, so we use traditional approach
     */
    public function markAsCompleted(): self
    {
        $clone = clone $this;
        $clone->completed = true;
        $clone->updated_at = date('Y-m-d H:i:s');
        return $clone;
    }

    public function markAsIncomplete(): self
    {
        $clone = clone $this;
        $clone->completed = false;
        $clone->updated_at = date('Y-m-d H:i:s');
        return $clone;
    }

    public function updateTitle(string $title): self
    {
        $clone = clone $this;
        $clone->title = $title;
        $clone->updated_at = date('Y-m-d H:i:s');
        return $clone;
    }

    public function updatePriority(string $priority): self
    {
        $clone = clone $this;
        $clone->priority = $priority;
        $clone->updated_at = date('Y-m-d H:i:s');
        return $clone;
    }
}

