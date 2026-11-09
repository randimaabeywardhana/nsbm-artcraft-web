<?php
/**
 * NSBM ArtCraft - Authentication Controller
 * PHP 5.4.3 compatible
 */
require_once __DIR__ . '/config.php';

$action = get_param('action', 'check');
$db = get_db();

if ($action === 'check') {
    $user = get_current_user_data();
    if ($user) {
        // Refresh user details from DB
        $stmt = $db->prepare("SELECT id, student_id, name, email, role, faculty, bio, avatar, created_at FROM users WHERE id = ?");
        $stmt->execute(array($user['id']));
        $refreshed = $stmt->fetch();
        if ($refreshed) {
            $_SESSION['user'] = $refreshed;
            json_response('success', 'User logged in', array('user' => $refreshed));
        }
    }
    json_response('success', 'Guest session', array('user' => null));
}

if ($action === 'login') {
    $data = get_request_data();
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = isset($data['password']) ? trim($data['password']) : '';

    if (empty($email) || empty($password)) {
        json_response('error', 'Please provide both email and password.');
    }

    $hash = app_hash_password($password);

    $stmt = $db->prepare("SELECT id, student_id, name, email, password, role, faculty, bio, avatar, created_at FROM users WHERE email = ?");
    $stmt->execute(array($email));
    $user = $stmt->fetch();

    if ($user && $user['password'] === $hash) {
        unset($user['password']);
        $_SESSION['user'] = $user;
        json_response('success', 'Login successful! Welcome back, ' . $user['name'], array('user' => $user));
    } else {
        json_response('error', 'Invalid email or password. Please check your credentials.');
    }
}

if ($action === 'register') {
    $data = get_request_data();
    $student_id = isset($data['student_id']) ? trim($data['student_id']) : '';
    $name = isset($data['name']) ? trim($data['name']) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = isset($data['password']) ? trim($data['password']) : '';
    $faculty = isset($data['faculty']) ? trim($data['faculty']) : 'Faculty of Computing';
    $bio = isset($data['bio']) ? trim($data['bio']) : '';

    if (empty($name) || empty($email) || empty($password)) {
        json_response('error', 'Name, email, and password are required fields.');
    }

    if (strlen($password) < 6) {
        json_response('error', 'Password must be at least 6 characters long.');
    }

    // Check duplicate email
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(array($email));
    if ($stmt->fetch()) {
        json_response('error', 'An account with this email address already exists.');
    }

    // Check duplicate student_id if provided
    if (!empty($student_id)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE student_id = ?");
        $stmt->execute(array($student_id));
        if ($stmt->fetch()) {
            json_response('error', 'An account with this Student ID already exists.');
        }
    }

    $hash = app_hash_password($password);
    $avatar = 'assets/images/avatar-default.png';

    $stmt = $db->prepare("INSERT INTO users (student_id, name, email, password, role, faculty, bio, avatar, created_at) VALUES (?, ?, ?, ?, 'student', ?, ?, ?, NOW())");
    $stmt->execute(array($student_id, $name, $email, $hash, $faculty, $bio, $avatar));
    $new_id = $db->lastInsertId();

    // Fetch created user
    $stmt = $db->prepare("SELECT id, student_id, name, email, role, faculty, bio, avatar, created_at FROM users WHERE id = ?");
    $stmt->execute(array($new_id));
    $user = $stmt->fetch();

    $_SESSION['user'] = $user;

    // Send welcome notification
    add_notification(
        $new_id,
        'Welcome to NSBM ArtCraft!',
        'Your student account has been created. Start uploading your artworks and join campus exhibitions.',
        'system',
        'upload-artwork.html'
    );

    json_response('success', 'Registration successful! Welcome to NSBM ArtCraft.', array('user' => $user));
}

if ($action === 'logout') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    json_response('success', 'You have been logged out.');
}

json_response('error', 'Unknown auth action.');
