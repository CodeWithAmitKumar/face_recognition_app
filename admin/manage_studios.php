<?php
require_once '../config.php';
require_once '../functions.php';
requireAdminLogin();

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $studio_id = intval($_GET['toggle_status']);
    $stmt = $conn->prepare("UPDATE studios SET status = IF(status='active', 'inactive', 'active') WHERE studio_id = ?");
    $stmt->bind_param("i", $studio_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_studios.php");
    exit();
}

// Get all studios
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT s.*, COUNT(a.album_id) as album_count 
        FROM studios s 
        LEFT JOIN albums a ON s.studio_id = a.studio_id 
        WHERE 1=1";

if ($search) {
    $sql .= " AND (s.studio_name LIKE '%$search%' OR s.owner_name LIKE '%$search%' OR s.email LIKE '%$search%')";
}

if ($status_filter != 'all') {
    $sql .= " AND s.status = '$status_filter'";
}

$sql .= " GROUP BY s.studio_id ORDER BY s.created_at DESC";
$studios = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Studios</title>
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
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .card-header h1 {
            color: #333;
            font-size: 28px;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-box button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .filter-select {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            cursor: pointer;
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
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-info {
            background: #3498db;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-small:hover {
            transform: scale(1.05);
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
            .card {
                padding: 20px;
                overflow-x: auto;
            }
            table {
                font-size: 14px;
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
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-store"></i> Manage Studios</h1>
                <a href="create_studio.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Studio
                </a>
            </div>
            
            <form method="GET" class="filters">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search by studio name, owner, or email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </form>
            
            <?php if (mysqli_num_rows($studios) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Studio Name</th>
                            <th>Owner</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th>Albums</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($studio = mysqli_fetch_assoc($studios)): ?>
                            <tr>
                                <td><strong>#<?php echo $studio['studio_id']; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($studio['studio_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($studio['owner_name']); ?></td>
                                <td><?php echo htmlspecialchars($studio['email']); ?></td>
                                <td><?php echo htmlspecialchars($studio['contact_no']); ?></td>
                                <td><?php echo htmlspecialchars($studio['district'] . ', ' . $studio['state']); ?></td>
                                <td><span class="badge badge-success"><?php echo $studio['album_count']; ?> albums</span></td>
                                <td>
                                    <span class="badge <?php echo $studio['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($studio['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="view_studio_albums.php?id=<?php echo $studio['studio_id']; ?>" class="btn-small btn-info" title="View Albums">
                                            <i class="fas fa-images"></i>
                                        </a>
                                        <a href="edit_studio.php?id=<?php echo $studio['studio_id']; ?>" class="btn-small btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?toggle_status=<?php echo $studio['studio_id']; ?>" class="btn-small <?php echo $studio['status'] == 'active' ? 'btn-danger' : 'btn-success'; ?>" title="Toggle Status">
                                            <i class="fas fa-power-off"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-store-slash"></i>
                    <h3>No Studios Found</h3>
                    <p>Create your first studio to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
