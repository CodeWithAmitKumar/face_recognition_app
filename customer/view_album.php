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
$stmt = $conn->prepare("SELECT a.*, s.studio_name FROM albums a JOIN studios s ON a.studio_id = s.studio_id WHERE a.album_id = ? AND a.customer_id = ?");
$stmt->bind_param("ii", $album_id, $customer_id);
$stmt->execute();
$album_result = $stmt->get_result();


if ($album_result->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}


$album = $album_result->fetch_assoc();
$stmt->close();


// Get all images for this album
$images_query = $conn->prepare("SELECT * FROM album_images WHERE album_id = ? ORDER BY uploaded_at DESC");
$images_query->bind_param("i", $album_id);
$images_query->execute();
$images = $images_query->get_result();
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
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
        }
        
        .album-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .album-stats i {
            color: #667eea;
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
        
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
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
        
        .image-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
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
        <div class="album-header">
            <div>
                <h1><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($album['album_name']); ?></h1>
                <div class="album-stats">
                    <span><i class="fas fa-camera"></i> <?php echo htmlspecialchars($album['studio_name']); ?></span>
                    <span><i class="fas fa-images"></i> <?php echo mysqli_num_rows($images); ?> photos</span>
                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($album['created_at'])); ?></span>
                </div>
            </div>
            <div class="action-buttons">
                <button onclick="downloadAll()" class="btn btn-success">
                    <i class="fas fa-download"></i> Download All
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Albums
                </a>
            </div>
        </div>
        
        <div class="images-section">
            <div class="section-header">
                <h2><i class="fas fa-images"></i> Album Photos (<?php echo mysqli_num_rows($images); ?>)</h2>
            </div>
            
            <?php if (mysqli_num_rows($images) > 0): ?>
                <div class="images-grid">
                    <?php $index = 0; while($image = mysqli_fetch_assoc($images)): ?>
                        <div class="image-card" onclick="openLightbox(<?php echo $index; ?>)">
                            <img src="../uploads/albums/<?php echo $album_id . '/' . htmlspecialchars($image['image_path']); ?>" 
                                 alt="Album Image"
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
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-image"></i>
                    <h3>No Photos Yet</h3>
                    <p>This album is empty.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <span class="lightbox-nav lightbox-prev" onclick="changeImage(-1)">&#10094;</span>
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
                delay += 300; // 300ms delay between downloads
            });
        }
    </script>
</body>
</html>
