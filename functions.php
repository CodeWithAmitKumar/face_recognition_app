<?php
// Clean input data
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in as admin
function requireAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Check if user is logged in as studio
function requireStudioLogin() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'studio') {
        header("Location: login.php");
        exit();
    }
}

// Check if user is logged in as customer
function requireCustomerLogin() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'customer') {
        header("Location: login.php");
        exit();
    }
}

// Generate random 6-digit password
function generatePassword($length = 6) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}

// Get user type name
function getUserTypeName($type) {
    switch($type) {
        case 'admin':
            return 'Administrator';
        case 'studio':
            return 'Studio';
        case 'customer':
            return 'Customer';
        default:
            return 'Unknown';
    }
}

// Check if file is image
function isImage($file) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    return in_array($file['type'], $allowed_types);
}

// Generate unique filename
function generateUniqueFilename($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

// Create directory if not exists
function createDirectory($path) {
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
}

// Delete directory with files
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

// Get album statistics
function getAlbumStats($conn, $album_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM album_images WHERE album_id = ?");
    $stmt->bind_param("i", $album_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['count'];
}

// Get customer album count
function getCustomerAlbumCount($conn, $customer_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM albums WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['count'];
}

// Get studio statistics
function getStudioStats($conn, $studio_id) {
    $stats = [];
    
    // Total customers
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM customers WHERE studio_id = ?");
    $stmt->bind_param("i", $studio_id);
    $stmt->execute();
    $stats['customers'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // Total albums
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM albums WHERE studio_id = ?");
    $stmt->bind_param("i", $studio_id);
    $stmt->execute();
    $stats['albums'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // Total images
    $stmt = $conn->prepare("SELECT COUNT(ai.image_id) as count FROM album_images ai JOIN albums a ON ai.album_id = a.album_id WHERE a.studio_id = ?");
    $stmt->bind_param("i", $studio_id);
    $stmt->execute();
    $stats['images'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    return $stats;
}

// Log activity
function logActivity($conn, $user_type, $user_id, $action, $details = '') {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_type, user_id, action, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $user_type, $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}
?>
