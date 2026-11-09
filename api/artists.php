<?php
/**
 * NSBM ArtCraft - Artists API Controller
 * Artist Profiles, Follow System, Community Directory
 * PHP 5.4.3 compatible
 */
require_once __DIR__ . '/config.php';

$action = get_param('action', 'list');
$db = get_db();
$current_user = get_current_user_data();

if ($action === 'list') {
    $search = get_param('search');
    $faculty = get_param('faculty');

    $sql = "SELECT u.id, u.student_id, u.name, u.faculty, u.bio, u.avatar, u.created_at,
                   (SELECT COUNT(*) FROM artworks a WHERE a.user_id = u.id AND a.status = 'approved') AS artworks_count,
                   (SELECT COUNT(*) FROM user_follows f WHERE f.following_id = u.id) AS followers_count,
                   (SELECT COUNT(*) FROM artwork_likes l 
                    JOIN artworks a2 ON l.artwork_id = a2.id 
                    WHERE a2.user_id = u.id AND a2.status = 'approved') AS total_likes_received
            FROM users u
            WHERE u.role = 'student'";
    $params = array();

    if (!empty($faculty)) {
        $sql .= " AND u.faculty = ?";
        $params[] = $faculty;
    }

    if (!empty($search)) {
        $sql .= " AND (u.name LIKE ? OR u.bio LIKE ? OR u.faculty LIKE ?)";
        $sTerm = '%' . $search . '%';
        $params[] = $sTerm;
        $params[] = $sTerm;
        $params[] = $sTerm;
    }

    $sql .= " ORDER BY artworks_count DESC, followers_count DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $artists = $stmt->fetchAll();

    foreach ($artists as &$art) {
        $art['is_following'] = false;
        if ($current_user) {
            $fStmt = $db->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $fStmt->execute(array($current_user['id'], $art['id']));
            if ($fStmt->fetch()) {
                $art['is_following'] = true;
            }
        }
    }

    json_response('success', 'Artists loaded', array('artists' => $artists));
}

if ($action === 'detail') {
    $id = (int) get_param('id', 0);
    if ($id <= 0) {
        json_response('error', 'Valid artist ID required.');
    }

    $stmt = $db->prepare("SELECT id, student_id, name, faculty, bio, avatar, created_at FROM users WHERE id = ?");
    $stmt->execute(array($id));
    $artist = $stmt->fetch();
    if (!$artist) {
        json_response('error', 'Artist not found.');
    }

    // Follower and following counts
    $f1 = $db->prepare("SELECT COUNT(*) FROM user_follows WHERE following_id = ?");
    $f1->execute(array($id));
    $artist['followers_count'] = (int)$f1->fetchColumn();

    $f2 = $db->prepare("SELECT COUNT(*) FROM user_follows WHERE follower_id = ?");
    $f2->execute(array($id));
    $artist['following_count'] = (int)$f2->fetchColumn();

    $artist['is_following'] = false;
    if ($current_user) {
        $chk = $db->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
        $chk->execute(array($current_user['id'], $id));
        if ($chk->fetch()) {
            $artist['is_following'] = true;
        }
    }

    // Artist's approved artworks
    $artSql = "SELECT a.*, c.name AS category_name, c.slug AS category_slug,
                      (SELECT COUNT(*) FROM artwork_likes l WHERE l.artwork_id = a.id) AS likes_count,
                      (SELECT COALESCE(AVG(r.rating), 0) FROM artwork_ratings r WHERE r.artwork_id = a.id) AS avg_rating
               FROM artworks a
               JOIN categories c ON a.category_id = c.id
               WHERE a.user_id = ? AND a.status = 'approved'
               ORDER BY a.created_at DESC";
    $artStmt = $db->prepare($artSql);
    $artStmt->execute(array($id));
    $artworks = $artStmt->fetchAll();

    foreach ($artworks as &$a) {
        $a['avg_rating'] = round((float)$a['avg_rating'], 1);
    }

    // Exhibitions participated
    $exSql = "SELECT e.*, ep.notes, a.title AS art_title, a.image_path AS art_image
              FROM exhibition_participants ep
              JOIN exhibitions e ON ep.exhibition_id = e.id
              LEFT JOIN artworks a ON ep.artwork_id = a.id
              WHERE ep.user_id = ?
              ORDER BY e.start_date DESC";
    $exStmt = $db->prepare($exSql);
    $exStmt->execute(array($id));
    $exhibitions = $exStmt->fetchAll();

    json_response('success', 'Artist profile loaded', array(
        'artist' => $artist,
        'artworks' => $artworks,
        'exhibitions' => $exhibitions
    ));
}

if ($action === 'follow_toggle') {
    $user = require_auth();
    $data = get_request_data();
    $following_id = isset($data['artist_id']) ? (int)$data['artist_id'] : 0;

    if ($following_id <= 0 || $following_id == $user['id']) {
        json_response('error', 'You cannot follow yourself.');
    }

    $chkStmt = $db->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
    $chkStmt->execute(array($user['id'], $following_id));
    $existing = $chkStmt->fetch();

    $is_following = false;
    if ($existing) {
        $db->prepare("DELETE FROM user_follows WHERE id = ?")->execute(array($existing['id']));
        $is_following = false;
    } else {
        $db->prepare("INSERT INTO user_follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())")
           ->execute(array($user['id'], $following_id));
        $is_following = true;

        // Notify followed artist
        add_notification(
            $following_id,
            'New Follower! 👤',
            $user['name'] . ' is now following your artistic portfolio on NSBM ArtCraft.',
            'follow',
            'artist-profile.html?id=' . $user['id']
        );
    }

    $cnt = $db->prepare("SELECT COUNT(*) FROM user_follows WHERE following_id = ?");
    $cnt->execute(array($following_id));
    $newCount = (int)$cnt->fetchColumn();

    json_response('success', ($is_following ? 'You are now following this artist.' : 'Unfollowed artist.'), array(
        'is_following' => $is_following,
        'followers_count' => $newCount
    ));
}

json_response('error', 'Unknown action.');
