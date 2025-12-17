<?php
require_once '../config.php';
require_once '../functions.php';
requireAdminLogin();

$studio_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = '';
$error = '';

if ($studio_id <= 0) {
    header("Location: manage_studios.php");
    exit();
}

// Get studio details
$stmt = $conn->prepare("SELECT * FROM studios WHERE studio_id = ?");
$stmt->bind_param("i", $studio_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage_studios.php");
    exit();
}

$studio = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $owner_name = clean_input($_POST['owner_name']);
    $studio_name = clean_input($_POST['studio_name']);
    $email = clean_input($_POST['email']);
    $address_at = clean_input($_POST['address_at']);
    $address_po = clean_input($_POST['address_po']);
    $district = clean_input($_POST['district']);
    $state = clean_input($_POST['state']);
    $pin_code = clean_input($_POST['pin_code']);
    $whatsapp_no = clean_input($_POST['whatsapp_no']);
    $contact_no = clean_input($_POST['contact_no']);
    
    // Update studio
    $stmt = $conn->prepare("UPDATE studios SET owner_name=?, studio_name=?, email=?, address_at=?, address_po=?, district=?, state=?, pin_code=?, whatsapp_no=?, contact_no=? WHERE studio_id=?");
    $stmt->bind_param("ssssssssssi", $owner_name, $studio_name, $email, $address_at, $address_po, $district, $state, $pin_code, $whatsapp_no, $contact_no, $studio_id);
    
    if ($stmt->execute()) {
        $success = "Studio updated successfully!";
        // Refresh data
        $studio['owner_name'] = $owner_name;
        $studio['studio_name'] = $studio_name;
        $studio['email'] = $email;
    } else {
        $error = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Studio</title>
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
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .card-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .card-header p {
            color: #666;
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
        input[type="tel"] {
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
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 15px;
            transition: 0.3s;
        }
        
        .btn-back:hover {
            background: #7f8c8d;
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
                <h1><i class="fas fa-edit"></i> Edit Studio</h1>
                <p>Update studio information</p>
            </div>
            
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
            
            <form method="POST">
                <div class="section-title">
                    <i class="fas fa-user"></i> Owner & Studio Details
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
                    <label><i class="fas fa-envelope"></i> Email (User ID)</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($studio['email']); ?>" required>
                </div>
                
                <div class="section-title">
                    <i class="fas fa-map-marker-alt"></i> Address Details
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
                    <i class="fas fa-phone"></i> Contact Details
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
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Update Studio
                </button>
            </form>
            
            <a href="manage_studios.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Studios
            </a>
        </div>
    </div>
</body>
</html>
