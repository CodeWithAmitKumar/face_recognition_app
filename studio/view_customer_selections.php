<?php
require_once '../config.php';
require_once '../functions.php';
requireStudioLogin();

$studio_id = $_SESSION['studio_id'];
$success = '';
$error = '';

// Handle approve action
if (isset($_GET['action']) && $_GET['action'] == 'approve' && isset($_GET['album_id'])) {
    $album_id = intval($_GET['album_id']);
    
    // Verify album belongs to this studio
    $verify = $conn->prepare("SELECT * FROM albums WHERE album_id = ? AND studio_id = ?");
    $verify->bind_param("ii", $album_id, $studio_id);
    $verify->execute();
    $verify_result = $verify->get_result();
    
    if ($verify_result->num_rows > 0) {
        $album = $verify_result->fetch_assoc();
        
        // Get all selected image IDs
        $selected_query = $conn->prepare("SELECT image_id FROM customer_photo_selections WHERE album_id = ?");
        $selected_query->bind_param("i", $album_id);
        $selected_query->execute();
        $selected_result = $selected_query->get_result();
        
        $selected_ids = [];
        while ($row = $selected_result->fetch_assoc()) {
            $selected_ids[] = $row['image_id'];
        }
        $selected_query->close();
        
        if (count($selected_ids) > 0) {
            // Get all images in the album
            $all_images_query = $conn->prepare("SELECT image_id, image_path FROM album_images WHERE album_id = ?");
            $all_images_query->bind_param("i", $album_id);
            $all_images_query->execute();
            $all_images_result = $all_images_query->get_result();
            
            $deleted_count = 0;
            $kept_count = count($selected_ids);
            
            // Delete NON-SELECTED photos
            while ($image = $all_images_result->fetch_assoc()) {
                if (!in_array($image['image_id'], $selected_ids)) {
                    // This photo was NOT selected by customer - DELETE IT
                    
                    // 1. Delete physical file
                    $image_file = "../uploads/albums/" . $album_id . "/" . $image['image_path'];
                    if (file_exists($image_file)) {
                        unlink($image_file);
                    }
                    
                    // 2. Delete from database
                    $delete_image = $conn->prepare("DELETE FROM album_images WHERE image_id = ?");
                    $delete_image->bind_param("i", $image['image_id']);
                    $delete_image->execute();
                    $delete_image->close();
                    
                    $deleted_count++;
                }
            }
            $all_images_query->close();
            
            // 3. Update selection status to approved
            $update_selections = $conn->prepare("UPDATE customer_photo_selections SET status = 'approved' WHERE album_id = ?");
            $update_selections->bind_param("i", $album_id);
            $update_selections->execute();
            $update_selections->close();
            
            // 4. Update album status to approved
            $update_album = $conn->prepare("UPDATE albums SET selection_status = 'approved' WHERE album_id = ?");
            $update_album->bind_param("i", $album_id);
            $update_album->execute();
            $update_album->close();
            
            $success = "Selection approved! $kept_count photo(s) kept, $deleted_count photo(s) removed from album.";
        } else {
            $error = "No photos were selected by the customer.";
        }
    } else {
        $error = "Album not found or access denied.";
    }
    $verify->close();
}

