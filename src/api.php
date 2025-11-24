<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/TodoService.php';

/**
 * REST API for Todo application
 * Demonstrates routing and API handling
 */

$service = new TodoService();

// Parse the request
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Remove /api prefix if present
$path = preg_replace('#^/api#', '', $path);

// Route handling
try {
    match (true) {
        // GET /todos - Get all todos
        $method === 'GET' && $path === '/todos' => (function () use ($service) {
                $filter = $_GET['filter'] ?? null;

                if ($filter === 'completed') {
                    echo json_encode($service->getByStatus(true));
                } elseif ($filter === 'pending') {
                    echo json_encode($service->getByStatus(false));
                } else {
                    echo json_encode($service->getAll());
                }
            })(),

        // GET /todos/stats - Get statistics
        $method === 'GET' && $path === '/todos/stats' => (function () use ($service) {
                echo json_encode($service->getStats());
            })(),

        // GET /todos/priority - Get todos grouped by priority
        $method === 'GET' && $path === '/todos/priority' => (function () use ($service) {
                echo json_encode($service->getByPriority());
            })(),

        // GET /todos/{id} - Get specific todo
        $method === 'GET' && preg_match('#^/todos/(\d+)$#', $path, $matches) => (function () use ($service, $matches) {
                $id = (int) $matches[1];
                $todo = $service->getById($id);

                if ($todo) {
                    echo json_encode($todo);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Todo not found']);
                }
            })(),

        // POST /todos - Create new todo
        $method === 'POST' && $path === '/todos' => (function () use ($service) {
                $data = json_decode(file_get_contents('php://input'), true);

                if (!isset($data['title']) || empty(trim($data['title']))) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Title is required']);
                    return;
                }

                $todo = $service->create($data);
                http_response_code(201);
                echo json_encode($todo);
            })(),

        // PUT /todos/{id} - Update todo
        $method === 'PUT' && preg_match('#^/todos/(\d+)$#', $path, $matches) => (function () use ($service, $matches) {
                $id = (int) $matches[1];
                $data = json_decode(file_get_contents('php://input'), true);

                $todo = $service->update($id, $data);

                if ($todo) {
                    echo json_encode($todo);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Todo not found']);
                }
            })(),

        // DELETE /todos/{id} - Delete todo
        $method === 'DELETE' && preg_match('#^/todos/(\d+)$#', $path, $matches) => (function () use ($service, $matches) {
                $id = (int) $matches[1];

                if ($service->delete($id)) {
                    http_response_code(204);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Todo not found']);
                }
            })(),

        // Default - 404
        default => (function () {
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
            })(),
    };
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
