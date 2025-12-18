<?php
require_once '../config.php';
require_once '../functions.php';
requireCustomerLogin();

$customer_id = $_SESSION['customer_id'];

// Get customer details with studio info
$stmt = $conn->prepare("SELECT c.*, s.studio_name, s.email as studio_email, s.contact_no as studio_contact FROM customers c JOIN studios s ON c.studio_id = s.studio_id WHERE c.customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

$success = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $customer['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE customers SET password=? WHERE customer_id=?");
                $stmt->bind_param("si", $hashed_password, $customer_id);
                
                if ($stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Error changing password.";
                }
                $stmt->close();
            } else {
                $error = "Password must be at least 6 characters.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .card-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-header h2 {
            color: #333;
            font-size: 24px;
        }
        
        .success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .profile-header {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            font-weight: 700;
            color: #667eea;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .profile-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .profile-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }
        
        .section-title {
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        label i {
            color: #667eea;
            margin-right: 5px;
        }
        
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-submit {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .studio-info-box {
            background: linear-gradient(135deg, #e8f0ff 0%, #f0f4ff 100%);
            padding: 25px;
            border-radius: 15px;
            border: 2px solid #667eea;
        }
        
        .studio-info-box h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
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
            .info-grid {
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
        
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($customer['customer_name'], 0, 1)); ?>
            </div>
            <h1><?php echo htmlspecialchars($customer['customer_name']); ?></h1>
            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?></p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user"></i> Personal Information</h2>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Email (Login ID)</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['email']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">WhatsApp Number</div>
                    <div class="info-value"><i class="fab fa-whatsapp" style="color: #25D366;"></i> <?php echo htmlspecialchars($customer['whatsapp_no']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Contact Number</div>
                    <div class="info-value"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['contact_no']); ?></div>
                </div>
            </div>
            
            <div class="section-title">
                <i class="fas fa-map-marker-alt"></i> Address
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['address_at']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Post Office</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['address_po']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">District</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['district']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">State</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['state']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">PIN Code</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['pin_code']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Member Since</div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-camera"></i> Studio Information</h2>
            </div>
            
            <div class="studio-info-box">
                <h3><?php echo htmlspecialchars($customer['studio_name']); ?></h3>
                <div class="info-grid" style="margin-top: 15px;">
                    <div style="color: #666;">
                        <i class="fas fa-envelope" style="color: #667eea;"></i> 
                        <strong>Email:</strong> <?php echo htmlspecialchars($customer['studio_email']); ?>
                    </div>
                    <div style="color: #666;">
                        <i class="fas fa-phone" style="color: #667eea;"></i> 
                        <strong>Contact:</strong> <?php echo htmlspecialchars($customer['studio_contact']); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-key"></i> Change Password</h2>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> New Password</label>
                    <input type="password" name="new_password" minlength="6" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm New Password</label>
                    <input type="password" name="confirm_password" minlength="6" required>
                </div>
                
                <button type="submit" name="change_password" class="btn-submit">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>
