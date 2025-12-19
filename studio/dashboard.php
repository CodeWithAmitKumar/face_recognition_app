<?php
require_once '../config.php';
require_once '../functions.php';
requireStudioLogin();

$studio_id = $_SESSION['studio_id'];


// Check studio status
$stmt = $conn->prepare("SELECT status FROM studios WHERE studio_id = ?");
$stmt->bind_param("i", $studio_id);
$stmt->execute();
$studio_status = $stmt->get_result()->fetch_assoc()['status'];
$stmt->close();

$is_inactive = ($studio_status == 'inactive');

// Clear status error message
$status_error = isset($_SESSION['status_error']) ? $_SESSION['status_error'] : '';
unset($_SESSION['status_error']);

// Get studio statistics
$stats = getStudioStats($conn, $studio_id);

// Get recent customers
$recent_customers = $conn->query("SELECT customer_id, customer_name, email, created_at FROM customers WHERE studio_id = $studio_id ORDER BY created_at DESC LIMIT 5");

// Get recent albums
$recent_albums = $conn->query("SELECT a.album_id, a.album_name, a.created_at, c.customer_name, COUNT(ai.image_id) as image_count 
    FROM albums a 
    LEFT JOIN customers c ON a.customer_id = c.customer_id 
    LEFT JOIN album_images ai ON a.album_id = ai.album_id 
    WHERE a.studio_id = $studio_id 
    GROUP BY a.album_id 
    ORDER BY a.created_at DESC 
    LIMIT 5");


// Get statistics
$total_albums = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM albums WHERE studio_id = $studio_id"))['count'];
$total_images = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM album_images ai JOIN albums a ON ai.album_id = a.album_id WHERE a.studio_id = $studio_id"))['count'];
$total_searches = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM search_logs sl JOIN albums a ON sl.album_id = a.album_id WHERE a.studio_id = $studio_id"))['count'];

// Get recent albums
$recent_albums = mysqli_query($conn, "SELECT a.*, COUNT(ai.image_id) as image_count FROM albums a LEFT JOIN album_images ai ON a.album_id = ai.album_id WHERE a.studio_id = $studio_id GROUP BY a.album_id ORDER BY a.created_at DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Dashboard</title>
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

        .welcome-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .welcome-banner h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .welcome-banner p {
            font-size: 16px;
            opacity: 0.9;
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: white;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }

        .stat-info h3 {
            font-size: 32px;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #666;
            font-size: 14px;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
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

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
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
            cursor: pointer;
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
                flex-direction: column;
                gap: 15px;
            }

            .navbar-menu {
                flex-direction: column;
                gap: 10px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
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
            <a href="view_customer_selections.php">
               <i class="fas fa-heart"></i> Selections
               
           </a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="select_album.php"><i class="fas fa-folder"></i> My Albums</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <?php if ($is_inactive): ?>
    <div style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white; padding: 20px; text-align: center; margin-bottom: 30px; border-radius: 15px; animation: pulse 2s infinite;">
        <i class="fas fa-exclamation-triangle" style="font-size: 40px; margin-bottom: 10px;"></i>
        <h2 style="margin-bottom: 10px;">Account Deactivated</h2>
        <p style="font-size: 16px;">Your studio account has been temporarily deactivated by the administrator. You can view your data but cannot make any changes. Please contact the admin for assistance.</p>
    </div>
<?php endif; ?>

<?php if ($status_error): ?>
    <div style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 30px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i>
        <span><?php echo $status_error; ?></span>
    </div>
<?php endif; ?>


    <div class="container">
        <div class="welcome-banner">
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['studio_name']); ?>! 👋</h1>
            <p>Manage your photo albums and track user searches</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_albums; ?></h3>
                    <p>Total Albums</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon customer">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['customers']; ?></h3>
                    <p>Total Customers</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_images; ?></h3>
                    <p>Total Images</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-search"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_searches; ?></h3>
                    <p>Total Searches</p>
                </div>
            </div>
        </div>



        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-folder-open"></i> Recent Albums</h2>
                <a href="select_album.php" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View All
                </a>
            </div>

            <?php if (mysqli_num_rows($recent_albums) > 0): ?>
                <div class="albums-grid">
                    <?php while ($album = mysqli_fetch_assoc($recent_albums)): ?>
                        <div class="album-card">
                            <img src="../uploads/covers/<?php echo htmlspecialchars($album['cover_image']); ?>" alt="Album Cover"
                                class="album-cover"
                                onclick="location.href='upload_images.php?album_id=<?php echo $album['album_id']; ?>'">
                            <div class="album-info">
                                <div class="album-name">
                                    <?php echo htmlspecialchars($album['album_name']); ?>
                                </div>
                                <div class="album-meta">
                                    <span><i class="fas fa-images"></i> <?php echo $album['image_count']; ?> photos</span>
                                    <span><i class="fas fa-calendar"></i>
                                        <?php echo date('M d', strtotime($album['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-images"></i>
                    <h3>No Albums Yet</h3>
                    <p>Create your first album to get started!</p>
                    <a href="create_album.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i> Create Album
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>