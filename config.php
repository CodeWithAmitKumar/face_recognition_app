<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'face_recognition_app');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Base URL (Change this to your domain in production)
define('BASE_URL', 'http://localhost/face_recognition_app/');

// Absolute paths for uploads
define('ROOT_PATH', dirname(__FILE__) . '/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('COVERS_PATH', UPLOAD_PATH . 'covers/');
define('ALBUMS_PATH', UPLOAD_PATH . 'albums/');
define('QRCODES_PATH', UPLOAD_PATH . 'qrcodes/');
define('SEARCH_TEMP_PATH', UPLOAD_PATH . 'search_temp/');

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
if (!file_exists(COVERS_PATH)) mkdir(COVERS_PATH, 0755, true);
if (!file_exists(ALBUMS_PATH)) mkdir(ALBUMS_PATH, 0755, true);
if (!file_exists(QRCODES_PATH)) mkdir(QRCODES_PATH, 0755, true);
if (!file_exists(SEARCH_TEMP_PATH)) mkdir(SEARCH_TEMP_PATH, 0755, true);

session_start();
?>
