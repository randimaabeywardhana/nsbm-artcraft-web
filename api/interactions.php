<?php
/**
 * NSBM ArtCraft - Interactions API Controller
 * Likes, Ratings (1-5 stars), Comments
 * PHP 5.4.3 compatible
 */
require_once __DIR__ . '/config.php';

$action = get_param('action');
$db = get_db();
$user = require_auth();

// -------------------------------------------------------------
// TOGGLE LIKE
// -------------------------------------------------------------
if ($action === 'like_toggle') {
    $data = get_request_data();
    $artwork_id = isset($data['artwork_id']) ? (int)$data['artwork_id'] : 0;
    if ($artwork_id <= 0) {
        json_response('error', 'Valid artwork ID required.');
    }

    // Check if artwork exists
    $artStmt = $db->prepare("SELECT user_id, title FROM artworks WHERE id = ?");
    $artStmt->execute(array($artwork_id));
    $art = $artStmt->fetch();
    if (!$art) {
        json_response('error', 'Artwork not found.');
    }

    // Check existing like
    $chkStmt = $db->prepare("SELECT id FROM artwork_likes WHERE artwork_id = ? AND user_id = ?");
    $chkStmt->execute(array($artwork_id, $user['id']));
    $existing = $chkStmt->fetch();

    $is_liked = false;
    if ($existing) {
        // Unlike
        $db->prepare("DELETE FROM artwork_likes WHERE id = ?")->execute(array($existing['id']));
        $is_liked = false;
    } else {
        // Like
        $db->prepare("INSERT INTO artwork_likes (artwork_id, user_id, created_at) VALUES (?, ?, NOW())")
           ->execute(array($artwork_id, $user['id']));
        $is_liked = true;

        // Notify artwork owner if not own artwork
        if ($art['user_id'] != $user['id']) {
            add_notification(
                $art['user_id'],
                'New Like on your Artwork! ❤️',
                $user['name'] . ' liked your artwork "' . $art['title'] . '".',
                'like',
                'artwork-detail.html?id=' . $artwork_id
            );
        }
    }

    // Get updated count
    $cntStmt = $db->prepare("SELECT COUNT(*) AS total FROM artwork_likes WHERE artwork_id = ?");
    $cntStmt->execute(array($artwork_id));
    $total = (int)$cntStmt->fetchColumn();

    json_response('success', ($is_liked ? 'Artwork liked' : 'Artwork unliked'), array(
        'is_liked' => $is_liked,
        'likes_count' => $total
    ));
}

// -------------------------------------------------------------
// SUBMIT RATING (1 - 5 stars)
// -------------------------------------------------------------
if ($action === 'rate') {
    $data = get_request_data();
    $artwork_id = isset($data['artwork_id']) ? (int)$data['artwork_id'] : 0;
    $rating = isset($data['rating']) ? (int)$data['rating'] : 0;

    if ($artwork_id <= 0 || $rating < 1 || $rating > 5) {
        json_response('error', 'Please provide a valid rating between 1 and 5 stars.');
    }

    // Check artwork
    $artStmt = $db->prepare("SELECT user_id, title FROM artworks WHERE id = ?");
    $artStmt->execute(array($artwork_id));
    $art = $artStmt->fetch();
    if (!$art) {
        json_response('error', 'Artwork not found.');
    }

    // Upsert rating (MySQL ON DUPLICATE KEY UPDATE)
    $sql = "INSERT INTO artwork_ratings (artwork_id, user_id, rating, created_at) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), created_at = NOW()";
    $stmt = $db->prepare($sql);
    $stmt->execute(array($artwork_id, $user['id'], $rating));

    // Notify creator
    if ($art['user_id'] != $user['id']) {
        add_notification(
            $art['user_id'],
            'New Artwork Rating! ⭐',
            $user['name'] . ' rated your artwork "' . $art['title'] . '" ' . $rating . ' stars.',
            'rating',
            'artwork-detail.html?id=' . $artwork_id
        );
    }

    // Calculate new average
    $statStmt = $db->prepare("SELECT AVG(rating) AS avg_rate, COUNT(*) AS total_rates FROM artwork_ratings WHERE artwork_id = ?");
    $statStmt->execute(array($artwork_id));
    $stat = $statStmt->fetch();

    json_response('success', 'Thank you! Your rating of ' . $rating . ' stars has been recorded.', array(
        'avg_rating' => round((float)$stat['avg_rate'], 1),
        'ratings_count' => (int)$stat['total_rates'],
        'my_rating' => $rating
    ));
}

// -------------------------------------------------------------
// ADD COMMENT
// -------------------------------------------------------------
if ($action === 'comment') {
    $data = get_request_data();
    $artwork_id = isset($data['artwork_id']) ? (int)$data['artwork_id'] : 0;
    $comment = isset($data['comment']) ? trim($data['comment']) : '';

    if ($artwork_id <= 0 || empty($comment)) {
        json_response('error', 'Comment text cannot be empty.');
    }

    $artStmt = $db->prepare("SELECT user_id, title FROM artworks WHERE id = ?");
    $artStmt->execute(array($artwork_id));
    $art = $artStmt->fetch();
    if (!$art) {
        json_response('error', 'Artwork not found.');
    }

    $ins = $db->prepare("INSERT INTO artwork_comments (artwork_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
    $ins->execute(array($artwork_id, $user['id'], $comment));

    // Notify creator
    if ($art['user_id'] != $user['id']) {
        add_notification(
            $art['user_id'],
            'New Feedback on your Artwork! 💬',
            $user['name'] . ' commented on "' . $art['title'] . '": "' . substr($comment, 0, 50) . '..."',
            'comment',
            'artwork-detail.html?id=' . $artwork_id
        );
    }

    // Return updated comments list
    $cmtStmt = $db->prepare("SELECT cm.*, u.name AS user_name, u.avatar AS user_avatar, u.faculty AS user_faculty 
                             FROM artwork_comments cm 
                             JOIN users u ON cm.user_id = u.id 
                             WHERE cm.artwork_id = ? 
                             ORDER BY cm.created_at DESC");
    $cmtStmt->execute(array($artwork_id));
    $comments = $cmtStmt->fetchAll();

    json_response('success', 'Comment posted successfully.', array(
        'comments' => $comments,
        'comments_count' => count($comments)
    ));
}

json_response('error', 'Unknown interaction action.');
