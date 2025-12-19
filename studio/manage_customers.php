<?php
require_once '../config.php';
require_once '../functions.php';
requireStudioLogin();


$studio_id = $_SESSION['studio_id'];


// Get all customers for this studio
$sql = "SELECT c.*, COUNT(a.album_id) as album_count 
        FROM customers c 
        LEFT JOIN albums a ON c.customer_id = a.customer_id 
        WHERE c.studio_id = $studio_id
        GROUP BY c.customer_id 
        ORDER BY c.created_at DESC";
$result = mysqli_query($conn, $sql);


$delete_success = isset($_GET['deleted']) && $_GET['deleted'] == 'success';
$created_success = isset($_GET['created']) && $_GET['created'] == 'success';
$customer_data = isset($_SESSION['customer_created']) ? $_SESSION['customer_created'] : null;
if ($created_success && $customer_data) {
    // Don't unset yet - will unset after displaying
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers</title>
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
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .email-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 15px;
        }
        
        .email-sent {
            background: rgba(255,255,255,0.25);
            border: 2px solid rgba(255,255,255,0.5);
        }
        
        .email-failed {
            background: rgba(255,152,0,0.3);
            border: 2px solid rgba(255,152,0,0.6);
        }
        
        .credentials-box {
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .credentials-box h3 {
            margin-bottom: 15px;
            color: white;
            font-size: 18px;
        }
        
        .credential-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: rgba(255,255,255,0.95);
            margin-bottom: 10px;
            border-radius: 8px;
        }
        
        .credential-label {
            font-weight: 600;
            color: #666;
        }
        
        .credential-value {
            font-family: monospace;
            font-size: 16px;
            color: #333;
            background: white;
            padding: 6px 15px;
            border-radius: 5px;
            border: 2px solid #e0e0e0;
            font-weight: 600;
        }
        
        .credential-value.password {
            background: #fff3cd;
            border-color: #f39c12;
            color: #856404;
        }
        
        .copy-btn {
            padding: 6px 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .copy-btn:hover {
            background: #5568d3;
            transform: scale(1.05);
        }
        
        .warning-note {
            margin-top: 15px;
            padding: 12px;
            background: rgba(255,243,205,0.3);
            border-radius: 8px;
            font-size: 13px;
            color: #fff;
            border: 2px solid rgba(255,193,7,0.5);
        }
        
        .customers-table {
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
        
        .customer-name {
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
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
            margin-bottom: 30px;
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
            .customers-table {
                overflow-x: auto;
            }
            table {
                min-width: 800px;
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
            <a href="select_album.php"><i class="fas fa-folder"></i> Albums</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if ($delete_success): ?>
            <div class="success-message">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle" style="font-size: 24px;"></i>
                    <span style="font-size: 18px; font-weight: 600;">Customer deleted successfully!</span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($created_success && $customer_data): ?>
            <div class="success-message">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <i class="fas fa-check-circle" style="font-size: 28px;"></i>
                    <span style="font-size: 20px; font-weight: 700;">Customer Account Created Successfully!</span>
                </div>
                
                <?php if (isset($customer_data['email_sent']) && $customer_data['email_sent']): ?>
                    <div class="email-status email-sent">
                        <i class="fas fa-envelope-circle-check" style="font-size: 20px;"></i>
                        <div>
                            <strong>✅ Credentials Email Sent Successfully!</strong><br>
                            <small>Login details have been sent to: <strong><?php echo htmlspecialchars($customer_data['email']); ?></strong></small>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="email-status email-failed">
                        <i class="fas fa-triangle-exclamation" style="font-size: 20px;"></i>
                        <div>
                            <strong>⚠️ Email Sending Failed</strong><br>
                            <small>Please share the credentials manually with the customer</small>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="credentials-box">
                    <h3><i class="fas fa-key"></i> Login Credentials</h3>
                    
                    <div class="credential-item">
                        <span class="credential-label">Customer Name:</span>
                        <span class="credential-value"><?php echo htmlspecialchars($customer_data['name']); ?></span>
                    </div>
                    
                    <div class="credential-item">
                        <span class="credential-label">Email (User ID):</span>
                        <span class="credential-value" id="email"><?php echo htmlspecialchars($customer_data['email']); ?></span>
                        <button class="copy-btn" onclick="copyText('email')"><i class="fas fa-copy"></i> Copy</button>
                    </div>
                    
                    <div class="credential-item">
                        <span class="credential-label">Password:</span>
                        <span class="credential-value password" id="password"><?php echo htmlspecialchars($customer_data['password']); ?></span>
                        <button class="copy-btn" onclick="copyText('password')"><i class="fas fa-copy"></i> Copy</button>
                    </div>
                    
                    <div class="warning-note">
                        <i class="fas fa-exclamation-circle"></i> <strong>Important:</strong> Save this password now! It won't be shown again. 
                        <?php if (!isset($customer_data['email_sent']) || !$customer_data['email_sent']): ?>
                            Make sure to share these credentials with the customer.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['customer_created']); ?>
        <?php endif; ?>
        
        <div class="header">
            <h1><i class="fas fa-users"></i> My Customers (<?php echo mysqli_num_rows($result); ?>)</h1>
            <a href="add_customer.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add New Customer
            </a>
        </div>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="customers-table">
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th>Albums</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($customer = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <div class="customer-name">
                                        <div class="customer-avatar">
                                            <?php echo strtoupper(substr($customer['customer_name'], 0, 1)); ?>
                                        </div>
                                        <span><?php echo htmlspecialchars($customer['customer_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <i class="fas fa-envelope" style="color: #667eea; margin-right: 5px;"></i>
                                    <?php echo htmlspecialchars($customer['email']); ?>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">
                                        <div><i class="fas fa-phone" style="color: #667eea; margin-right: 5px;"></i> <?php echo htmlspecialchars($customer['contact_no']); ?></div>
                                        <div style="margin-top: 5px;"><i class="fab fa-whatsapp" style="color: #25D366; margin-right: 5px;"></i> <?php echo htmlspecialchars($customer['whatsapp_no']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt" style="color: #667eea; margin-right: 5px;"></i>
                                    <?php echo htmlspecialchars($customer['district'] . ', ' . $customer['state']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-albums">
                                        <i class="fas fa-folder"></i> <?php echo $customer['album_count']; ?> Albums
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_customer.php?id=<?php echo $customer['customer_id']; ?>" class="btn-small btn-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $customer['customer_id']; ?>, '<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')" class="btn-small btn-delete" title="Delete">
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
                <i class="fas fa-users"></i>
                <h3>No Customers Yet</h3>
                <p>Add your first customer to get started!</p>
                <a href="add_customer.php" class="btn btn-primary" style="margin-top: 30px;">
                    <i class="fas fa-user-plus"></i> Add First Customer
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
            <h3>Delete Customer?</h3>
            <p>Are you sure you want to delete "<strong id="customerNameText"></strong>"?<br>
            This will also delete all albums and images associated with this customer.</p>
            <div class="modal-actions">
                <button onclick="closeDeleteModal()" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-confirm">
                    <i class="fas fa-trash"></i> Delete Customer
                </a>
            </div>
        </div>
    </div>
    
    <script>
        function confirmDelete(customerId, customerName) {
            const modal = document.getElementById('deleteModal');
            const customerNameText = document.getElementById('customerNameText');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            customerNameText.textContent = customerName;
            confirmBtn.href = 'delete_customer.php?id=' + customerId + '&confirm=yes';
            
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
        
        function copyText(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.target.closest('.copy-btn');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = '#2ecc71';
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '#667eea';
                }, 2000);
            });
        }
    </script>
</body>
</html>
