<?php
session_start();
require_once '../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    $password = trim($_POST['password']);
    
    // Get studio details - simple query without extra tables
    $stmt = $conn->prepare("SELECT studio_id, studio_name, password FROM studios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $studio = $result->fetch_assoc();
        
        // Check if password matches the hashed password
        if (password_verify($password, $studio['password'])) {
            // Login successful
            $_SESSION['studio_id'] = $studio['studio_id'];
            $_SESSION['studio_name'] = $studio['studio_name'];
            $_SESSION['user_type'] = 'studio';
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "Invalid email or password!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 90%;
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .login-header { text-align: center; margin-bottom: 40px; }
        .login-header i { font-size: 60px; color: #667eea; margin-bottom: 20px; }
        .login-header h1 { color: #333; font-size: 28px; margin-bottom: 10px; }
        .login-header p { color: #666; font-size: 14px; }
        .error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            animation: shake 0.5s;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }
        label i { color: #667eea; margin-right: 5px; }
        input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        .back-link a:hover { color: #764ba2; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-camera"></i>
            <h1>Studio Login</h1>
            <p>Access your studio dashboard</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" name="email" required autofocus>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            
            <div class="back-link">
                <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </form>
    </div>
</body>
</html>
