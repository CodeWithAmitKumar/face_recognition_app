<?php
require_once '../config.php';
require_once '../functions.php';
requireStudioLogin();

$studio_id = $_SESSION['studio_id'];

// Get studio details
$stmt = $conn->prepare("SELECT * FROM studios WHERE studio_id = ?");
$stmt->bind_param("i", $studio_id);
$stmt->execute();
$studio = $stmt->get_result()->fetch_assoc();
$stmt->close();

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $owner_name = clean_input($_POST['owner_name']);
    $studio_name = clean_input($_POST['studio_name']);
    $address_at = clean_input($_POST['address_at']);
    $address_po = clean_input($_POST['address_po']);
    $district = clean_input($_POST['district']);
    $state = clean_input($_POST['state']);
    $pin_code = clean_input($_POST['pin_code']);
    $whatsapp_no = clean_input($_POST['whatsapp_no']);
    $contact_no = clean_input($_POST['contact_no']);
    
    $stmt = $conn->prepare("UPDATE studios SET owner_name=?, studio_name=?, address_at=?, address_po=?, district=?, state=?, pin_code=?, whatsapp_no=?, contact_no=? WHERE studio_id=?");
    $stmt->bind_param("sssssssssi", $owner_name, $studio_name, $address_at, $address_po, $district, $state, $pin_code, $whatsapp_no, $contact_no, $studio_id);
    
    if ($stmt->execute()) {
        $_SESSION['studio_name'] = $studio_name;
        $success = "Profile updated successfully!";
        $studio['studio_name'] = $studio_name;
        $studio['owner_name'] = $owner_name;
    } else {
        $error = "Error updating profile.";
    }
    $stmt->close();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $studio['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE studios SET password=? WHERE studio_id=?");
                $stmt->bind_param("si", $hashed_password, $studio_id);
                
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
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
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
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
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
        
        .section-title {
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
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
            .form-row {
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
            <a href="create_album.php"><i class="fas fa-plus"></i> Create Album</a>
            <a href="select_album.php"><i class="fas fa-folder"></i> My Albums</a>
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
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-edit"></i> Update Profile</h2>
            </div>
            
            <form method="POST">
                <div class="section-title">
                    <i class="fas fa-user"></i> Basic Information
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Owner Name</label>
                        <input type="text" name="owner_name" value="<?php echo htmlspecialchars($studio['owner_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-store"></i> Studio Name</label>
                        <input type="text" name="studio_name" value="<?php echo htmlspecialchars($studio['studio_name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email (Login ID)</label>
                    <input type="email" value="<?php echo htmlspecialchars($studio['email']); ?>" disabled>
                    <small style="color: #999;">Email cannot be changed</small>
                </div>
                
                <div class="section-title">
                    <i class="fas fa-map-marker-alt"></i> Address
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-home"></i> Address (At)</label>
                    <input type="text" name="address_at" value="<?php echo htmlspecialchars($studio['address_at']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-mail-bulk"></i> Post Office (PO)</label>
                        <input type="text" name="address_po" value="<?php echo htmlspecialchars($studio['address_po']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-city"></i> District</label>
                        <input type="text" name="district" value="<?php echo htmlspecialchars($studio['district']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-map"></i> State</label>
                        <input type="text" name="state" value="<?php echo htmlspecialchars($studio['state']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> PIN Code</label>
                        <input type="text" name="pin_code" value="<?php echo htmlspecialchars($studio['pin_code']); ?>" pattern="[0-9]{6}" maxlength="6" required>
                    </div>
                </div>
                
                <div class="section-title">
                    <i class="fas fa-phone"></i> Contact
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fab fa-whatsapp"></i> WhatsApp Number</label>
                        <input type="tel" name="whatsapp_no" value="<?php echo htmlspecialchars($studio['whatsapp_no']); ?>" pattern="[0-9]{10}" maxlength="10" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Contact Number</label>
                        <input type="tel" name="contact_no" value="<?php echo htmlspecialchars($studio['contact_no']); ?>" pattern="[0-9]{10}" maxlength="10" required>
                    </div>
                </div>
                
                <button type="submit" name="update_profile" class="btn-submit">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
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
