<?php
/**
 * NSBM ArtCraft - Reports & Analytics API Controller
 * Submission, engagement & exhibition participation reporting + CSV Export
 * PHP 5.4.3 compatible
 */
require_once __DIR__ . '/config.php';

$action = get_param('action', 'summary');
$db = get_db();
require_admin();

if ($action === 'summary') {
    // 1. Overall counts
    $stats = array();

    $stats['total_artworks'] = (int)$db->query("SELECT COUNT(*) FROM artworks")->fetchColumn();
    $stats['approved_artworks'] = (int)$db->query("SELECT COUNT(*) FROM artworks WHERE status = 'approved'")->fetchColumn();
    $stats['pending_artworks'] = (int)$db->query("SELECT COUNT(*) FROM artworks WHERE status = 'pending'")->fetchColumn();
    $stats['rejected_artworks'] = (int)$db->query("SELECT COUNT(*) FROM artworks WHERE status = 'rejected'")->fetchColumn();

    $stats['total_students'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $stats['total_exhibitions'] = (int)$db->query("SELECT COUNT(*) FROM exhibitions")->fetchColumn();
    $stats['total_participants'] = (int)$db->query("SELECT COUNT(*) FROM exhibition_participants")->fetchColumn();

    $stats['total_likes'] = (int)$db->query("SELECT COUNT(*) FROM artwork_likes")->fetchColumn();
    $stats['total_ratings'] = (int)$db->query("SELECT COUNT(*) FROM artwork_ratings")->fetchColumn();
    $stats['total_comments'] = (int)$db->query("SELECT COUNT(*) FROM artwork_comments")->fetchColumn();
    $stats['total_views'] = (int)$db->query("SELECT COALESCE(SUM(views_count), 0) FROM artworks")->fetchColumn();

    // 2. Category distribution
    $catSql = "SELECT c.name, COUNT(a.id) AS total_submissions, 
                      SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
                      SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) AS pending_count
               FROM categories c
               LEFT JOIN artworks a ON c.id = a.category_id
               GROUP BY c.id, c.name
               ORDER BY total_submissions DESC";
    $stats['category_stats'] = $db->query($catSql)->fetchAll();

    // 3. Faculty participation
    $facSql = "SELECT u.faculty, COUNT(DISTINCT u.id) AS student_count, COUNT(a.id) AS artwork_count
               FROM users u
               LEFT JOIN artworks a ON u.id = a.user_id
               WHERE u.role = 'student'
               GROUP BY u.faculty
               ORDER BY artwork_count DESC";
    $stats['faculty_stats'] = $db->query($facSql)->fetchAll();

    // 4. Top 5 Liked Artworks
    $topLikedSql = "SELECT a.id, a.title, a.medium, a.image_path, u.name AS artist_name, COUNT(l.id) AS likes_count
                    FROM artworks a
                    JOIN users u ON a.user_id = u.id
                    LEFT JOIN artwork_likes l ON a.id = l.artwork_id
                    WHERE a.status = 'approved'
                    GROUP BY a.id, a.title, a.medium, a.image_path, u.name
                    ORDER BY likes_count DESC
                    LIMIT 5";
    $stats['top_liked'] = $db->query($topLikedSql)->fetchAll();

    // 5. Top 5 Rated Artworks
    $topRatedSql = "SELECT a.id, a.title, a.medium, a.image_path, u.name AS artist_name, 
                           COALESCE(AVG(r.rating), 0) AS avg_rate, COUNT(r.id) AS ratings_count
                    FROM artworks a
                    JOIN users u ON a.user_id = u.id
                    JOIN artwork_ratings r ON a.id = r.artwork_id
                    WHERE a.status = 'approved'
                    GROUP BY a.id, a.title, a.medium, a.image_path, u.name
                    HAVING ratings_count >= 1
                    ORDER BY avg_rate DESC, ratings_count DESC
                    LIMIT 5";
    $topRated = $db->query($topRatedSql)->fetchAll();
    foreach ($topRated as &$tr) {
        $tr['avg_rate'] = round((float)$tr['avg_rate'], 1);
    }
    $stats['top_rated'] = $topRated;

    // 6. Recent Submissions Queue (pending)
    $pendingSql = "SELECT a.id, a.title, a.medium, a.created_at, a.image_path, u.name AS artist_name, u.student_id, c.name AS category_name
                   FROM artworks a
                   JOIN users u ON a.user_id = u.id
                   JOIN categories c ON a.category_id = c.id
                   WHERE a.status = 'pending'
                   ORDER BY a.created_at ASC";
    $stats['pending_queue'] = $db->query($pendingSql)->fetchAll();

    json_response('success', 'Reports summary loaded', array('stats' => $stats));
}

if ($action === 'export_csv') {
    $type = get_param('type', 'artworks'); // 'artworks' or 'exhibitions'

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="nsbm-artcraft-report-' . $type . '-' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($type === 'artworks') {
        fputcsv($output, array('Artwork ID', 'Title', 'Artist Name', 'Student ID', 'Faculty', 'Category', 'Medium', 'Dimensions', 'Status', 'Views', 'Likes', 'Avg Rating', 'Created At'));

        $sql = "SELECT a.id, a.title, u.name AS artist_name, u.student_id, u.faculty, c.name AS category_name, 
                       a.medium, a.dimensions, a.status, a.views_count,
                       (SELECT COUNT(*) FROM artwork_likes l WHERE l.artwork_id = a.id) AS likes_count,
                       (SELECT COALESCE(AVG(r.rating), 0) FROM artwork_ratings r WHERE r.artwork_id = a.id) AS avg_rate,
                       a.created_at
                FROM artworks a
                JOIN users u ON a.user_id = u.id
                JOIN categories c ON a.category_id = c.id
                ORDER BY a.id ASC";
        $rows = $db->query($sql)->fetchAll();

        foreach ($rows as $r) {
            fputcsv($output, array(
                $r['id'],
                $r['title'],
                $r['artist_name'],
                $r['student_id'],
                $r['faculty'],
                $r['category_name'],
                $r['medium'],
                $r['dimensions'],
                ucfirst($r['status']),
                $r['views_count'],
                $r['likes_count'],
                round((float)$r['avg_rate'], 1),
                $r['created_at']
            ));
        }
    } elseif ($type === 'exhibitions') {
        fputcsv($output, array('Exhibition ID', 'Exhibition Title', 'Venue', 'Participant Name', 'Student ID', 'Faculty', 'Showcased Artwork', 'Notes', 'Registration Date'));

        $sql = "SELECT e.id AS ex_id, e.title AS ex_title, e.venue, u.name AS participant_name, u.student_id, u.faculty,
                       COALESCE(a.title, 'General Participation') AS art_title, ep.notes, ep.registered_at
                FROM exhibition_participants ep
                JOIN exhibitions e ON ep.exhibition_id = e.id
                JOIN users u ON ep.user_id = u.id
                LEFT JOIN artworks a ON ep.artwork_id = a.id
                ORDER BY e.id ASC, ep.registered_at ASC";
        $rows = $db->query($sql)->fetchAll();

        foreach ($rows as $r) {
            fputcsv($output, array(
                $r['ex_id'],
                $r['ex_title'],
                $r['venue'],
                $r['participant_name'],
                $r['student_id'],
                $r['faculty'],
                $r['art_title'],
                $r['notes'],
                $r['registered_at']
            ));
        }
    }

    fclose($output);
    exit;
}

json_response('error', 'Unknown action.');
