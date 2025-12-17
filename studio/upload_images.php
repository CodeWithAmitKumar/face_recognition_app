<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../phpqrcode/qrlib.php';
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

$error = '';

// Handle multiple image uploads
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['images'])) {
    $upload_count = 0;
    $error_count = 0;
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $upload_folder = "../uploads/albums/" . $album_id . "/";
    
    // Create folder if not exists
    if (!file_exists($upload_folder)) {
        mkdir($upload_folder, 0755, true);
    }
    
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] == 0) {
            $file_extension = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
            
            if (in_array($file_extension, $allowed_extensions)) {
                $imageinfo = getimagesize($tmp_name);
                
                if ($imageinfo !== false) {
                    $filename = "img_" . uniqid() . '.' . $file_extension;
                    $filepath = $upload_folder . $filename;
                    
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        // Insert into database
                        $relative_path = "uploads/albums/" . $album_id . "/" . $filename;
                        $stmt = $conn->prepare("INSERT INTO album_images (album_id, image_path) VALUES (?, ?)");
                        $stmt->bind_param("is", $album_id, $relative_path);
                        $stmt->execute();
                        $stmt->close();
                        
                        $upload_count++;
                    } else {
                        $error_count++;
                    }
                } else {
                    $error_count++;
                }
            } else {
                $error_count++;
            }
        }
    }
    
    if ($upload_count > 0) {
        // Generate QR code if not exists
        if (empty($album['qr_code'])) {
            $shareable_link = BASE_URL . "user/search.php?album=" . $album_id;
            $qr_filename = "../uploads/qrcodes/album_" . $album_id . ".png";
            
            if (!file_exists('../uploads/qrcodes')) {
                mkdir('../uploads/qrcodes', 0755, true);
            }
            
            QRcode::png($shareable_link, $qr_filename, QR_ECLEVEL_L, 10);
            
            // Update album with QR code and link
            $qr_relative_path = "uploads/qrcodes/album_" . $album_id . ".png";
            $update_stmt = $conn->prepare("UPDATE albums SET qr_code = ?, shareable_link = ? WHERE album_id = ?");
            $update_stmt->bind_param("ssi", $qr_relative_path, $shareable_link, $album_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $album['qr_code'] = $qr_relative_path;
            $album['shareable_link'] = $shareable_link;
        }
        
        // REDIRECT to prevent duplicate submission on refresh
        $_SESSION['upload_success'] = "$upload_count image(s) uploaded successfully!";
        if ($error_count > 0) {
            $_SESSION['upload_success'] .= " ($error_count failed)";
        }
        header("Location: upload_images.php?album_id=" . $album_id);
        exit();
    } else {
        $error = "Failed to upload images. Please try again.";
    }
}

// Display success message from session
$success = '';
if (isset($_SESSION['upload_success'])) {
    $success = $_SESSION['upload_success'];
    unset($_SESSION['upload_success']); // Clear after displaying
}

