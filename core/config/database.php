<?php
// core/config/database.php - Konfigurasi Database

// Database Configuration - SESUAIKAN DENGAN SETTING ANDA
$db_host = 'localhost';
$db_user = 'root';          // Ganti jika berbeda
$db_pass = '';              // Ganti jika ada password
$db_name = 'sms_db';

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Helper functions
function query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function getAll($sql, $params = []) {
    return query($sql, $params)->fetchAll();
}

function getOne($sql, $params = []) {
    return query($sql, $params)->fetch();
}

function insert($table, $data) {
    global $pdo;
    $fields = implode(',', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    return $pdo->lastInsertId();
}

function update($table, $data, $where, $params = []) {
    global $pdo;
    $set = [];
    foreach($data as $key => $value) {
        $set[] = "$key = :$key";
    }
    $set = implode(', ', $set);
    $sql = "UPDATE $table SET $set WHERE $where";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($data, $params));
    return $stmt->rowCount();
}

function delete($table, $where, $params = []) {
    global $pdo;
    $sql = "DELETE FROM $table WHERE $where";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

// Authentication functions
function login($username, $password) {
    $user = getOne("SELECT * FROM users WHERE username = ? AND status = 'active'", [$username]);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function getUser() {
    return $_SESSION['user'] ?? null;
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function hasRole($roles) {
    $user = getUser();
    if (!$user) return false;
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    return in_array($user['role'], $roles);
}

// Base URL - SESUAIKAN DENGAN FOLDER ANDA
$base_url = 'http://localhost/SMS'; // Ganti jika berbeda

// App info
$app_name = 'SMS - School Management System';
$app_version = '1.0';
?>