<?php
require_once '../config.php';
require_once '../functions.php';
requireCustomerLogin();

$customer_id = $_SESSION['customer_id'];

// Get customer details
$stmt = $conn->prepare("SELECT c.*, s.studio_name FROM customers c JOIN studios s ON c.studio_id = s.studio_id WHERE c.customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get all albums for this customer
$albums_query = $conn->prepare("SELECT a.*, COUNT(ai.image_id) as image_count FROM albums a LEFT JOIN album_images ai ON a.album_id = ai.album_id WHERE a.customer_id = ? GROUP BY a.album_id ORDER BY a.created_at DESC");
$albums_query->bind_param("i", $customer_id);
$albums_query->execute();
$albums = $albums_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Albums - Customer Dashboard</title>
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
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .welcome-section h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .welcome-section p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .studio-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 20px;
            margin-top: 15px;
            font-size: 14px;
            backdrop-filter: blur(10px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        
        .stat-icon.albums {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-icon.photos {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }
        
        .stat-icon.studio {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .stat-content h3 {
            color: #333;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-content p {
            color: #666;
            font-size: 14px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .section-header h2 {
            color: #333;
            font-size: 28px;
        }
        
        .albums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .album-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            animation: zoomIn 0.5s ease;
        }
        
        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .album-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }
        
        .album-cover {
            width: 100%;
            height: 250px;
            object-fit: cover;
            cursor: pointer;
        }
        
        .album-info {
            padding: 25px;
        }
        
        .album-name {
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 12px;
            color: #333;
        }
        
        .album-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .album-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .album-meta i {
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
            width: 100%;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .empty-state i {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
            color: #667eea;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #666;
        }
        
        .empty-state p {
            color: #999;
            font-size: 16px;
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
            .welcome-section h1 {
                font-size: 28px;
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
            <i class="fas fa-images"></i> My Photo Albums
        </div>
        <div class="navbar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> My Albums</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-section">
            <h1>👋 Welcome, <?php echo htmlspecialchars($customer['customer_name']); ?>!</h1>
            <p>Access and download all your photo albums</p>
            <span class="studio-badge">
                <i class="fas fa-camera"></i> Studio: <?php echo htmlspecialchars($customer['studio_name']); ?>
            </span>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon albums">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo mysqli_num_rows($albums); ?></h3>
                    <p>Total Albums</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon photos">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-content">
                    <h3>
                        <?php
                        $total_photos = 0;
                        $temp_albums = $albums_query->result ?? mysqli_query($conn, "SELECT COUNT(ai.image_id) as total FROM albums a LEFT JOIN album_images ai ON a.album_id = ai.album_id WHERE a.customer_id = $customer_id");
                        if ($temp_albums) {
                            $temp_row = mysqli_fetch_assoc($temp_albums);
                            echo $temp_row ? $temp_row['total'] : 0;
                        }
                        mysqli_data_seek($albums, 0); // Reset pointer
                        ?>
                    </h3>
                    <p>Total Photos</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon studio">
                    <i class="fas fa-camera"></i>
                </div>
                <div class="stat-content">
                    <h3><i class="fas fa-check-circle" style="color: #2ecc71; font-size: 28px;"></i></h3>
                    <p>Active Account</p>
                </div>
            </div>
        </div>
        
        <div class="section-header">
            <h2><i class="fas fa-folder-open"></i> My Albums</h2>
        </div>
        
        <?php if (mysqli_num_rows($albums) > 0): ?>
            <div class="albums-grid">
                <?php while($album = mysqli_fetch_assoc($albums)): ?>
                    <div class="album-card">
                        <img src="../uploads/covers/<?php echo htmlspecialchars($album['cover_image']); ?>" 
                             alt="Album Cover" 
                             class="album-cover"
                             onclick="location.href='view_album.php?album_id=<?php echo $album['album_id']; ?>'">
                        <div class="album-info">
                            <div class="album-name">
                                <?php echo htmlspecialchars($album['album_name']); ?>
                            </div>
                            <div class="album-meta">
                                <span><i class="fas fa-images"></i> <?php echo $album['image_count']; ?> photos</span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($album['created_at'])); ?></span>
                            </div>
                            <a href="view_album.php?album_id=<?php echo $album['album_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Album
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>No Albums Yet</h3>
                <p>Your studio hasn't created any albums for you yet.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
