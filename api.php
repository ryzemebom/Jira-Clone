<?php
// api.php - REST API handler for Jira Clone with user isolation
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Verify authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized / Não autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'comments') {
                $issue_id = intval($_GET['issue_id'] ?? 0);
                // Verify the issue belongs to the user
                $stmt = $pdo->prepare("SELECT id FROM issues WHERE id = ? AND user_id = ?");
                $stmt->execute([$issue_id, $user_id]);
                if (!$stmt->fetch()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }
                
                $stmt = $pdo->prepare("SELECT * FROM comments WHERE issue_id = ? ORDER BY created_at ASC");
                $stmt->execute([$issue_id]);
                echo json_encode($stmt->fetchAll());
            } elseif ($action === 'subtasks') {
                $issue_id = intval($_GET['issue_id'] ?? 0);
                // Verify the issue belongs to the user
                $stmt = $pdo->prepare("SELECT id FROM issues WHERE id = ? AND user_id = ?");
                $stmt->execute([$issue_id, $user_id]);
                if (!$stmt->fetch()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }

                $stmt = $pdo->prepare("SELECT * FROM subtasks WHERE issue_id = ? ORDER BY id ASC");
                $stmt->execute([$issue_id]);
                echo json_encode($stmt->fetchAll());
            } else {
                $stmt = $pdo->prepare("SELECT * FROM issues WHERE user_id = ? ORDER BY id DESC");
                $stmt->execute([$user_id]);
                echo json_encode($stmt->fetchAll());
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if ($action === 'comment') {
                $issue_id = intval($input['issue_id'] ?? 0);
                // Verify the issue belongs to the user
                $stmt = $pdo->prepare("SELECT id FROM issues WHERE id = ? AND user_id = ?");
                $stmt->execute([$issue_id, $user_id]);
                if (!$stmt->fetch()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }

                $author = trim($input['author'] ?? 'Anonymous');
                $comment_text = trim($input['comment_text'] ?? '');
                
                if (empty($comment_text)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Comment text cannot be empty']);
                    exit;
                }
                
                $stmt = $pdo->prepare("INSERT INTO comments (issue_id, author, comment_text) VALUES (?, ?, ?)");
                $stmt->execute([$issue_id, $author, $comment_text]);
                
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } elseif ($action === 'create_subtask') {
                $issue_id = intval($input['issue_id'] ?? 0);
                // Verify the issue belongs to the user
                $stmt = $pdo->prepare("SELECT id FROM issues WHERE id = ? AND user_id = ?");
                $stmt->execute([$issue_id, $user_id]);
                if (!$stmt->fetch()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }

                $title = trim($input['title'] ?? '');
                
                if (empty($title)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Subtask title cannot be empty']);
                    exit;
                }
                
                $stmt = $pdo->prepare("INSERT INTO subtasks (issue_id, title, completed) VALUES (?, ?, 0)");
                $stmt->execute([$issue_id, $title]);
                
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                $title = trim($input['title'] ?? '');
                $description = trim($input['description'] ?? '');
                $status = trim($input['status'] ?? 'todo');
                $priority = trim($input['priority'] ?? 'medium');
                $assignee = trim($input['assignee'] ?? 'Unassigned');
                $story_points = intval($input['story_points'] ?? 0);
                $label = trim($input['label'] ?? 'Task');

                if (empty($title)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Title is required']);
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO issues (user_id, title, description, status, priority, assignee, story_points, label) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $title, $description, $status, $priority, $assignee, $story_points, $label]);
                
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            }
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'toggle_subtask') {
                $subtask_id = intval($input['id'] ?? 0);
                // Verify the subtask belongs to one of user's issues
                $stmt = $pdo->prepare("SELECT s.id FROM subtasks s JOIN issues i ON s.issue_id = i.id WHERE s.id = ? AND i.user_id = ?");
                $stmt->execute([$subtask_id, $user_id]);
                if (!$stmt->fetch()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }

                $completed = intval($input['completed'] ?? 0);
                $stmt = $pdo->prepare("UPDATE subtasks SET completed = ? WHERE id = ?");
                $stmt->execute([$completed, $subtask_id]);
                echo json_encode(['success' => true]);
                exit;
            }

            $id = intval($input['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID is required for updates']);
                exit;
            }

            // Verify issue ownership
            $stmt = $pdo->prepare("SELECT id FROM issues WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                exit;
            }

            if (isset($input['status_only']) && $input['status_only']) {
                $status = trim($input['status'] ?? 'todo');
                $stmt = $pdo->prepare("UPDATE issues SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$status, $id]);
            } else {
                $title = trim($input['title'] ?? '');
                $description = trim($input['description'] ?? '');
                $status = trim($input['status'] ?? 'todo');
                $priority = trim($input['priority'] ?? 'medium');
                $assignee = trim($input['assignee'] ?? 'Unassigned');
                $story_points = intval($input['story_points'] ?? 0);
                $label = trim($input['label'] ?? 'Task');

                if (empty($title)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Title is required']);
                    exit;
                }

                $stmt = $pdo->prepare("UPDATE issues SET title = ?, description = ?, status = ?, priority = ?, assignee = ?, story_points = ?, label = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$title, $description, $status, $priority, $assignee, $story_points, $label, $id]);
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            if ($action === 'delete_subtask') {
                $subtask_id = intval($_GET['id'] ?? 0);
                // Verify ownership
                $stmt = $pdo->prepare("SELECT s.id FROM subtasks s JOIN issues i ON s.issue_id = i.id WHERE s.id = ? AND i.user_id = ?");
                $stmt->execute([$subtask_id, $user_id]);
                if (!$stmt->fetch()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }

                $stmt = $pdo->prepare("DELETE FROM subtasks WHERE id = ?");
                $stmt->execute([$subtask_id]);
                echo json_encode(['success' => true]);
                exit;
            }

            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID is required for deletion']);
                exit;
            }
            
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM issues WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM issues WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
