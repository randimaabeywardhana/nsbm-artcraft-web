<?php
/**
 * NSBM ArtCraft - Student Arts and Handcraft Showcasing System
 * Backend Configuration & Database Bootstrap
 * 
 * Target Environment: WAMP (PHP 5.4.3 / Apache 2.2.22 / MySQL 5.5.24)
 * Architecture: Team Leader (Database & Core Arch) + QA Lead (Data Validation & Security)
 */

// Error reporting (clean output for JSON APIs)
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0');

// Start session if not already started
if (session_id() === '') {
    session_start();
}

// Database Credentials (Default WAMP stack settings)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nsbm-artcraft-web');

// Password Salt for SHA-256 (PHP 5.4 safe authentication)
define('AUTH_SALT', 'NSBM_GreenUniv_ArtCraft_2026_Salt');

// Global PDO Database Connection
function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ));
        } catch (PDOException $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Database connection error: ' . $e->getMessage()
            ));
            exit;
        }
    }
    return $pdo;
}

// Password hashing function (PHP 5.4 compatible)
function app_hash_password($password) {
    return hash('sha256', AUTH_SALT . $password);
}

// Password verification function (PHP 5.4 compatible)
function app_verify_password($password, $hash) {
    return app_hash_password($password) === $hash;
}

// Standard JSON response helper
function json_response($status, $message, $data = array(), $http_code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    if ($http_code !== 200 && function_exists('http_response_code')) {
        http_response_code($http_code);
    }
    echo json_encode(array(
        'status' => $status,
        'message' => $message,
        'data' => $data
    ));
    exit;
}

// Read raw JSON input or POST data
function get_request_data() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        return $json;
    }
    return $_POST;
}

// Get query param safely (PHP 5.4 compatible without ??)
function get_param($key, $default = null) {
    if (isset($_GET[$key])) {
        return trim($_GET[$key]);
    }
    if (isset($_POST[$key])) {
        return trim($_POST[$key]);
    }
    return $default;
}

// Check if user is authenticated
function get_current_user_data() {
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

// Require authentication guard
function require_auth() {
    $user = get_current_user_data();
    if (!$user) {
        json_response('error', 'Authentication required. Please login.', array(), 401);
    }
    return $user;
}

// Require admin guard
function require_admin() {
    $user = require_auth();
    if ($user['role'] !== 'admin') {
        json_response('error', 'Administrator access required.', array(), 403);
    }
    return $user;
}

// Helper to push a notification to a specific user
function add_notification($user_id, $title, $message, $type = 'system', $link = null) {
    try {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
        $stmt->execute(array($user_id, $title, $message, $type, $link));
        return true;
    } catch (Exception $e) {
        return false;
    }
}
