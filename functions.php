<?php
// Generate random 6-digit password
function generatePassword($length = 6) {
    return str_pad(rand(0, pow(10, $length)-1), $length, '0', STR_PAD_LEFT);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Check if studio is logged in
function isStudioLoggedIn() {
    return isset($_SESSION['studio_id']);
}

// Redirect if not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: " . BASE_URL . "admin/login.php");
        exit();
    }
}

function requireStudioLogin() {
    if (!isStudioLoggedIn()) {
        header("Location: " . BASE_URL . "studio/login.php");
        exit();
    }
}

// Sanitize input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Send email (configure with your SMTP)
function sendPasswordEmail($email, $studio_name, $password) {
    $subject = "Your Studio Login Credentials";
    $message = "
    <html>
    <body>
        <h2>Welcome to Face Recognition System</h2>
        <p><strong>Studio Name:</strong> $studio_name</p>
        <p><strong>Email/User ID:</strong> $email</p>
        <p><strong>Password:</strong> $password</p>
        <p>Please login at: " . BASE_URL . "studio/login.php</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: admin@facerecoapp.com" . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}
?>
