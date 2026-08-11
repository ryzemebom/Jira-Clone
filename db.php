<?php
// db.php - Database connection helper with MySQL / SQLite fallback for zero-config run

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'jira_clone';

try {
    // Try connecting to MySQL
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Create database if not exists and select it
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");
    
    // Self-healing: Check if issues table exists and has user_id column. If not, reset tables to rebuild schema.
    try {
        $pdo->query("SELECT user_id FROM issues LIMIT 1");
    } catch (PDOException $check_e) {
        $pdo->exec("DROP TABLE IF EXISTS subtasks");
        $pdo->exec("DROP TABLE IF EXISTS comments");
        $pdo->exec("DROP TABLE IF EXISTS issues");
        $pdo->exec("DROP TABLE IF EXISTS users");
    }

    // Create tables if they do not exist
    create_tables($pdo, 'mysql');

} catch (PDOException $e) {
    // Fallback to SQLite if MySQL is unavailable
    $sqlite_file = __DIR__ . '/jira_clone.db';
    try {
        $pdo = new PDO("sqlite:" . $sqlite_file, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        
        try {
            $pdo->query("SELECT user_id FROM issues LIMIT 1");
        } catch (PDOException $check_e) {
            $pdo->exec("DROP TABLE IF EXISTS subtasks");
            $pdo->exec("DROP TABLE IF EXISTS comments");
            $pdo->exec("DROP TABLE IF EXISTS issues");
            $pdo->exec("DROP TABLE IF EXISTS users");
        }

        create_tables($pdo, 'sqlite');
    } catch (PDOException $se) {
        die("Database connection failed (MySQL & SQLite): " . $se->getMessage());
    }
}

function create_tables($pdo, $driver) {
    $autoIncrement = ($driver === 'mysql') ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
    $timestampDefault = ($driver === 'mysql') ? 'CURRENT_TIMESTAMP' : "datetime('now')";
    
    // Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY $autoIncrement,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT $timestampDefault
    )");

    // Issues Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS issues (
        id INTEGER PRIMARY KEY $autoIncrement,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        status VARCHAR(50) DEFAULT 'todo',
        priority VARCHAR(20) DEFAULT 'medium',
        assignee VARCHAR(100) DEFAULT 'Não atribuído',
        story_points INT DEFAULT 0,
        label VARCHAR(50) DEFAULT 'Tarefa',
        created_at TIMESTAMP DEFAULT $timestampDefault,
        updated_at TIMESTAMP DEFAULT $timestampDefault,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Comments Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY $autoIncrement,
        issue_id INT NOT NULL,
        author VARCHAR(100) DEFAULT 'Anônimo',
        comment_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT $timestampDefault,
        FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE
    )");

    // Subtasks Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subtasks (
        id INTEGER PRIMARY KEY $autoIncrement,
        issue_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        completed BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE
    )");
}