// Get all albums with pending selections
$pending_query = $conn->prepare("
    SELECT 
        a.album_id,
        a.album_name,
        a.cover_image,
        a.selection_status,
        a.created_at,
        c.customer_name,
        c.email as customer_email,
        COUNT(cps.selection_id) as selected_count,
        (SELECT COUNT(*) FROM album_images WHERE album_id = a.album_id) as total_photos
    FROM albums a
    JOIN customers c ON a.customer_id = c.customer_id
    LEFT JOIN customer_photo_selections cps ON a.album_id = cps.album_id
    WHERE a.studio_id = ? AND a.selection_status IN ('submitted', 'approved')
    GROUP BY a.album_id
    ORDER BY a.selection_status ASC, a.created_at DESC
");
$pending_query->bind_param("i", $studio_id);
$pending_query->execute();
$albums = $pending_query->get_result();

// Get statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_submitted,
        SUM(CASE WHEN a.selection_status = 'submitted' THEN 1 ELSE 0 END) as pending_approval,
        SUM(CASE WHEN a.selection_status = 'approved' THEN 1 ELSE 0 END) as approved
    FROM albums a
    WHERE a.studio_id = ? AND a.selection_status IN ('submitted', 'approved')
");
$stats_query->bind_param("i", $studio_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Photo Selections</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
        }
        
        .navbar-menu {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .navbar-menu a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }
        
        .navbar-menu a:hover {
            opacity: 0.8;
        }
        
        .badge {
            position: absolute;
            top: -8px;
            right: -12px;
            background: #e74c3c;
            color: white;
            font-size: 11px;
            padding: 3px 7px;
            border-radius: 10px;
            font-weight: 700;
        }
        
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #666;
            font-size: 16px;
        }
        
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .info-box i {
            font-size: 32px;
        }
        
        .info-box-content h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .info-box-content p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        .stat-icon.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.pending { background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); }
        .stat-icon.approved { background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); }
        
        .stat-details h3 {
            color: #333;
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .stat-details p {
            color: #666;
            font-size: 14px;
        }
        
        .success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease;
        }
        
        .error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .albums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .album-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        
        .album-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .album-cover {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }
        
        .album-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-submitted {
            background: #f39c12;
            color: white;
        }
        
        .status-approved {
            background: #2ecc71;
            color: white;
        }
        
        .album-info {
            padding: 25px;
        }
        
        .album-title {
            color: #333;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .customer-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .customer-info p {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .selection-stats {
            display: flex;
            gap: 20px;
            padding: 15px 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
            margin: 15px 0;
        }
        
        .stat-item {
            flex: 1;
            text-align: center;
        }
        
        .stat-item .number {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-item .label {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
            justify-content: center;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .empty-state {
            background: white;
            padding: 80px 40px;
            border-radius: 15px;
            text-align: center;
            color: #999;
        }
        
        .empty-state i {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            .navbar-menu {
                flex-direction: column;
                gap: 10px;
            }
            .albums-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-camera"></i> <?php echo htmlspecialchars($_SESSION['studio_name']); ?>
        </div>
        <div class="navbar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="manage_customers.php"><i class="fas fa-users"></i> Customers</a>
            <a href="create_album.php"><i class="fas fa-plus"></i> Create Album</a>
            <a href="select_album.php"><i class="fas fa-folder"></i> My Albums</a>
            <a href="view_customer_selections.php">
                <i class="fas fa-heart"></i> Selections
                <?php if ($stats['pending_approval'] > 0): ?>
                    <span class="badge"><?php echo $stats['pending_approval']; ?></span>
                <?php endif; ?>
            </a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-heart"></i> Customer Photo Selections</h1>
            <p>Review and approve customer's favorite photos. Non-selected photos will be removed.</p>
        </div>
        
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <div class="info-box-content">
                <h3>How it works</h3>
                <p>Customers select their favorite photos. When you approve, <strong>only selected photos are kept</strong> and all others are permanently deleted from the album.</p>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle" style="font-size: 24px;"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['total_submitted']; ?></h3>
                    <p>Total Submissions</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['pending_approval']; ?></h3>
                    <p>Pending Approval</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['approved']; ?></h3>
                    <p>Approved</p>
                </div>
            </div>
        </div>
        
        <?php if (mysqli_num_rows($albums) > 0): ?>
            <div class="albums-grid">
                <?php while($album = mysqli_fetch_assoc($albums)): ?>
                    <div class="album-card">
                        <div class="album-cover">
                            <img src="../uploads/covers/<?php echo htmlspecialchars($album['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($album['album_name']); ?>">
                            <div class="status-badge status-<?php echo $album['selection_status']; ?>">
                                <?php 
                                if ($album['selection_status'] == 'submitted') 
                                    echo '⏳ Pending Approval';
                                else 
                                    echo '✓ Approved';
                                ?>
                            </div>
                        </div>
                        <div class="album-info">
                            <h3 class="album-title"><?php echo htmlspecialchars($album['album_name']); ?></h3>
                            
                            <div class="customer-info">
                                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($album['customer_name']); ?></p>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($album['customer_email']); ?></p>
                                <p><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($album['created_at'])); ?></p>
                            </div>
                            
                            <div class="selection-stats">
                                <div class="stat-item">
                                    <div class="number"><?php echo $album['selected_count']; ?></div>
                                    <div class="label">Selected</div>
                                </div>
                                <div class="stat-item">
                                    <div class="number"><?php echo $album['total_photos'] - $album['selected_count']; ?></div>
                                    <div class="label">To Remove</div>
                                </div>
                                <div class="stat-item">
                                    <div class="number"><?php echo $album['total_photos']; ?></div>
                                    <div class="label">Total</div>
                                </div>
                            </div>
                            
                            <?php if ($album['selection_status'] == 'submitted'): ?>
                                <a href="?action=approve&album_id=<?php echo $album['album_id']; ?>" 
                                   onclick="return confirm('⚠️ Approve selection?\n\n✓ Keep: <?php echo $album['selected_count']; ?> photo(s)\n✗ Delete: <?php echo $album['total_photos'] - $album['selected_count']; ?> photo(s)\n\nThis cannot be undone!');"
                                   class="btn btn-success">
                                    <i class="fas fa-check"></i> Approve Selection
                                </a>
                            <?php else: ?>
                                <a href="upload_images.php?album_id=<?php echo $album['album_id']; ?>" 
                                   class="btn btn-info">
                                    <i class="fas fa-folder-open"></i> View Album
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken"></i>
                <h3>No Submissions Yet</h3>
                <p>No customers have submitted their photo selections.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
