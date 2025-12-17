<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Recognition Album System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            width: 100%;
            text-align: center;
        }
        
        .logo {
            font-size: 80px;
            margin-bottom: 30px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        h1 {
            color: white;
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 20px;
            margin-bottom: 50px;
        }
        
        .login-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }
        
        .login-card {
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .login-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        
        .card-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: #667eea;
        }
        
        .card-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .card-description {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .features {
            margin-top: 60px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .feature {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            color: white;
        }
        
        .feature-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .feature h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .feature p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 32px;
            }
            .login-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📸</div>
        <h1>Face Recognition Album System</h1>
        <p class="subtitle">Find your photos instantly with AI-powered face recognition</p>
        
        <div class="login-grid">
            <div class="login-card">
                <div class="card-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h2 class="card-title">Admin</h2>
                <p class="card-description">Manage studios, view statistics, and oversee the entire system</p>
                <a href="admin/login.php" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Admin Login
                </a>
            </div>
            
            <div class="login-card">
                <div class="card-icon">
                    <i class="fas fa-camera"></i>
                </div>
                <h2 class="card-title">Studio</h2>
                <p class="card-description">Create albums, upload photos, and share QR codes with your clients</p>
                <a href="studio/login.php" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Studio Login
                </a>
            </div>
        </div>
        
        <div class="features">
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-brain"></i>
                </div>
                <h3>AI Face Recognition</h3>
                <p>Advanced facial recognition technology to find your photos instantly</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h3>QR Code Sharing</h3>
                <p>Easy sharing with automatically generated QR codes for each album</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-download"></i>
                </div>
                <h3>Easy Download</h3>
                <p>Download your photos individually or all at once with one click</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure & Private</h3>
                <p>Your photos are safe with us, protected by secure storage</p>
            </div>
        </div>
    </div>
</body>
</html>
