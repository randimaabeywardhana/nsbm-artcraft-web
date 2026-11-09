<?php
/**
 * NSBM ArtCraft - Categories API Controller
 * PHP 5.4.3 compatible
 */
require_once __DIR__ . '/config.php';

$action = get_param('action', 'list');
$db = get_db();

if ($action === 'list') {
    $sql = "SELECT c.*, 
                   (SELECT COUNT(*) FROM artworks a WHERE a.category_id = c.id AND a.status = 'approved') AS artworks_count
            FROM categories c
            ORDER BY c.name ASC";
    $stmt = $db->query($sql);
    $categories = $stmt->fetchAll();

    json_response('success', 'Categories loaded', array('categories' => $categories));
}

if ($action === 'create') {
    require_admin();
    $data = get_request_data();
    $name = isset($data['name']) ? trim($data['name']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $icon_image = isset($data['icon_image']) ? trim($data['icon_image']) : 'assets/images/cat-painting.png';

    if (empty($name)) {
        json_response('error', 'Category name is required.');
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

    // Duplicate check
    $stCheck = $db->prepare("SELECT id FROM categories WHERE slug = ? OR name = ?");
    $stCheck->execute(array($slug, $name));
    if ($stCheck->fetch()) {
        json_response('error', 'A category with this name already exists.');
    }

    $stmt = $db->prepare("INSERT INTO categories (name, slug, description, icon_image, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute(array($name, $slug, $description, $icon_image));

    json_response('success', 'Category created successfully.', array('id' => $db->lastInsertId()));
}

if ($action === 'update') {
    require_admin();
    $data = get_request_data();
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $name = isset($data['name']) ? trim($data['name']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $icon_image = isset($data['icon_image']) ? trim($data['icon_image']) : '';

    if ($id <= 0 || empty($name)) {
        json_response('error', 'Valid category ID and name are required.');
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

    $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon_image = IF(? != '', ?, icon_image) WHERE id = ?");
    $stmt->execute(array($name, $slug, $description, $icon_image, $icon_image, $id));

    json_response('success', 'Category updated successfully.');
}

if ($action === 'delete') {
    require_admin();
    $id = (int) get_param('id', 0);
    if ($id <= 0) {
        $data = get_request_data();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
    }

    if ($id <= 0) {
        json_response('error', 'Valid category ID required.');
    }

    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute(array($id));

    json_response('success', 'Category deleted successfully.');
}

json_response('error', 'Unknown action.');
