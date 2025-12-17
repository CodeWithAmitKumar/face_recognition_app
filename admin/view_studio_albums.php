<?php
require_once '../config.php';
require_once '../functions.php';
requireAdminLogin();

$studio_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($studio_id <= 0) {
    header("Location: manage_studios.php");
    exit();
}

// Get studio details
$stmt = $conn->prepare("SELECT * FROM studios WHERE studio_id = ?");
$stmt->bind_param("i", $studio_id);
$stmt->execute();
$studio_result = $stmt->get_result();

if ($studio_result->num_rows == 0) {
    header("Location: manage_studios.php");
    exit();
}

$studio = $studio_result->fetch_assoc();
$stmt->close();

// Get albums
$albums_query = $conn->prepare("SELECT a.*, COUNT(ai.image_id) as image_count FROM albums a LEFT JOIN album_images ai ON a.album_id = ai.album_id WHERE a.studio_id = ? GROUP BY a.album_id ORDER BY a.created_at DESC");
$albums_query->bind_param("i", $studio_id);
$albums_query->execute();
$albums = $albums_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Albums - <?php echo htmlspecialchars($studio['studio_name']); ?></title>
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
        }
        
        .navbar-menu a:hover {
            opacity: 0.8;
        }
        
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .studio-info {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .studio-info h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
        }
        
        .info-item i {
            color: #667eea;
            width: 20px;
        }
        
        .albums-section {
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
        
        .section-header h3 {
            color: #333;
            font-size: 24px;
        }
        
        .btn-back {
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        
        .btn-back:hover {
            background: #7f8c8d;
        }
        
        .albums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .album-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .album-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }
        
        .album-cover {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .album-info {
            padding: 20px;
        }
        
        .album-name {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .album-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .album-meta i {
            color: #667eea;
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
        
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
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
            <i class="fas fa-user-shield"></i> Admin Panel
        </div>
        <div class="navbar-menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="create_studio.php"><i class="fas fa-plus"></i> Create Studio</a>
            <a href="manage_studios.php"><i class="fas fa-store"></i> Manage Studios</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="studio-info">
            <h2><i class="fas fa-store"></i> <?php echo htmlspecialchars($studio['studio_name']); ?></h2>
            <div class="info-grid">
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span><strong>Owner:</strong> <?php echo htmlspecialchars($studio['owner_name']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo htmlspecialchars($studio['email']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <span><?php echo htmlspecialchars($studio['contact_no']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($studio['district'] . ', ' . $studio['state']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="albums-section">
            <div class="section-header">
                <h3><i class="fas fa-folder"></i> Albums (<?php echo mysqli_num_rows($albums); ?>)</h3>
                <a href="manage_studios.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Studios
                </a>
            </div>
            
            <?php if (mysqli_num_rows($albums) > 0): ?>
                <div class="albums-grid">
                    <?php while($album = mysqli_fetch_assoc($albums)): ?>
                        <div class="album-card">
                            <img src="../<?php echo htmlspecialchars($album['cover_image']); ?>" alt="Album Cover" class="album-cover">
                            <div class="album-info">
                                <div class="album-name">
                                    <?php echo htmlspecialchars($album['album_name']); ?>
                                </div>
                                <div class="album-meta">
                                    <span><i class="fas fa-images"></i> <?php echo $album['image_count']; ?> photos</span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($album['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-images"></i>
                    <h3>No Albums Yet</h3>
                    <p>This studio hasn't created any albums.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
