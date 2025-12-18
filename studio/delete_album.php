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
        
        // Delete cover image
        if ($album['cover_image'] && file_exists(ROOT_PATH . $album['cover_image'])) {
            unlink(ROOT_PATH . $album['cover_image']);
        }
        
        // Delete QR code
        if ($album['qr_code'] && file_exists(ROOT_PATH . $album['qr_code'])) {
            unlink(ROOT_PATH . $album['qr_code']);
        }
        
        // Delete album folder
        $album_folder = ALBUMS_PATH . $album_id . "/";
        if (file_exists($album_folder)) {
            $files = glob($album_folder . "*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($album_folder);
        }
        
        // Delete from database
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
