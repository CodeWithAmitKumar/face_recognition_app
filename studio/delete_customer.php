<?php
require_once '../config.php';
require_once '../functions.php';
requireActiveStudio($conn);

$studio_id = $_SESSION['studio_id'];

if (isset($_GET['id']) && isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $customer_id = intval($_GET['id']);
    
    // Verify customer belongs to this studio
    $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ? AND studio_id = ?");
    $stmt->bind_param("ii", $customer_id, $studio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Get all albums of this customer
        $albums_query = $conn->prepare("SELECT album_id, cover_image, qr_code FROM albums WHERE customer_id = ?");
        $albums_query->bind_param("i", $customer_id);
        $albums_query->execute();
        $albums_result = $albums_query->get_result();
        
        // Delete all album files
        while ($album = $albums_result->fetch_assoc()) {
            // Delete cover image
            if ($album['cover_image'] && file_exists(ROOT_PATH . $album['cover_image'])) {
                unlink(ROOT_PATH . $album['cover_image']);
            }
            
            // Delete QR code
            if ($album['qr_code'] && file_exists(ROOT_PATH . $album['qr_code'])) {
                unlink(ROOT_PATH . $album['qr_code']);
            }
            
            // Delete album folder
            $album_folder = ALBUMS_PATH . $album['album_id'] . "/";
            if (file_exists($album_folder)) {
                $files = glob($album_folder . "*");
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($album_folder);
            }
        }
        
        // Delete customer from database (CASCADE will delete albums, album_images, search_logs)
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: manage_customers.php?deleted=success");
        exit();
    }
    
    $stmt->close();
}

header("Location: manage_customers.php");
exit();
?>
