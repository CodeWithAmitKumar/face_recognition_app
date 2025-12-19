<?php
require_once '../config.php';
require_once '../functions.php';
requireCustomerLogin();

$customer_id = $_SESSION['customer_id'];
$album_id = isset($_GET['album_id']) ? intval($_GET['album_id']) : 0;

if ($album_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

// Verify album belongs to this customer
$stmt = $conn->prepare("SELECT a.*, s.studio_name, s.studio_id FROM albums a JOIN studios s ON a.studio_id = s.studio_id WHERE a.album_id = ? AND a.customer_id = ?");
$stmt->bind_param("ii", $album_id, $customer_id);
$stmt->execute();
$album_result = $stmt->get_result();

if ($album_result->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}

$album = $album_result->fetch_assoc();
$stmt->close();

// Handle photo selection submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] == 'add_selections' && isset($_POST['selected_photos'])) {
        // Add photos to selection
        $selected_photos = $_POST['selected_photos'];
        
        if (count($selected_photos) > 0) {
            $success_count = 0;
            
            foreach ($selected_photos as $image_id) {
                $image_id = intval($image_id);
                
                // Check if already selected
                $check = $conn->prepare("SELECT selection_id FROM customer_photo_selections WHERE customer_id = ? AND album_id = ? AND image_id = ?");
                $check->bind_param("iii", $customer_id, $album_id, $image_id);
                $check->execute();
                $check_result = $check->get_result();
                
                if ($check_result->num_rows == 0) {
                    // Insert new selection
                    $insert = $conn->prepare("INSERT INTO customer_photo_selections (customer_id, album_id, image_id, status) VALUES (?, ?, ?, 'pending')");
                    $insert->bind_param("iii", $customer_id, $album_id, $image_id);
                    if ($insert->execute()) {
                        $success_count++;
                    }
                    $insert->close();
                }
                $check->close();
            }
            
            if ($success_count > 0) {
                $success = "$success_count photo(s) added to your selection!";
            }
        }
    } elseif ($_POST['action'] == 'submit_final') {
        // Submit final selection to studio
        
        // Count selected photos
        $count_query = $conn->prepare("SELECT COUNT(*) as count FROM customer_photo_selections WHERE customer_id = ? AND album_id = ?");
        $count_query->bind_param("ii", $customer_id, $album_id);
        $count_query->execute();
        $count_result = $count_query->get_result()->fetch_assoc();
        $selection_count = $count_result['count'];
        $count_query->close();
        
        if ($selection_count > 0) {
            // Update album status to submitted
            $update_album = $conn->prepare("UPDATE albums SET selection_status = 'submitted' WHERE album_id = ?");
            $update_album->bind_param("i", $album_id);
            $update_album->execute();
            $update_album->close();
            
            $success = "Final selection submitted! Studio will review your $selection_count selected photo(s).";
            // Reload album data
            $stmt = $conn->prepare("SELECT a.*, s.studio_name, s.studio_id FROM albums a JOIN studios s ON a.studio_id = s.studio_id WHERE a.album_id = ? AND a.customer_id = ?");
            $stmt->bind_param("ii", $album_id, $customer_id);
            $stmt->execute();
            $album = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Please select at least one photo before submitting!";
        }
    }
}

