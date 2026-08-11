<?php
// auth.php - Authentication controller for Jira Clone
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Preencha todos os campos / Please fill in all fields']);
        exit;
    }

    if ($action === 'register') {
        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome de usuário já existe / Username already exists']);
                exit;
            }

            // Create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashedPassword]);
            $userId = $pdo->lastInsertId();

            // Set session
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;

            // Generate dummy starter tasks for the new user
            $dummyIssues = [
                ['Bem-vindo ao Jira Clone!', 'Este é o seu quadro Kanban pessoal. Você pode criar novas tarefas e arrastá-las entre as colunas.', 'todo', 'medium', 'Você', 1, 'Tarefa'],
                ['Configurar meu primeiro projeto', 'Personalize as configurações e adicione subtarefas nesta tarefa.', 'in_progress', 'high', 'Você', 3, 'Feature'],
                ['Concluir uma tarefa de teste', 'Arraste este cartão para a coluna CONCLUÍDO!', 'done', 'low', 'Não atribuído', 1, 'Tarefa']
            ];
            $insert = $pdo->prepare("INSERT INTO issues (user_id, title, description, status, priority, assignee, story_points, label) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($dummyIssues as $issue) {
                $insert->execute(array_merge([$userId], $issue));
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'login') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Usuário ou senha incorretos / Invalid username or password']);
                exit;
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

if ($method === 'GET' && $action === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Ação inválida / Invalid action']);
