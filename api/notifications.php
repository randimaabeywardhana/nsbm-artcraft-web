<?php
/**
 * NSBM ArtCraft - Notifications API Controller
 * Real-time notification center & badge counter
 * PHP 5.4.3 compatible
 */
require_once __DIR__ . '/config.php';

$action = get_param('action', 'list');
$db = get_db();
$user = require_auth();

if ($action === 'list') {
    $limit = (int) get_param('limit', 30);
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT " . $limit);
    $stmt->execute(array($user['id']));
    $notifications = $stmt->fetchAll();

    // Get unread count
    $cntStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $cntStmt->execute(array($user['id']));
    $unread = (int)$cntStmt->fetchColumn();

    json_response('success', 'Notifications loaded', array(
        'notifications' => $notifications,
        'unread_count' => $unread
    ));
}

if ($action === 'unread_count') {
    $cntStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $cntStmt->execute(array($user['id']));
    $unread = (int)$cntStmt->fetchColumn();

    json_response('success', 'Unread count', array('unread_count' => $unread));
}

if ($action === 'mark_read') {
    $id = (int) get_param('id', 0);
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute(array($id, $user['id']));
    } else {
        // Mark all as read
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute(array($user['id']));
    }

    json_response('success', 'Notifications marked as read.');
}

if ($action === 'delete') {
    $id = (int) get_param('id', 0);
    if ($id <= 0) {
        $data = get_request_data();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
    }

    if ($id <= 0) {
        json_response('error', 'Valid notification ID required.');
    }

    $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute(array($id, $user['id']));

    json_response('success', 'Notification deleted.');
}

json_response('error', 'Unknown action.');
