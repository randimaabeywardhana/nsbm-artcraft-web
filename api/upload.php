<?php
/**
 * NSBM ArtCraft - Image Upload Handler
 * Validates and stores PNG artwork files directly
 * PHP 5.4.3 compatible
 */
require_once __DIR__ . '/config.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
    json_response('error', 'No image file uploaded.');
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response('error', 'Upload error code: ' . $file['error']);
}

// Max 10MB
if ($file['size'] > 10 * 1024 * 1024) {
    json_response('error', 'File size exceeds 10MB limit.');
}

// Check file extension
$filename = $file['name'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Enforce PNG as strictly requested
if ($ext !== 'png') {
    // If user uploaded jpg/jpeg, we can convert it to PNG using GD if available, or reject
    if (in_array($ext, array('jpg', 'jpeg', 'webp', 'gif')) && function_exists('imagecreatefromjpeg')) {
        // We will convert to PNG for strict adherence to user's PNG requirement!
    } else {
        json_response('error', 'Only .png image files are permitted. Please upload a PNG file.');
    }
}

// Target folder
$uploadDir = __DIR__ . '/../assets/images/uploads/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

$cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($filename, PATHINFO_FILENAME));
$newFileName = 'art_' . time() . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 8) . '.png';
$targetPath = $uploadDir . $newFileName;

// If already PNG
if ($ext === 'png') {
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        json_response('success', 'PNG image uploaded successfully.', array(
            'image_path' => 'assets/images/uploads/' . $newFileName
        ));
    } else {
        json_response('error', 'Failed to move uploaded file.');
    }
} else {
    // Convert to PNG using GD
    $sourceImg = null;
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $sourceImg = @imagecreatefromjpeg($file['tmp_name']);
    } elseif ($ext === 'gif') {
        $sourceImg = @imagecreatefromgif($file['tmp_name']);
    }

    if ($sourceImg && imagepng($sourceImg, $targetPath)) {
        imagedestroy($sourceImg);
        json_response('success', 'Image converted and uploaded as PNG successfully.', array(
            'image_path' => 'assets/images/uploads/' . $newFileName
        ));
    } else {
        json_response('error', 'Could not process and save image as PNG.');
    }
}
