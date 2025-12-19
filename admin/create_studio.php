<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../email_config.php'; // ✅ Email config
requireAdminLogin();

// ✅ Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Composer autoload

$success = '';
$error = '';

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
    
    // Generate 6-digit password
    $plain_password = generatePassword(6);
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
    
    // Check if email already exists
    $check_email = $conn->prepare("SELECT studio_id FROM studios WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    
    if ($check_email->get_result()->num_rows > 0) {
        $error = "Email already exists!";
    } else {
        // Insert studio
        $stmt = $conn->prepare("INSERT INTO studios (owner_name, studio_name, email, password, address_at, address_po, district, state, pin_code, whatsapp_no, contact_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssss", $owner_name, $studio_name, $email, $hashed_password, $address_at, $address_po, $district, $state, $pin_code, $whatsapp_no, $contact_no);
        
        if ($stmt->execute()) {
            // ✅ Send email with PHPMailer
            $email_sent = sendCredentialsEmailPHPMailer($email, $owner_name, $studio_name, $plain_password);
            
            if ($email_sent) {
                $success = "Studio created successfully!<br>
                           <strong>✅ Credentials sent to: $email</strong><br><br>
                           <div style='background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; margin-top: 10px;'>
                           <strong>Login Details:</strong><br>
                           Email: <strong>$email</strong><br>
                           Password: <strong>$plain_password</strong><br>
                           <small style='color:#fff3cd;'>(⚠️ Save this password - it won't be shown again!)</small>
                           </div>";
            } else {
                $success = "Studio created successfully!<br>
                           <strong>⚠️ Email sending failed - Please share credentials manually:</strong><br><br>
                           <div style='background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; margin-top: 10px;'>
                           Email: <strong>$email</strong><br>
                           Password: <strong>$plain_password</strong><br>
                           <small style='color:#fff3cd;'>(⚠️ Save this password - it won't be shown again!)</small>
                           </div>";
            }
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $check_email->close();
}

// ✅ PHPMailer Function
function sendCredentialsEmailPHPMailer($to_email, $owner_name, $studio_name, $password) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS encryption
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $owner_name);
        $mail->addReplyTo(SMTP_FROM_EMAIL, 'Support Team');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Studio Account Credentials - ' . $studio_name;
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f5f6fa; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 30px auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { padding: 40px 30px; }
                .credentials-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 25px 0; border-radius: 8px; }
                .credentials-box h3 { color: #333; margin-top: 0; }
                .credential-item { margin: 15px 0; }
                .credential-label { color: #666; font-size: 14px; margin-bottom: 5px; }
                .credential-value { color: #333; font-size: 18px; font-weight: bold; background: white; padding: 10px 15px; border-radius: 5px; display: inline-block; }
                .warning-box { background: #fff3cd; border-left: 4px solid #f39c12; padding: 15px; margin: 20px 0; border-radius: 8px; color: #856404; }
                .btn { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; padding: 15px 30px; border-radius: 10px; margin: 20px 0; font-weight: bold; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Welcome to Photo Album System!</h1>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . htmlspecialchars($owner_name) . "</strong>,</p>
                    <p>Your studio account has been successfully created! You can now manage your photo albums and customers.</p>
                    
                    <div class='credentials-box'>
                        <h3>🔐 Your Login Credentials</h3>
                        <div class='credential-item'>
                            <div class='credential-label'>Studio Name:</div>
                            <div class='credential-value'>" . htmlspecialchars($studio_name) . "</div>
                        </div>
                        <div class='credential-item'>
                            <div class='credential-label'>Email (User ID):</div>
                            <div class='credential-value'>" . htmlspecialchars($to_email) . "</div>
                        </div>
                        <div class='credential-item'>
                            <div class='credential-label'>Password:</div>
                            <div class='credential-value'>" . htmlspecialchars($password) . "</div>
                        </div>
                    </div>
                    
                    <div class='warning-box'>
                        <strong>⚠️ Important Security Notice:</strong><br>
                        Please save these credentials securely. We recommend changing your password after your first login.
                    </div>
                    
                    <center>
                        <a href='" . BASE_URL . "studio/login.php' class='btn'>
                            🔓 Login to Your Dashboard
                        </a>
                    </center>
                    
                    <p style='margin-top: 30px;'>If you have any questions or need assistance, please contact our support team.</p>
                    
                    <p style='color: #666; font-size: 14px; margin-top: 20px;'>
                        Best regards,<br>
                        <strong>Photo Album System Team</strong>
                    </p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>&copy; " . date('Y') . " Photo Album System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Welcome to Photo Album System!\n\n"
                       . "Studio Name: $studio_name\n"
                       . "Email: $to_email\n"
                       . "Password: $password\n\n"
                       . "Login at: " . BASE_URL . "studio/login.php";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Studio</title>
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
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            animation: slideDown 0.5s;
            line-height: 1.8;
            font-size: 15px;
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
            animation: slideDown 0.5s;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
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
        
        .required {
            color: #e74c3c;
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
                <h1><i class="fas fa-store"></i> Create New Studio</h1>
                <p>Fill in the details below to create a new studio account</p>
            </div>
            
            <?php if ($success): ?>
                <div class="success">
                    <i class="fas fa-check-circle" style="font-size: 32px; display: block; margin-bottom: 15px;"></i>
                    <?php echo $success; ?>
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
                        <label><i class="fas fa-user"></i> Owner Name <span class="required">*</span></label>
                        <input type="text" name="owner_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-store"></i> Studio Name <span class="required">*</span></label>
                        <input type="text" name="studio_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email (User ID) <span class="required">*</span></label>
                    <input type="email" name="email" required placeholder="studio@example.com">
                </div>
                
                <div class="section-title">
                    <i class="fas fa-map-marker-alt"></i> Address Details
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-home"></i> Address (At) <span class="required">*</span></label>
                    <input type="text" name="address_at" placeholder="Village/Town" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-mail-bulk"></i> Post Office (PO)</label>
                        <input type="text" name="address_po">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-city"></i> District <span class="required">*</span></label>
                        <input type="text" name="district" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-map"></i> State <span class="required">*</span></label>
                        <input type="text" name="state" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> PIN Code <span class="required">*</span></label>
                        <input type="text" name="pin_code" pattern="[0-9]{6}" maxlength="6" required placeholder="000000">
                    </div>
                </div>
                
                <div class="section-title">
                    <i class="fas fa-phone"></i> Contact Details
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fab fa-whatsapp"></i> WhatsApp Number <span class="required">*</span></label>
                        <input type="tel" name="whatsapp_no" pattern="[0-9]{10}" maxlength="10" required placeholder="9876543210">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Contact Number <span class="required">*</span></label>
                        <input type="tel" name="contact_no" pattern="[0-9]{10}" maxlength="10" required placeholder="9876543210">
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Create Studio & Send Credentials via Email
                </button>
            </form>
        </div>
    </div>
</body>
</html>
