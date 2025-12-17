<?php
require_once '../config.php';
require_once '../functions.php';
requireStudioLogin();

$studio_id = $_SESSION['studio_id'];
$album_id = isset($_GET['album_id']) ? intval($_GET['album_id']) : 0;

if ($album_id <= 0) {
    header("Location: select_album.php");
    exit();
}

// Verify album belongs to this studio
$stmt = $conn->prepare("SELECT * FROM albums WHERE album_id = ? AND studio_id = ?");
$stmt->bind_param("ii", $album_id, $studio_id);
$stmt->execute();
$album_result = $stmt->get_result();

if ($album_result->num_rows == 0) {
    header("Location: select_album.php");
    exit();
}

$album = $album_result->fetch_assoc();
$stmt->close();

// Check if QR code exists
if (empty($album['qr_code']) || empty($album['shareable_link'])) {
    header("Location: upload_images.php?album_id=" . $album_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo htmlspecialchars($album['album_name']); ?></title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .success-icon {
            font-size: 80px;
            color: #2ecc71;
            margin-bottom: 20px;
            animation: bounce 1s ease;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
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
        
        .album-name {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            margin: 20px 0;
        }
        
        .qr-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .qr-code-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            display: inline-block;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .qr-code-container img {
            max-width: 350px;
            width: 100%;
            display: block;
        }
        
        .qr-label {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .link-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .link-label {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .link-label i {
            color: #667eea;
        }
        
        .link-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            word-break: break-all;
            color: #667eea;
            font-size: 16px;
            border: 2px solid #667eea;
            margin-bottom: 15px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.6);
        }
        
        .btn-success.copied {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .instructions {
            background: #fff9e6;
            border-left: 4px solid #f39c12;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .instructions h3 {
            color: #e67e22;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .instructions ol {
            margin-left: 20px;
            color: #666;
            line-height: 1.8;
        }
        
        .instructions li {
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            font-size: 30px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        @media print {
            body {
                background: white;
            }
            .action-buttons, .instructions {
                display: none;
            }
            .container {
                box-shadow: none;
                padding: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            h1 {
                font-size: 24px;
            }
            .qr-code-container {
                padding: 20px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>QR Code Generated Successfully!</h1>
            <p class="subtitle">Share this QR code with your clients to let them find their photos</p>
            <div class="album-name">
                <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($album['album_name']); ?>
            </div>
        </div>
        
        <div class="instructions">
            <h3><i class="fas fa-lightbulb"></i> How to Use</h3>
            <ol>
                <li><strong>Print the QR Code:</strong> Download and print this QR code on banners, standees, or cards</li>
                <li><strong>Display at Event:</strong> Place the QR code prominently at your photo studio or event venue</li>
                <li><strong>Clients Scan:</strong> Guests scan the QR code with their phones to access the album</li>
                <li><strong>Upload Photo:</strong> They upload a selfie to find all photos with their face</li>
                <li><strong>Download Photos:</strong> Matched photos can be downloaded instantly!</li>
            </ol>
        </div>
        
        <div class="qr-section">
            <div class="qr-label">
                <i class="fas fa-qrcode"></i> Scan to Find Your Photos
            </div>
            <div class="qr-code-container">
                <img src="../<?php echo htmlspecialchars($album['qr_code']); ?>" alt="QR Code">
            </div>
        </div>
        
        <div class="link-section">
            <div class="link-label">
                <i class="fas fa-link"></i> Shareable Link
            </div>
            <div class="link-box" id="shareLink">
                <?php echo htmlspecialchars($album['shareable_link']); ?>
            </div>
            <button onclick="copyLink()" class="btn btn-success" id="copyBtn">
                <i class="fas fa-copy"></i> Copy Link
            </button>
        </div>
        
        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print QR Code
            </button>
            <a href="../<?php echo htmlspecialchars($album['qr_code']); ?>" download="qr_code_<?php echo $album['album_id']; ?>.png" class="btn btn-primary">
                <i class="fas fa-download"></i> Download QR Code
            </a>
            <a href="upload_images.php?album_id=<?php echo $album_id; ?>" class="btn btn-primary">
                <i class="fas fa-images"></i> Add More Photos
            </a>
            <a href="select_album.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Albums
            </a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-number">
                    <?php
                    $count_query = $conn->prepare("SELECT COUNT(*) as count FROM album_images WHERE album_id = ?");
                    $count_query->bind_param("i", $album_id);
                    $count_query->execute();
                    echo $count_query->get_result()->fetch_assoc()['count'];
                    ?>
                </div>
                <div class="stat-label">Total Photos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="stat-number">
                    <?php
                    $search_query = $conn->prepare("SELECT COUNT(*) as count FROM search_logs WHERE album_id = ?");
                    $search_query->bind_param("i", $album_id);
                    $search_query->execute();
                    echo $search_query->get_result()->fetch_assoc()['count'];
                    ?>
                </div>
                <div class="stat-label">Total Searches</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-number">
                    <?php echo date('M d', strtotime($album['created_at'])); ?>
                </div>
                <div class="stat-label">Created Date</div>
            </div>
        </div>
    </div>
    
    <script>
        function copyLink() {
            const linkText = document.getElementById('shareLink').textContent.trim();
            const copyBtn = document.getElementById('copyBtn');
            
            navigator.clipboard.writeText(linkText).then(() => {
                const originalHTML = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check"></i> Link Copied!';
                copyBtn.classList.add('copied');
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalHTML;
                    copyBtn.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = linkText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                const originalHTML = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check"></i> Link Copied!';
                copyBtn.classList.add('copied');
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalHTML;
                    copyBtn.classList.remove('copied');
                }, 2000);
            });
        }
        
        // Auto-focus on link for easy manual copy
        document.getElementById('shareLink').addEventListener('click', function() {
            const range = document.createRange();
            range.selectNodeContents(this);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        });
    </script>
</body>
</html>
