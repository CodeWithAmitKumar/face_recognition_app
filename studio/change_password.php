<?php
session_start();

if (!isset($_SESSION['studio_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "face_recognition_app");
$studio_id = (int)$_SESSION['studio_id'];
$msg = '';

if (isset($_POST['change'])) {
    $new = $_POST['new_pass'];
    
    // Update password hash
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $conn->query("UPDATE studios SET password='".addslashes($hash)."' WHERE studio_id=$studio_id");
    
    // Update plain password
    $conn->query("REPLACE INTO password_storage (user_type, user_id, plain_password) VALUES ('studio', $studio_id, '".addslashes($new)."')");
    
    $msg = "✅ Password changed to: <strong>$new</strong>";
    echo '<script>setTimeout(function(){location.reload();}, 1000);</script>';
}

// Get current password
$result = $conn->query("SELECT plain_password FROM password_storage WHERE user_type='studio' AND user_id=$studio_id");
$row = $result->fetch_assoc();
$current = $row['plain_password'] ?? 'Not Set';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-width: 400px; width: 90%; }
        .current { background: #667eea; color: white; text-align: center; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .current h2 { margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; }
        .current .pass { font-size: 40px; font-weight: bold; letter-spacing: 3px; font-family: monospace; }
        h1 { text-align: center; color: #333; margin-bottom: 20px; font-size: 24px; }
        input { width: 100%; padding: 15px; margin: 10px 0; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box; }
        input:focus { outline: none; border-color: #667eea; }
        button { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        button:hover { opacity: 0.9; }
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 8px; text-align: center; background: #2ecc71; color: white; font-size: 16px; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .info { text-align: center; margin-top: 20px; font-size: 13px; color: #666; }
        a { color: #667eea; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <div class="current">
            <h2>🔑 CURRENT PASSWORD</h2>
            <div class="pass"><?php echo htmlspecialchars($current); ?></div>
        </div>
        
        <h1>🔐 Change Password</h1>
        
        <?php if($msg): ?>
        <div class="msg"><?php echo $msg; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="new_pass" placeholder="Enter New Password (min 6 chars)" minlength="6" required autofocus>
            <button type="submit" name="change">CHANGE PASSWORD</button>
        </form>
        
        <div class="info">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
