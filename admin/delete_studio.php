<?php
require_once '../config.php';
require_once '../functions.php';
requireAdminLogin();

if (isset($_GET['id'])) {
    $studio_id = intval($_GET['id']);
    
    // Get studio details first
    $stmt = $conn->prepare("SELECT studio_name FROM studios WHERE studio_id = ?");
    $stmt->bind_param("i", $studio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $studio = $result->fetch_assoc();
        
        // Get all albums for this studio
        $albums_query = $conn->prepare("SELECT album_id, cover_image, qr_code FROM albums WHERE studio_id = ?");
        $albums_query->bind_param("i", $studio_id);
        $albums_query->execute();
        $albums_result = $albums_query->get_result();
        
        // Delete all album files and folders
        while ($album = $albums_result->fetch_assoc()) {
            // Delete cover image
            if ($album['cover_image'] && file_exists("../" . $album['cover_image'])) {
                unlink("../" . $album['cover_image']);
            }
            
            // Delete QR code
            if ($album['qr_code'] && file_exists("../" . $album['qr_code'])) {
                unlink("../" . $album['qr_code']);
            }
            
            // Delete album folder with all images
            $album_folder = "../uploads/albums/" . $album['album_id'] . "/";
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
        
        $albums_query->close();
        
        // Delete studio from database (CASCADE will delete related records)
        $delete_stmt = $conn->prepare("DELETE FROM studios WHERE studio_id = ?");
        $delete_stmt->bind_param("i", $studio_id);
        
        if ($delete_stmt->execute()) {
            $delete_stmt->close();
            $stmt->close();
            header("Location: manage_studios.php?deleted=success");
            exit();
        } else {
            $delete_stmt->close();
            $stmt->close();
            header("Location: manage_studios.php?error=delete_failed");
            exit();
        }
    } else {
        $stmt->close();
        header("Location: manage_studios.php");
        exit();
    }
}

header("Location: manage_studios.php");
exit();
?>
