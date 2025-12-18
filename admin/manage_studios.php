<?php
require_once '../config.php';
require_once '../functions.php';
requireAdminLogin();

// Handle status toggle via AJAX
if (isset($_GET['toggle_status']) && isset($_GET['studio_id'])) {
    $studio_id = intval($_GET['studio_id']);
    $stmt = $conn->prepare("SELECT status FROM studios WHERE studio_id = ?");
    $stmt->bind_param("i", $studio_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $new_status = ($result['status'] == 'active') ? 'inactive' : 'active';
    
    $update = $conn->prepare("UPDATE studios SET status = ? WHERE studio_id = ?");
    $update->bind_param("si", $new_status, $studio_id);
    $update->execute();
    
    echo json_encode(['success' => true, 'status' => $new_status]);
    exit();
}

// Get all studios
$sql = "SELECT s.*, COUNT(a.album_id) as album_count 
        FROM studios s 
        LEFT JOIN albums a ON s.studio_id = a.studio_id 
        GROUP BY s.studio_id 
        ORDER BY s.created_at DESC";
$result = mysqli_query($conn, $sql);

$delete_success = isset($_GET['deleted']) && $_GET['deleted'] == 'success';
$delete_error = isset($_GET['error']) && $_GET['error'] == 'delete_failed';
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
        
        .header {
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
        
        .header h1 {
            color: #333;
            font-size: 28px;
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
        
        .success-message {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease;
        }
        
        .error-message {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .studios-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th {
            padding: 20px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        td {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .studio-name {
            font-weight: 600;
            color: #333;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-albums {
            background: #e8f0ff;
            color: #667eea;
        }
        
        /* Toggle Switch Styles */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ff6b6b;
            transition: .4s;
            border-radius: 30px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #2ecc71;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }
        
        .status-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 5px;
            text-align: center;
        }
        
        .status-active {
            color: #2ecc71;
        }
        
        .status-inactive {
            color: #ff6b6b;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 8px 15px;
            font-size: 13px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .btn-info:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }
        
        .btn-edit:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }
        
        .btn-delete:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
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
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
            margin: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: zoomIn 0.3s ease;
            position: relative;
        }
        
        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .modal-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ff6b6b;
        }
        
        .modal-content h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .modal-content p {
            color: #666;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f0f0f0;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .close-btn:hover {
            background: #e0e0e0;
            transform: rotate(90deg);
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn-cancel {
            background: #e0e0e0;
            color: #666;
        }
        
        .btn-cancel:hover {
            background: #d0d0d0;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }
        
        .btn-confirm:hover {
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }
        
        .warning-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            color: #856404;
            font-size: 14px;
            text-align: left;
        }
        
        .warning-box ul {
            margin: 10px 0 10px 30px;
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
            .studios-table {
                overflow-x: auto;
            }
            table {
                min-width: 900px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-shield-alt"></i> Admin Panel
        </div>
        <div class="navbar-menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="create_studio.php"><i class="fas fa-plus"></i> Create Studio</a>
            <a href="manage_studios.php"><i class="fas fa-building"></i> Manage Studios</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if ($delete_success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle" style="font-size: 24px;"></i>
                <span>Studio deleted successfully!</span>
            </div>
        <?php endif; ?>
        
        <?php if ($delete_error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i>
                <span>Error deleting studio. Please try again.</span>
            </div>
        <?php endif; ?>
        
        <div class="header">
            <h1><i class="fas fa-building"></i> Manage Studios (<?php echo mysqli_num_rows($result); ?>)</h1>
            <a href="create_studio.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Studio
            </a>
        </div>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="studios-table">
                <table>
                    <thead>
                        <tr>
                            <th>Studio Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Albums</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($studio = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="studio-name"><?php echo htmlspecialchars($studio['studio_name']); ?></td>
                                <td>
                                    <i class="fas fa-envelope" style="color: #667eea; margin-right: 5px;"></i>
                                    <?php echo htmlspecialchars($studio['email']); ?>
                                </td>
                                <td>
                                    <i class="fas fa-phone" style="color: #667eea; margin-right: 5px;"></i>
                                    <?php echo htmlspecialchars($studio['contact_no']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-albums">
                                        <i class="fas fa-folder"></i> <?php echo $studio['album_count']; ?> Albums
                                    </span>
                                </td>
                                <td>
                                    <label class="toggle-switch">
                                        <input type="checkbox" 
                                               class="status-toggle" 
                                               data-studio-id="<?php echo $studio['studio_id']; ?>"
                                               <?php echo ($studio['status'] == 'active') ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <div class="status-label status-<?php echo $studio['status']; ?>" id="status-label-<?php echo $studio['studio_id']; ?>">
                                        <?php echo $studio['status']; ?>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($studio['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_studio_albums.php?studio_id=<?php echo $studio['studio_id']; ?>" class="btn-small btn-info" title="View Albums">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_studio.php?id=<?php echo $studio['studio_id']; ?>" class="btn-small btn-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $studio['studio_id']; ?>, '<?php echo htmlspecialchars(addslashes($studio['studio_name'])); ?>', <?php echo $studio['album_count']; ?>)" class="btn-small btn-delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-building"></i>
                <h3>No Studios Yet</h3>
                <p>Create your first studio to get started!</p>
                <a href="create_studio.php" class="btn btn-primary" style="margin-top: 30px;">
                    <i class="fas fa-plus"></i> Create First Studio
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeDeleteModal()">×</button>
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Delete Studio?</h3>
            <p>Are you sure you want to delete "<strong id="studioNameText"></strong>"?</p>
            <div class="warning-box">
                <i class="fas fa-exclamation-circle"></i> <strong>Warning:</strong> This will permanently delete:
                <ul>
                    <li>All customers of this studio</li>
                    <li><span id="albumCount">0</span> albums</li>
                    <li>All images in these albums</li>
                    <li>All search logs</li>
                </ul>
                <strong>This action cannot be undone!</strong>
            </div>
            <div class="modal-actions">
                <button onclick="closeDeleteModal()" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-confirm">
                    <i class="fas fa-trash"></i> Delete Permanently
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Handle status toggle
        document.querySelectorAll('.status-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const studioId = this.getAttribute('data-studio-id');
                const isChecked = this.checked;
                const statusLabel = document.getElementById('status-label-' + studioId);
                
                fetch('manage_studios.php?toggle_status=1&studio_id=' + studioId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            statusLabel.textContent = data.status;
                            statusLabel.className = 'status-label status-' + data.status;
                            
                            // Show notification
                            showNotification('Studio status updated to ' + data.status);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Revert toggle on error
                        this.checked = !isChecked;
                    });
            });
        });
        
        function confirmDelete(studioId, studioName, albumCount) {
            const modal = document.getElementById('deleteModal');
            const studioNameText = document.getElementById('studioNameText');
            const albumCountText = document.getElementById('albumCount');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            studioNameText.textContent = studioName;
            albumCountText.textContent = albumCount;
            confirmBtn.href = 'delete_studio.php?id=' + studioId;
            
            modal.classList.add('active');
        }
        
        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('active');
        }
        
        window.onclick = function(event) {
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeDeleteModal();
            }
        });
        
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); color: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); z-index: 9999; animation: slideIn 0.5s ease;';
            notification.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.5s ease';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }
    </script>
    
    <style>
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
    </style>
</body>
</html>