// Get all images for this album
$images_query = $conn->prepare("SELECT * FROM album_images WHERE album_id = ? ORDER BY uploaded_at DESC");
$images_query->bind_param("i", $album_id);
$images_query->execute();
$images = $images_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($album['album_name']); ?> - Upload Images</title>
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
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .album-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .album-header h1 {
            color: #333;
            font-size: 28px;
        }
        
        .album-stats {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .album-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .album-stats i {
            color: #667eea;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .upload-section {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .upload-area {
            border: 3px dashed #e0e0e0;
            border-radius: 15px;
            padding: 60px 40px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .upload-area.dragover {
            border-color: #667eea;
            background: #e8f0ff;
            transform: scale(1.02);
        }
        
        .upload-icon {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .upload-text {
            color: #333;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .upload-subtext {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        input[type="file"] {
            display: none;
        }
        
        .selected-files {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            display: none;
        }
        
        .selected-files h4 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .file-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .file-item {
            padding: 8px 15px;
            background: #e8f0ff;
            border-radius: 20px;
            color: #667eea;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-upload {
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
        
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-upload:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .images-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-header h2 {
            color: #333;
            font-size: 24px;
        }
        
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .image-card {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .image-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        
        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);
            padding: 15px;
            opacity: 0;
            transition: 0.3s;
        }
        
        .image-card:hover .image-overlay {
            opacity: 1;
        }
        
        .image-date {
            color: white;
            font-size: 12px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .qr-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            color: white;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .qr-section h3 {
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .qr-code-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .qr-code-container img {
            max-width: 250px;
            width: 100%;
        }
        
        .qr-link {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            word-break: break-all;
        }
        
        .qr-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-white {
            background: white;
            color: #667eea;
        }
        
        .btn-white:hover {
            background: #f0f0f0;
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
            .album-header {
                flex-direction: column;
            }
            .images-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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
        <div class="album-header">
            <div>
                <h1><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($album['album_name']); ?></h1>
                <div class="album-stats">
                    <span><i class="fas fa-images"></i> <?php echo mysqli_num_rows($images); ?> photos</span>
                    <span><i class="fas fa-calendar"></i> Created <?php echo date('M d, Y', strtotime($album['created_at'])); ?></span>
                </div>
            </div>
            <a href="select_album.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Albums
            </a>
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
        
        <?php if ($album['qr_code']): ?>
            <div class="qr-section">
                <h3><i class="fas fa-qrcode"></i> Share This Album</h3>
                <div class="qr-code-container">
                    <img src="../<?php echo htmlspecialchars($album['qr_code']); ?>" alt="QR Code">
                </div>
                <div class="qr-link">
                    <?php echo htmlspecialchars($album['shareable_link']); ?>
                </div>
                <div class="qr-actions">
                    <button onclick="copyLink()" class="btn btn-white" id="copyBtn">
                        <i class="fas fa-copy"></i> Copy Link
                    </button>
                    <a href="../<?php echo htmlspecialchars($album['qr_code']); ?>" download="qr_code_<?php echo $album['album_id']; ?>.png" class="btn btn-white">
                        <i class="fas fa-download"></i> Download QR
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="upload-section">
            <h2 style="color: #333; margin-bottom: 25px;"><i class="fas fa-cloud-upload-alt"></i> Upload Photos</h2>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" id="uploadArea" onclick="document.getElementById('images').click()">
                    <div class="upload-icon">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="upload-text">Click to select or drag & drop photos</div>
                    <div class="upload-subtext">You can upload multiple images at once (JPG, PNG, GIF)</div>
                </div>
                <input type="file" id="images" name="images[]" accept="image/*" multiple required>
                
                <div class="selected-files" id="selectedFiles">
                    <h4><i class="fas fa-check-circle"></i> Selected Files:</h4>
                    <div class="file-list" id="fileList"></div>
                </div>
                
                <button type="submit" class="btn-upload" id="uploadBtn" disabled>
                    <i class="fas fa-upload"></i> Upload Photos
                </button>
            </form>
        </div>
        
        <div class="images-section">
            <div class="section-header">
                <h2><i class="fas fa-images"></i> Album Photos (<?php echo mysqli_num_rows($images); ?>)</h2>
            </div>
            
            <?php if (mysqli_num_rows($images) > 0): ?>
                <div class="images-grid">
                    <?php while($image = mysqli_fetch_assoc($images)): ?>
                        <div class="image-card">
                            <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" alt="Album Image">
                            <div class="image-overlay">
                                <div class="image-date">
                                    <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($image['uploaded_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-image"></i>
                    <h3>No Photos Yet</h3>
                    <p>Upload photos to this album to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        const fileInput = document.getElementById('images');
        const uploadArea = document.getElementById('uploadArea');
        const selectedFiles = document.getElementById('selectedFiles');
        const fileList = document.getElementById('fileList');
        const uploadBtn = document.getElementById('uploadBtn');
        
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                displaySelectedFiles(e.target.files);
            }
        });
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                displaySelectedFiles(e.dataTransfer.files);
            }
        });
        
        function displaySelectedFiles(files) {
            fileList.innerHTML = '';
            for (let i = 0; i < files.length; i++) {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = '<i class="fas fa-image"></i> ' + files[i].name;
                fileList.appendChild(fileItem);
            }
            selectedFiles.style.display = 'block';
            uploadBtn.disabled = false;
        }
        
        function copyLink() {
            const link = "<?php echo $album['shareable_link'] ?? ''; ?>";
            const copyBtn = document.getElementById('copyBtn');
            
            navigator.clipboard.writeText(link).then(() => {
                const originalHTML = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalHTML;
                }, 2000);
            });
        }
        
        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
