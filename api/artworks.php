<?php
/**
 * NSBM ArtCraft - Artworks API Controller
 * Complete CRUD + Approval + Rating/Like Aggregation
 * PHP 5.4.3 compatible
 */
require_once __DIR__ . '/config.php';

$action = get_param('action', 'list');
$db = get_db();
$current_user = get_current_user_data();

// -------------------------------------------------------------
// LIST ARTWORKS
// -------------------------------------------------------------
if ($action === 'list') {
    $category_id = get_param('category_id');
    $category_slug = get_param('category');
    $status = get_param('status', 'approved');
    $featured = get_param('featured');
    $search = get_param('search');
    $artist_id = get_param('artist_id');
    $sort = get_param('sort', 'newest');
    $limit = (int) get_param('limit', 50);

    $sql = "SELECT a.*, 
                   u.name AS artist_name, u.faculty AS artist_faculty, u.avatar AS artist_avatar, u.student_id AS artist_student_id,
                   c.name AS category_name, c.slug AS category_slug,
                   (SELECT COUNT(*) FROM artwork_likes l WHERE l.artwork_id = a.id) AS likes_count,
                   (SELECT COALESCE(AVG(r.rating), 0) FROM artwork_ratings r WHERE r.artwork_id = a.id) AS avg_rating,
                   (SELECT COUNT(*) FROM artwork_ratings r WHERE r.artwork_id = a.id) AS ratings_count,
                   (SELECT COUNT(*) FROM artwork_comments cm WHERE cm.artwork_id = a.id) AS comments_count
            FROM artworks a
            JOIN users u ON a.user_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE 1=1";

    $params = array();

    // Status filter
    if ($status === 'my') {
        if (!$current_user) {
            json_response('error', 'Login required to view your submissions.');
        }
        $sql .= " AND a.user_id = ?";
        $params[] = $current_user['id'];
    } elseif ($status !== 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    } else {
        // Only admin can see all statuses without filtering
        if (!$current_user || $current_user['role'] !== 'admin') {
            $sql .= " AND a.status = 'approved'";
        }
    }

    if (!empty($category_id)) {
        $sql .= " AND a.category_id = ?";
        $params[] = (int) $category_id;
    }
    if (!empty($category_slug)) {
        $sql .= " AND c.slug = ?";
        $params[] = $category_slug;
    }
    if (!empty($artist_id)) {
        $sql .= " AND a.user_id = ?";
        $params[] = (int) $artist_id;
    }
    if ($featured === '1' || $featured === 'true') {
        $sql .= " AND a.is_featured = 1";
    }
    if (!empty($search)) {
        $sql .= " AND (a.title LIKE ? OR a.description LIKE ? OR a.medium LIKE ? OR u.name LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Sorting
    if ($sort === 'rating') {
        $sql .= " ORDER BY avg_rating DESC, a.created_at DESC";
    } elseif ($sort === 'likes') {
        $sql .= " ORDER BY likes_count DESC, a.created_at DESC";
    } elseif ($sort === 'views') {
        $sql .= " ORDER BY a.views_count DESC, a.created_at DESC";
    } else {
        $sql .= " ORDER BY a.created_at DESC";
    }

    if ($limit > 0) {
        $sql .= " LIMIT " . (int)$limit;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $artworks = $stmt->fetchAll();

    // Add user-specific flags (liked, rated) if logged in
    foreach ($artworks as &$art) {
        $art['avg_rating'] = round((float)$art['avg_rating'], 1);
        $art['is_liked'] = false;
        $art['my_rating'] = 0;

        if ($current_user) {
            $stLike = $db->prepare("SELECT id FROM artwork_likes WHERE artwork_id = ? AND user_id = ?");
            $stLike->execute(array($art['id'], $current_user['id']));
            if ($stLike->fetch()) {
                $art['is_liked'] = true;
            }

            $stRate = $db->prepare("SELECT rating FROM artwork_ratings WHERE artwork_id = ? AND user_id = ?");
            $stRate->execute(array($art['id'], $current_user['id']));
            $rRow = $stRate->fetch();
            if ($rRow) {
                $art['my_rating'] = (int)$rRow['rating'];
            }
        }
    }

    json_response('success', 'Artworks loaded', array('artworks' => $artworks, 'count' => count($artworks)));
}

// -------------------------------------------------------------
// SINGLE ARTWORK DETAIL
// -------------------------------------------------------------
if ($action === 'detail') {
    $id = (int) get_param('id', 0);
    if ($id <= 0) {
        json_response('error', 'Valid artwork ID required.');
    }

    // Increment views counter
    $db->prepare("UPDATE artworks SET views_count = views_count + 1 WHERE id = ?")->execute(array($id));

    $sql = "SELECT a.*, 
                   u.name AS artist_name, u.faculty AS artist_faculty, u.avatar AS artist_avatar, u.student_id AS artist_student_id, u.bio AS artist_bio,
                   c.name AS category_name, c.slug AS category_slug,
                   (SELECT COUNT(*) FROM artwork_likes l WHERE l.artwork_id = a.id) AS likes_count,
                   (SELECT COALESCE(AVG(r.rating), 0) FROM artwork_ratings r WHERE r.artwork_id = a.id) AS avg_rating,
                   (SELECT COUNT(*) FROM artwork_ratings r WHERE r.artwork_id = a.id) AS ratings_count,
                   (SELECT COUNT(*) FROM artwork_comments cm WHERE cm.artwork_id = a.id) AS comments_count
            FROM artworks a
            JOIN users u ON a.user_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute(array($id));
    $artwork = $stmt->fetch();

    if (!$artwork) {
        json_response('error', 'Artwork not found.', array(), 404);
    }

    $artwork['avg_rating'] = round((float)$artwork['avg_rating'], 1);
    $artwork['is_liked'] = false;
    $artwork['my_rating'] = 0;
    $artwork['is_following_artist'] = false;

    if ($current_user) {
        // Liked check
        $stL = $db->prepare("SELECT id FROM artwork_likes WHERE artwork_id = ? AND user_id = ?");
        $stL->execute(array($id, $current_user['id']));
        if ($stL->fetch()) {
            $artwork['is_liked'] = true;
        }

        // Rated check
        $stR = $db->prepare("SELECT rating FROM artwork_ratings WHERE artwork_id = ? AND user_id = ?");
        $stR->execute(array($id, $current_user['id']));
        $rRow = $stR->fetch();
        if ($rRow) {
            $artwork['my_rating'] = (int)$rRow['rating'];
        }

        // Following check
        $stF = $db->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
        $stF->execute(array($current_user['id'], $artwork['user_id']));
        if ($stF->fetch()) {
            $artwork['is_following_artist'] = true;
        }
    }

    // Fetch comments
    $cmtStmt = $db->prepare("SELECT cm.*, u.name AS user_name, u.avatar AS user_avatar, u.faculty AS user_faculty 
                             FROM artwork_comments cm 
                             JOIN users u ON cm.user_id = u.id 
                             WHERE cm.artwork_id = ? 
                             ORDER BY cm.created_at DESC");
    $cmtStmt->execute(array($id));
    $comments = $cmtStmt->fetchAll();

    json_response('success', 'Artwork details loaded', array(
        'artwork' => $artwork,
        'comments' => $comments
    ));
}

// -------------------------------------------------------------
// CREATE ARTWORK (Student or Admin)
// -------------------------------------------------------------
if ($action === 'create') {
    $user = require_auth();
    $data = get_request_data();

    $title = isset($data['title']) ? trim($data['title']) : '';
    $category_id = isset($data['category_id']) ? (int)$data['category_id'] : 0;
    $description = isset($data['description']) ? trim($data['description']) : '';
    $medium = isset($data['medium']) ? trim($data['medium']) : '';
    $dimensions = isset($data['dimensions']) ? trim($data['dimensions']) : 'N/A';
    $image_path = isset($data['image_path']) ? trim($data['image_path']) : '';

    if (empty($title) || $category_id <= 0 || empty($description) || empty($medium) || empty($image_path)) {
        json_response('error', 'All fields including title, category, medium, description, and image are required.');
    }

    // Auto-approve if created by admin; students require admin moderation
    $status = ($user['role'] === 'admin') ? 'approved' : 'pending';

    $stmt = $db->prepare("INSERT INTO artworks (user_id, category_id, title, description, medium, dimensions, image_path, status, is_featured, views_count, created_at, updated_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW(), NOW())");
    $stmt->execute(array(
        $user['id'],
        $category_id,
        $title,
        $description,
        $medium,
        $dimensions,
        $image_path,
        $status
    ));
    $artwork_id = $db->lastInsertId();

    if ($user['role'] === 'student') {
        // Notify student that submission is pending review
        add_notification(
            $user['id'],
            'Artwork Submitted for Review',
            'Your submission "' . $title . '" has been received and is waiting for curator review.',
            'approval',
            'my-submissions.html'
        );

        // Notify admins
        $adminStmt = $db->query("SELECT id FROM users WHERE role = 'admin'");
        while ($admin = $adminStmt->fetch()) {
            add_notification(
                $admin['id'],
                'New Artwork Submission',
                $user['name'] . ' submitted "' . $title . '" for review.',
                'approval',
                'admin-artworks.html'
            );
        }
    }

    json_response('success', 'Artwork submitted successfully!', array('artwork_id' => $artwork_id, 'status' => $status));
}

// -------------------------------------------------------------
// UPDATE ARTWORK
// -------------------------------------------------------------
if ($action === 'update') {
    $user = require_auth();
    $data = get_request_data();
    $id = isset($data['id']) ? (int)$data['id'] : 0;

    if ($id <= 0) {
        json_response('error', 'Valid artwork ID required.');
    }

    // Check ownership
    $stmt = $db->prepare("SELECT * FROM artworks WHERE id = ?");
    $stmt->execute(array($id));
    $art = $stmt->fetch();
    if (!$art) {
        json_response('error', 'Artwork not found.');
    }

    if ($art['user_id'] != $user['id'] && $user['role'] !== 'admin') {
        json_response('error', 'You are not authorized to edit this artwork.', array(), 403);
    }

    $title = isset($data['title']) ? trim($data['title']) : $art['title'];
    $category_id = isset($data['category_id']) ? (int)$data['category_id'] : $art['category_id'];
    $description = isset($data['description']) ? trim($data['description']) : $art['description'];
    $medium = isset($data['medium']) ? trim($data['medium']) : $art['medium'];
    $dimensions = isset($data['dimensions']) ? trim($data['dimensions']) : $art['dimensions'];
    $image_path = (!empty($data['image_path'])) ? trim($data['image_path']) : $art['image_path'];

    // If student edits a rejected artwork, reset status to pending
    $status = $art['status'];
    if ($user['role'] === 'student' && $status === 'rejected') {
        $status = 'pending';
    }

    $upStmt = $db->prepare("UPDATE artworks SET title = ?, category_id = ?, description = ?, medium = ?, dimensions = ?, image_path = ?, status = ?, updated_at = NOW() WHERE id = ?");
    $upStmt->execute(array($title, $category_id, $description, $medium, $dimensions, $image_path, $status, $id));

    json_response('success', 'Artwork updated successfully.');
}

// -------------------------------------------------------------
// DELETE ARTWORK
// -------------------------------------------------------------
if ($action === 'delete') {
    $user = require_auth();
    $id = (int) get_param('id', 0);

    if ($id <= 0) {
        $data = get_request_data();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
    }

    if ($id <= 0) {
        json_response('error', 'Valid artwork ID required.');
    }

    $stmt = $db->prepare("SELECT * FROM artworks WHERE id = ?");
    $stmt->execute(array($id));
    $art = $stmt->fetch();
    if (!$art) {
        json_response('error', 'Artwork not found.');
    }

    if ($art['user_id'] != $user['id'] && $user['role'] !== 'admin') {
        json_response('error', 'You do not have permission to delete this artwork.', array(), 403);
    }

    $delStmt = $db->prepare("DELETE FROM artworks WHERE id = ?");
    $delStmt->execute(array($id));

    json_response('success', 'Artwork deleted successfully.');
}

// -------------------------------------------------------------
// ADMIN: APPROVE OR REJECT ARTWORK
// -------------------------------------------------------------
if ($action === 'review') {
    require_admin();
    $data = get_request_data();
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $status = isset($data['status']) ? trim($data['status']) : '';
    $feedback = isset($data['feedback']) ? trim($data['feedback']) : '';

    if ($id <= 0 || !in_array($status, array('approved', 'rejected'))) {
        json_response('error', 'Valid artwork ID and status (approved or rejected) are required.');
    }

    $stmt = $db->prepare("SELECT a.*, u.id AS creator_id, u.name AS creator_name FROM artworks a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
    $stmt->execute(array($id));
    $art = $stmt->fetch();
    if (!$art) {
        json_response('error', 'Artwork not found.');
    }

    $upStmt = $db->prepare("UPDATE artworks SET status = ?, admin_feedback = ?, updated_at = NOW() WHERE id = ?");
    $upStmt->execute(array($status, $feedback, $id));

    // Send immediate notification to student
    if ($status === 'approved') {
        add_notification(
            $art['creator_id'],
            'Artwork Approved! 🎉',
            'Great news! Your artwork "' . $art['title'] . '" was approved by the curator and is now live.',
            'approval',
            'artwork-detail.html?id=' . $id
        );
    } else {
        $msg = 'Your artwork "' . $art['title'] . '" was rejected by the curator.';
        if (!empty($feedback)) {
            $msg .= ' Reason: ' . $feedback;
        }
        add_notification(
            $art['creator_id'],
            'Submission Needs Revision',
            $msg,
            'approval',
            'my-submissions.html'
        );
    }

    json_response('success', 'Artwork ' . $status . ' successfully.');
}

// -------------------------------------------------------------
// ADMIN: TOGGLE FEATURED
// -------------------------------------------------------------
if ($action === 'toggle_featured') {
    require_admin();
    $data = get_request_data();
    $id = isset($data['id']) ? (int)$data['id'] : 0;

    $stmt = $db->prepare("SELECT * FROM artworks WHERE id = ?");
    $stmt->execute(array($id));
    $art = $stmt->fetch();
    if (!$art) {
        json_response('error', 'Artwork not found.');
    }

    $newFeatured = ($art['is_featured'] == 1) ? 0 : 1;
    $upStmt = $db->prepare("UPDATE artworks SET is_featured = ? WHERE id = ?");
    $upStmt->execute(array($newFeatured, $id));

    if ($newFeatured == 1) {
        add_notification(
            $art['user_id'],
            'Artwork Featured on Homepage! 🌟',
            'Congratulations! "' . $art['title'] . '" has been selected as a featured showcase piece.',
            'approval',
            'artwork-detail.html?id=' . $id
        );
    }

    json_response('success', 'Featured status updated.', array('is_featured' => $newFeatured));
}

json_response('error', 'Unknown action.');
