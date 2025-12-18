<?php
require_once '../config.php';
require_once '../functions.php';
requireActiveStudio($conn); // ADD THIS LINE

// requireStudioLogin();

$studio_id = $_SESSION['studio_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = clean_input($_POST['customer_name']);
    $email = clean_input($_POST['email']);
    $address_at = clean_input($_POST['address_at']);
    $address_po = clean_input($_POST['address_po']);
    $district = clean_input($_POST['district']);
    $state = clean_input($_POST['state']);
    $pin_code = clean_input($_POST['pin_code']);
    $whatsapp_no = clean_input($_POST['whatsapp_no']);
    $contact_no = clean_input($_POST['contact_no']);
    
    // Check if email already exists
    $check = $conn->prepare("SELECT email FROM customers WHERE email = ? UNION SELECT email FROM studios WHERE email = ?");
    $check->bind_param("ss", $email, $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Email already exists!";
    } else {
        // Generate 6-digit password
        $auto_password = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $hashed_password = password_hash($auto_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO customers (studio_id, customer_name, email, password, address_at, address_po, district, state, pin_code, whatsapp_no, contact_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssss", $studio_id, $customer_name, $email, $hashed_password, $address_at, $address_po, $district, $state, $pin_code, $whatsapp_no, $contact_no);
        
        if ($stmt->execute()) {
            $_SESSION['customer_created'] = [
                'name' => $customer_name,
                'email' => $email,
                'password' => $auto_password
            ];
            header("Location: manage_customers.php?created=success");
            exit();
        } else {
            $error = "Error creating customer: " . $stmt->error;
        }
        $stmt->close();
    }
    $check->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer</title>
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
            margin: 50px auto;
            padding: 0 20px;
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
        
        input {
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
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .info-box {
            background: #e8f0ff;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            color: #333;
        }
        
        .info-box i {
            color: #667eea;
            margin-right: 10px;
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
            <a href="manage_customers.php"><i class="fas fa-users"></i> Customers</a>
            <a href="select_album.php"><i class="fas fa-folder"></i> Albums</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>Add New Customer</h1>
                <p class="subtitle">Create customer account with auto-generated password</p>
            </div>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Note:</strong> A 6-digit password will be automatically generated for the customer. Share it securely after creation.
            </div>
            
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle" style="font-size: 20px;"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="section-title">
                    <i class="fas fa-user"></i> Basic Information
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Customer Name *</label>
                        <input type="text" name="customer_name" placeholder="Full Name" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email (Login ID) *</label>
                        <input type="email" name="email" placeholder="customer@example.com" required>
                    </div>
                </div>
                
                <div class="section-title">
                    <i class="fas fa-map-marker-alt"></i> Address
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-home"></i> Address (At) *</label>
                    <input type="text" name="address_at" placeholder="House No, Street" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-mail-bulk"></i> Post Office (PO)</label>
                        <input type="text" name="address_po" placeholder="Post Office">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-city"></i> District *</label>
                        <input type="text" name="district" placeholder="District" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-map"></i> State *</label>
                        <input type="text" name="state" placeholder="State" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> PIN Code *</label>
                        <input type="text" name="pin_code" placeholder="000000" pattern="[0-9]{6}" maxlength="6" required>
                    </div>
                </div>
                
                <div class="section-title">
                    <i class="fas fa-phone"></i> Contact Information
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fab fa-whatsapp"></i> WhatsApp Number *</label>
                        <input type="tel" name="whatsapp_no" placeholder="1234567890" pattern="[0-9]{10}" maxlength="10" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Contact Number *</label>
                        <input type="tel" name="contact_no" placeholder="1234567890" pattern="[0-9]{10}" maxlength="10" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-plus"></i> Create Customer Account
                </button>
            </form>
        </div>
    </div>
</body>
</html>
