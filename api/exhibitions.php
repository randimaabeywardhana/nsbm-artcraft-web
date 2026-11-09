<?php
/**
 * NSBM ArtCraft - Exhibitions API Controller
 * PHP 5.4.3 compatible
 */
require_once __DIR__ . '/config.php';

$action = get_param('action', 'list');
$db = get_db();
$current_user = get_current_user_data();

if ($action === 'list') {
    $status = get_param('status');
    $sql = "SELECT e.*, 
                   (SELECT COUNT(*) FROM exhibition_participants ep WHERE ep.exhibition_id = e.id) AS participants_count
            FROM exhibitions e
            WHERE 1=1";
    $params = array();

    if (!empty($status) && in_array($status, array('upcoming', 'active', 'completed'))) {
        $sql .= " AND e.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY CASE e.status WHEN 'active' THEN 1 WHEN 'upcoming' THEN 2 ELSE 3 END, e.start_date ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $exhibitions = $stmt->fetchAll();

    // If logged in, check if user is already registered for each exhibition
    if ($current_user) {
        foreach ($exhibitions as &$ex) {
            $stR = $db->prepare("SELECT id FROM exhibition_participants WHERE exhibition_id = ? AND user_id = ?");
            $stR->execute(array($ex['id'], $current_user['id']));
            $ex['is_registered'] = ($stR->fetch() ? true : false);
        }
    }

    json_response('success', 'Exhibitions loaded', array('exhibitions' => $exhibitions));
}

if ($action === 'detail') {
    $id = (int) get_param('id', 0);
    if ($id <= 0) {
        json_response('error', 'Valid exhibition ID required.');
    }

    $stmt = $db->prepare("SELECT * FROM exhibitions WHERE id = ?");
    $stmt->execute(array($id));
    $exhibition = $stmt->fetch();

    if (!$exhibition) {
        json_response('error', 'Exhibition not found.', array(), 404);
    }

    // Fetch registered participants
    $partSql = "SELECT ep.*, u.name AS user_name, u.avatar AS user_avatar, u.faculty AS user_faculty,
                       a.title AS artwork_title, a.image_path AS artwork_image, a.medium AS artwork_medium
                FROM exhibition_participants ep
                JOIN users u ON ep.user_id = u.id
                LEFT JOIN artworks a ON ep.artwork_id = a.id
                WHERE ep.exhibition_id = ?
                ORDER BY ep.registered_at DESC";
    $partStmt = $db->prepare($partSql);
    $partStmt->execute(array($id));
    $participants = $partStmt->fetchAll();

    $exhibition['is_registered'] = false;
    if ($current_user) {
        $chk = $db->prepare("SELECT id FROM exhibition_participants WHERE exhibition_id = ? AND user_id = ?");
        $chk->execute(array($id, $current_user['id']));
        if ($chk->fetch()) {
            $exhibition['is_registered'] = true;
        }
    }

    json_response('success', 'Exhibition details loaded', array(
        'exhibition' => $exhibition,
        'participants' => $participants
    ));
}

if ($action === 'create') {
    require_admin();
    $data = get_request_data();
    $title = isset($data['title']) ? trim($data['title']) : '';
    $theme = isset($data['theme']) ? trim($data['theme']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $venue = isset($data['venue']) ? trim($data['venue']) : '';
    $start_date = isset($data['start_date']) ? trim($data['start_date']) : '';
    $end_date = isset($data['end_date']) ? trim($data['end_date']) : '';
    $banner_image = isset($data['banner_image']) ? trim($data['banner_image']) : 'assets/images/exhibit-1.png';
    $status = isset($data['status']) ? trim($data['status']) : 'upcoming';

    if (empty($title) || empty($theme) || empty($venue) || empty($start_date) || empty($end_date)) {
        json_response('error', 'Title, theme, venue, start date, and end date are required.');
    }

    $stmt = $db->prepare("INSERT INTO exhibitions (title, theme, description, venue, start_date, end_date, banner_image, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute(array($title, $theme, $description, $venue, $start_date, $end_date, $banner_image, $status));
    $new_id = $db->lastInsertId();

    // Broadcast notification to all students
    $students = $db->query("SELECT id FROM users WHERE role = 'student'")->fetchAll();
    foreach ($students as $stu) {
        add_notification(
            $stu['id'],
            'New Exhibition Announced: ' . $title,
            'Theme: ' . $theme . '. Registrations are now open to showcase your artworks!',
            'exhibition',
            'exhibition-detail.html?id=' . $new_id
        );
    }

    json_response('success', 'Exhibition created successfully.', array('id' => $new_id));
}

if ($action === 'update') {
    require_admin();
    $data = get_request_data();
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    if ($id <= 0) {
        json_response('error', 'Valid exhibition ID required.');
    }

    $title = isset($data['title']) ? trim($data['title']) : '';
    $theme = isset($data['theme']) ? trim($data['theme']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $venue = isset($data['venue']) ? trim($data['venue']) : '';
    $start_date = isset($data['start_date']) ? trim($data['start_date']) : '';
    $end_date = isset($data['end_date']) ? trim($data['end_date']) : '';
    $banner_image = isset($data['banner_image']) ? trim($data['banner_image']) : '';
    $status = isset($data['status']) ? trim($data['status']) : 'upcoming';

    $stmt = $db->prepare("UPDATE exhibitions SET title = ?, theme = ?, description = ?, venue = ?, start_date = ?, end_date = ?, status = ?, banner_image = IF(? != '', ?, banner_image) WHERE id = ?");
    $stmt->execute(array($title, $theme, $description, $venue, $start_date, $end_date, $status, $banner_image, $banner_image, $id));

    json_response('success', 'Exhibition updated successfully.');
}

if ($action === 'delete') {
    require_admin();
    $id = (int) get_param('id', 0);
    if ($id <= 0) {
        $data = get_request_data();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
    }

    if ($id <= 0) {
        json_response('error', 'Valid exhibition ID required.');
    }

    $stmt = $db->prepare("DELETE FROM exhibitions WHERE id = ?");
    $stmt->execute(array($id));

    json_response('success', 'Exhibition deleted successfully.');
}

if ($action === 'register') {
    $user = require_auth();
    $data = get_request_data();
    $exhibition_id = isset($data['exhibition_id']) ? (int)$data['exhibition_id'] : 0;
    $artwork_id = (isset($data['artwork_id']) && (int)$data['artwork_id'] > 0) ? (int)$data['artwork_id'] : null;
    $notes = isset($data['notes']) ? trim($data['notes']) : '';

    if ($exhibition_id <= 0) {
        json_response('error', 'Valid exhibition ID required.');
    }

    // Check if exhibition exists
    $exStmt = $db->prepare("SELECT * FROM exhibitions WHERE id = ?");
    $exStmt->execute(array($exhibition_id));
    $ex = $exStmt->fetch();
    if (!$ex) {
        json_response('error', 'Exhibition not found.');
    }

    // Check if already registered
    $chkStmt = $db->prepare("SELECT id FROM exhibition_participants WHERE exhibition_id = ? AND user_id = ?");
    $chkStmt->execute(array($exhibition_id, $user['id']));
    if ($chkStmt->fetch()) {
        json_response('error', 'You are already registered for this exhibition.');
    }

    // Insert registration
    $insStmt = $db->prepare("INSERT INTO exhibition_participants (exhibition_id, user_id, artwork_id, notes, registered_at) VALUES (?, ?, ?, ?, NOW())");
    $insStmt->execute(array($exhibition_id, $user['id'], $artwork_id, $notes));

    // Send confirmation notification
    add_notification(
        $user['id'],
        'Exhibition Registration Confirmed! 🎨',
        'Your registration for "' . $ex['title'] . '" has been confirmed. Get your work ready for showcase!',
        'exhibition',
        'exhibition-detail.html?id=' . $exhibition_id
    );

    json_response('success', 'Registration submitted successfully! You are participating in this exhibition.');
}

json_response('error', 'Unknown action.');