// Get all images for this album with selection status
$images_query = $conn->prepare("
    SELECT ai.*, 
           cps.selection_id, 
           cps.status as selection_status 
    FROM album_images ai 
    LEFT JOIN customer_photo_selections cps 
        ON ai.image_id = cps.image_id 
        AND cps.customer_id = ? 
    WHERE ai.album_id = ? 
    ORDER BY ai.uploaded_at DESC
");
$images_query->bind_param("ii", $customer_id, $album_id);
$images_query->execute();
$images = $images_query->get_result();

// Count selected photos
$selected_count_query = $conn->prepare("SELECT COUNT(*) as count FROM customer_photo_selections WHERE customer_id = ? AND album_id = ?");
$selected_count_query->bind_param("ii", $customer_id, $album_id);
$selected_count_query->execute();
$selected_count = $selected_count_query->get_result()->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($album['album_name']); ?> - View Album</title>
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
        }
        
        .navbar-menu a:hover {
            opacity: 0.8;
        }
        
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .album-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .album-header h1 {
            color: #333;
            font-size: 28px;
        }
        
        .album-stats {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 14px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .album-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .album-stats i {
            color: #667eea;
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 15px;
        }
        
        .status-open {
            background: #e8f5e9;
            color: #27ae60;
        }
        
        .status-submitted {
            background: #fff3e0;
            color: #f39c12;
        }
        
        .status-approved {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .info-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .info-banner i {
            font-size: 32px;
        }
        
        .info-banner-content h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .info-banner-content p {
            font-size: 14px;
            opacity: 0.9;
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
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 18px;
            padding: 15px 35px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
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
        
        .selection-bar {
            position: fixed;
            bottom: -100px;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.2);
            transition: 0.3s;
            z-index: 1000;
        }
        
        .selection-bar.active {
            bottom: 0;
        }
        
        .selection-bar-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .selection-count {
            color: white;
            font-size: 18px;
            font-weight: 600;
        }
        
        .selection-actions {
            display: flex;
            gap: 15px;
        }
        
        .images-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-header h2 {
            color: #333;
            font-size: 24px;
        }
        
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .image-card {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: 0.3s;
            cursor: pointer;
        }
        
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .image-card.selected {
            box-shadow: 0 0 0 4px #2ecc71;
        }
        
        .image-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
        }
        
        .checkbox-overlay {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 10;
        }
        
        .checkbox-overlay input[type="checkbox"] {
            width: 25px;
            height: 25px;
            cursor: pointer;
            accent-color: #2ecc71;
        }
        
        .selected-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #2ecc71;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            z-index: 10;
        }
        
        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%);
            padding: 20px;
            opacity: 0;
            transition: 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .image-card:hover .image-overlay {
            opacity: 1;
        }
        
        .image-date {
            color: white;
            font-size: 12px;
        }
        
        .download-btn {
            background: white;
            color: #667eea;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .download-btn:hover {
            background: #667eea;
            color: white;
            transform: scale(1.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        /* Lightbox Modal */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.95);
        }
        
        .lightbox.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lightbox-content {
            max-width: 90%;
            max-height: 90%;
            position: relative;
        }
        
        .lightbox-content img {
            max-width: 100%;
            max-height: 90vh;
            display: block;
            margin: auto;
            box-shadow: 0 10px 50px rgba(0,0,0,0.5);
        }
        
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
            transition: 0.3s;
        }
        
        .lightbox-close:hover {
            color: #667eea;
        }
        
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            padding: 20px;
            user-select: none;
            transition: 0.3s;
        }
        
        .lightbox-nav:hover {
            color: #667eea;
        }
        
        .lightbox-prev {
            left: 20px;
        }
        
        .lightbox-next {
            right: 20px;
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
            .album-header {
                flex-direction: column;
            }
            .images-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-images"></i> My Photo Albums
        </div>
        <div class="navbar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> My Albums</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
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
        
        <div class="album-header">
            <div>
                <h1><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($album['album_name']); ?></h1>
                <div class="album-stats">
                    <span><i class="fas fa-camera"></i> <?php echo htmlspecialchars($album['studio_name']); ?></span>
                    <span><i class="fas fa-images"></i> <?php echo mysqli_num_rows($images); ?> photos</span>
                    <span><i class="fas fa-heart"></i> <?php echo $selected_count; ?> selected</span>
                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($album['created_at'])); ?></span>
                </div>
                
                <div class="status-indicator status-<?php echo $album['selection_status']; ?>">
                    <?php
                    if ($album['selection_status'] == 'open') {
                        echo '<i class="fas fa-hand-pointer"></i> Select your favorite photos';
                    } elseif ($album['selection_status'] == 'submitted') {
                        echo '<i class="fas fa-clock"></i> Waiting for studio approval';
                    } else {
                        echo '<i class="fas fa-check-circle"></i> Approved by studio';
                    }
                    ?>
                </div>
            </div>
            
            <div class="action-buttons">
                <?php if ($album['selection_status'] == 'open'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="submit_final">
                        <button type="submit" class="btn btn-primary" <?php echo $selected_count == 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane"></i> Submit Final Selection (<?php echo $selected_count; ?>)
                        </button>
                    </form>
                <?php endif; ?>
                
                <button onclick="downloadAll()" class="btn btn-success">
                    <i class="fas fa-download"></i> Download All
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Albums
                </a>
            </div>
        </div>
        
        <?php if ($album['selection_status'] == 'open'): ?>
            <div class="info-banner">
                <i class="fas fa-info-circle"></i>
                <div class="info-banner-content">
                    <h3>Select Your Favorite Photos</h3>
                    <p>Choose the photos you want to keep. After you submit, the studio will approve your selection and remove the rest.</p>
                </div>
            </div>
        <?php elseif ($album['selection_status'] == 'submitted'): ?>
            <div class="info-banner" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                <i class="fas fa-hourglass-half"></i>
                <div class="info-banner-content">
                    <h3>Selection Submitted</h3>
                    <p>Your selection of <?php echo $selected_count; ?> photo(s) has been sent to the studio for approval.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="info-banner" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                <i class="fas fa-check-circle"></i>
                <div class="info-banner-content">
                    <h3>Selection Approved!</h3>
                    <p>The studio has approved your selection. These are your final photos!</p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="images-section">
            <div class="section-header">
                <h2><i class="fas fa-images"></i> Album Photos (<?php echo mysqli_num_rows($images); ?>)</h2>
            </div>
            
            <?php if (mysqli_num_rows($images) > 0): ?>
                <form method="POST" id="selectionForm">
                    <input type="hidden" name="action" value="add_selections">
                    <div class="images-grid">
                        <?php $index = 0; while($image = mysqli_fetch_assoc($images)): ?>
                            <div class="image-card <?php echo isset($image['selection_id']) ? 'selected' : ''; ?>" 
                                 data-image-id="<?php echo $image['image_id']; ?>">
                                
                                <?php if ($album['selection_status'] == 'open'): ?>
                                    <?php if (!isset($image['selection_id'])): ?>
                                        <div class="checkbox-overlay">
                                            <input type="checkbox" 
                                                   name="selected_photos[]" 
                                                   value="<?php echo $image['image_id']; ?>"
                                                   onchange="updateSelectionCount()">
                                        </div>
                                    <?php else: ?>
                                        <div class="selected-badge">
                                            ✓ Selected
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (isset($image['selection_id'])): ?>
                                        <div class="selected-badge">
                                            ✓ Selected
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <img src="../uploads/albums/<?php echo $album_id . '/' . htmlspecialchars($image['image_path']); ?>" 
                                     alt="Album Image"
                                     onclick="openLightbox(<?php echo $index; ?>)"
                                     data-src="../uploads/albums/<?php echo $album_id . '/' . htmlspecialchars($image['image_path']); ?>">
                                <div class="image-overlay">
                                    <div class="image-date">
                                        <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($image['uploaded_at'])); ?>
                                    </div>
                                    <a href="../uploads/albums/<?php echo $album_id . '/' . htmlspecialchars($image['image_path']); ?>" 
                                       download="photo_<?php echo $index + 1; ?>.<?php echo pathinfo($image['image_path'], PATHINFO_EXTENSION); ?>"
                                       class="download-btn"
                                       onclick="event.stopPropagation();"
                                       title="Download Image">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        <?php $index++; endwhile; ?>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-image"></i>
                    <h3>No Photos Yet</h3>
                    <p>This album is empty.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Selection Bar (only show if album is open) -->
    <?php if ($album['selection_status'] == 'open'): ?>
    <div class="selection-bar" id="selectionBar">
        <div class="selection-bar-content">
            <div class="selection-count">
                <i class="fas fa-check-circle"></i> <span id="selectedCount">0</span> photo(s) selected
            </div>
            <div class="selection-actions">
                <button type="button" onclick="clearSelection()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </button>
                <button type="button" onclick="submitSelection()" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add to Selection
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <span class="lightbox-prev" onclick="changeImage(-1)">&#10094;</span>
        <span class="lightbox-nav lightbox-next" onclick="changeImage(1)">&#10095;</span>
        <div class="lightbox-content">
            <img id="lightbox-img" src="" alt="Full View">
        </div>
    </div>
    
    <script>
        let currentImageIndex = 0;
        const images = document.querySelectorAll('.image-card img');
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const selectionBar = document.getElementById('selectionBar');
        const selectedCountSpan = document.getElementById('selectedCount');
        
        function openLightbox(index) {
            currentImageIndex = index;
            lightboxImg.src = images[index].getAttribute('data-src');
            lightbox.classList.add('active');
        }
        
        function closeLightbox() {
            lightbox.classList.remove('active');
        }
        
        function changeImage(direction) {
            currentImageIndex += direction;
            if (currentImageIndex < 0) {
                currentImageIndex = images.length - 1;
            } else if (currentImageIndex >= images.length) {
                currentImageIndex = 0;
            }
            lightboxImg.src = images[currentImageIndex].getAttribute('data-src');
        }
        
        function updateSelectionCount() {
            const checkboxes = document.querySelectorAll('input[name="selected_photos[]"]:checked');
            const count = checkboxes.length;
            if (selectedCountSpan) {
                selectedCountSpan.textContent = count;
            }
            
            if (selectionBar) {
                if (count > 0) {
                    selectionBar.classList.add('active');
                } else {
                    selectionBar.classList.remove('active');
                }
            }
        }
        
        function clearSelection() {
            const checkboxes = document.querySelectorAll('input[name="selected_photos[]"]');
            checkboxes.forEach(cb => cb.checked = false);
            updateSelectionCount();
        }
        
        function submitSelection() {
            const checkboxes = document.querySelectorAll('input[name="selected_photos[]"]:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one photo!');
                return;
            }
            
            document.getElementById('selectionForm').submit();
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (lightbox.classList.contains('active')) {
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') changeImage(-1);
                if (e.key === 'ArrowRight') changeImage(1);
            }
        });
        
        // Click outside image to close
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });
        
        // Download all images function
        function downloadAll() {
            const allImages = document.querySelectorAll('.image-card img');
            let delay = 0;
            
            allImages.forEach((img, index) => {
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = img.getAttribute('data-src');
                    link.download = 'photo_' + (index + 1) + '.jpg';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }, delay);
                delay += 300;
            });
        }
    </script>
</body>
</html>
