<?php
require_once '../config.php';
require_once '../functions.php';
requireCustomerLogin();

$customer_id = $_SESSION['customer_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password from database
    $stmt = $conn->prepare("SELECT password FROM customers WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();
    
    // Verify current password
    if (!password_verify($current_password, $customer['password'])) {
        $message = '<div class="error"><i class="fas fa-exclamation-circle"></i> Current password is incorrect!</div>';
    } elseif ($new_password !== $confirm_password) {
        $message = '<div class="error"><i class="fas fa-exclamation-circle"></i> New passwords do not match!</div>';
    } elseif (strlen($new_password) < 6) {
        $message = '<div class="error"><i class="fas fa-exclamation-circle"></i> Password must be at least 6 characters long!</div>';
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update hashed password in customers table
        $update = $conn->prepare("UPDATE customers SET password = ? WHERE customer_id = ?");
        $update->bind_param("si", $hashed_password, $customer_id);
        $update->execute();
        $update->close();
        
        // Update plain password in password_storage table
        $store = $conn->prepare("REPLACE INTO password_storage (user_type, user_id, plain_password) VALUES ('customer', ?, ?)");
        $store->bind_param("is", $customer_id, $new_password);
        $store->execute();
        $store->close();
        
        $message = '<div class="success"><i class="fas fa-check-circle"></i> Password changed successfully!<br>Your new password is: <strong style="font-size:20px;">' . htmlspecialchars($new_password) . '</strong></div>';
        
        // Reload page after 2 seconds to show new password
        header("Refresh:2");
    }
}

// Get current password from password_storage table
$pass_query = $conn->prepare("SELECT plain_password FROM password_storage WHERE user_type = 'customer' AND user_id = ?");
$pass_query->bind_param("i", $customer_id);
$pass_query->execute();
$pass_result = $pass_query->get_result();
$pass_data = $pass_result->fetch_assoc();
$pass_query->close();

$current_display_pass = $pass_data['plain_password'] ?? 'Not Set';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
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
            max-width: 600px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .password-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .password-display h3 {
            font-size: 16px;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .password-display .pass-value {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 3px;
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
        }
        
        .card {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .card-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .card-icon {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 16px;
        }
        
        .success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            animation: slideDown 0.5s ease;
            text-align: center;
            line-height: 1.8;
        }
        
        .error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }
        
        label i {
            color: #667eea;
            margin-right: 8px;
        }
        
        .password-input-wrapper {
            position: relative;
        }
        
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 15px;
            padding-right: 50px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: 0.3s;
        }
        
        .toggle-password:hover {
            color: #667eea;
        }
        
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        
        .btn-back {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        
        .btn-back:hover {
            color: #764ba2;
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .password-requirements ul {
            margin: 10px 0 0 20px;
        }
        
        .password-requirements li {
            margin: 5px 0;
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
            .card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['customer_name']); ?>
        </div>
        <div class="navbar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="my_albums.php"><i class="fas fa-folder"></i> My Albums</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="password-display">
            <h3><i class="fas fa-key"></i> Your Current Password</h3>
            <div class="pass-value"><?php echo htmlspecialchars($current_display_pass); ?></div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h1>Change Password</h1>
                <p class="subtitle">Update your account password</p>
            </div>
            
            <?php echo $message; ?>
            
            <form method="POST" id="changePasswordForm">
                <div class="form-group">
                    <label><i class="fas fa-key"></i> Current Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="current_password" id="currentPassword" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('currentPassword', this)"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="new_password" id="newPassword" minlength="6" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('newPassword', this)"></i>
                    </div>
                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>Minimum 6 characters long</li>
                            <li>Use a strong password for better security</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i> Confirm New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="confirm_password" id="confirmPassword" minlength="6" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirmPassword', this)"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Change Password
                </button>
                
                <center>
                    <a href="profile.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                </center>
            </form>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password match validation
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
    </script>
</body>
</html>
