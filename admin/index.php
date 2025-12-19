<?php
require_once '../config.php';
require_once '../functions.php';
requireAdminLogin();

// Get statistics
$total_studios = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM studios"))['count'];
$active_studios = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM studios WHERE status='active'"))['count'];
$total_albums = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM albums"))['count'];
$total_images = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM album_images"))['count'];

// Get recent studios
$recent_studios = mysqli_query($conn, "SELECT * FROM studios ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            transition: 0.3s;
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
        
        .stat-icon.purple {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }
        
        table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-btns {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .btn-info {
            background: #3498db;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            .stats-grid {
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
            <a href="email_settings.php"><i class="fas fa-envelope-open-text"></i> Email Settings</a>

            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_studios; ?></h3>
                    <p>Total Studios</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $active_studios; ?></h3>
                    <p>Active Studios</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_albums; ?></h3>
                    <p>Total Albums</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_images; ?></h3>
                    <p>Total Images</p>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> Recent Studios</h2>
                <a href="manage_studios.php" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View All
                </a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Studio Name</th>
                        <th>Owner Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($studio = mysqli_fetch_assoc($recent_studios)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($studio['studio_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($studio['owner_name']); ?></td>
                            <td><?php echo htmlspecialchars($studio['email']); ?></td>
                            <td><?php echo htmlspecialchars($studio['contact_no']); ?></td>
                            <td>
                                <span class="badge <?php echo $studio['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($studio['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($studio['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
