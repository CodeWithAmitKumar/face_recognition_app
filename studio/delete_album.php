<?php
require_once '../config.php';
require_once '../functions.php';
requireStudioLogin();


$studio_id = $_SESSION['studio_id'];


if (isset($_GET['id'])) {
    $album_id = intval($_GET['id']);
    
    // Verify album belongs to this studio
    $stmt = $conn->prepare("SELECT * FROM albums WHERE album_id = ? AND studio_id = ?");
    $stmt->bind_param("ii", $album_id, $studio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $album = $result->fetch_assoc();
        
        // ✅ 1. Delete cover image (NOW STORES ONLY FILENAME)
        if (!empty($album['cover_image'])) {
            $cover_path = "../uploads/covers/" . $album['cover_image'];
            if (file_exists($cover_path)) {
                unlink($cover_path);
            }
        }
        
        // ✅ 2. Delete QR code
        if (!empty($album['qr_code']) && file_exists(ROOT_PATH . $album['qr_code'])) {
            unlink(ROOT_PATH . $album['qr_code']);
        }
        
        // ✅ 3. Delete all album images from database first
        $images_query = $conn->prepare("SELECT image_path FROM album_images WHERE album_id = ?");
        $images_query->bind_param("i", $album_id);
        $images_query->execute();
        $images_result = $images_query->get_result();
        
        // Delete each image file from folder
        while ($image = $images_result->fetch_assoc()) {
            $image_file = "../uploads/albums/" . $album_id . "/" . $image['image_path'];
            if (file_exists($image_file)) {
                unlink($image_file);
            }
        }
        $images_query->close();
        
        // Delete album images records from database
        $delete_images = $conn->prepare("DELETE FROM album_images WHERE album_id = ?");
        $delete_images->bind_param("i", $album_id);
        $delete_images->execute();
        $delete_images->close();
        
        // ✅ 4. Delete album folder
        $album_folder = ALBUMS_PATH . $album_id . "/";
        if (file_exists($album_folder) && is_dir($album_folder)) {
            // Remove any remaining files
            $files = glob($album_folder . "*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            // Remove directory
            rmdir($album_folder);
        }
        
        // ✅ 5. Delete search logs for this album
        $delete_logs = $conn->prepare("DELETE FROM search_logs WHERE album_id = ?");
        $delete_logs->bind_param("i", $album_id);
        $delete_logs->execute();
        $delete_logs->close();
        
        // ✅ 6. Finally delete album from database
        $delete_stmt = $conn->prepare("DELETE FROM albums WHERE album_id = ?");
        $delete_stmt->bind_param("i", $album_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        $stmt->close();
        header("Location: select_album.php?deleted=success");
        exit();
    }
    
    $stmt->close();
}


header("Location: select_album.php");
exit();
?>
