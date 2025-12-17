<?php
require_once '../config.php';
require_once '../functions.php';

$album_id = isset($_GET['album']) ? intval($_GET['album']) : 0;
$error = '';
$matches = [];
$searched = false;

if ($album_id <= 0) {
    die("Invalid album ID");
}

$stmt = $conn->prepare("SELECT a.*, s.studio_name FROM albums a JOIN studios s ON a.studio_id = s.studio_id WHERE a.album_id = ?");
$stmt->bind_param("i", $album_id);
$stmt->execute();
$album_result = $stmt->get_result();

if ($album_result->num_rows == 0) {
    die("Album not found");
}

$album = $album_result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['user_photo'])) {
    $searched = true;
    
    if ($_FILES['user_photo']['error'] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['user_photo']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $error = "Invalid file type. Please upload JPG, JPEG, PNG, or GIF.";
        } else {
            $imageinfo = getimagesize($_FILES['user_photo']['tmp_name']);
            
            if ($imageinfo === false) {
                $error = "File is not a valid image.";
            } else {
                $temp_image = "../uploads/search_temp/" . uniqid('search_', true) . '.jpg';
                
                if (move_uploaded_file($_FILES['user_photo']['tmp_name'], $temp_image)) {
                    $album_folder = "../uploads/albums/" . $album_id . "/";
                    
                    $python_path = "python";
                    $script_path = "../face_matcher.py";
                    $command = escapeshellcmd("$python_path $script_path") . " " . escapeshellarg($temp_image) . " " . escapeshellarg($album_folder);
                    
                    $output = shell_exec($command . " 2>&1");
                    $result = json_decode($output, true);
                    
                    if (file_exists($temp_image)) {
                        unlink($temp_image);
                    }
                    
                    if ($result && isset($result['matches'])) {
                        $matches = $result['matches'];
                        
                        $match_count = count($matches);
                        $stmt = $conn->prepare("INSERT INTO search_logs (album_id, search_image, matches_found) VALUES (?, ?, ?)");
                        $stmt->bind_param("isi", $album_id, $temp_image, $match_count);
                        $stmt->execute();
                        $stmt->close();
                    } elseif ($result && isset($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $error = "Error processing image. Please try again.";
                    }
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }
    } else {
        $error = "Please select an image to upload.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Your Photos - <?php echo htmlspecialchars($album['album_name']); ?></title>
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
            max-width: 1200px;
            margin: 50px auto;
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .header-icon {
            font-size: 70px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        h1 {
            color: #333;
            font-size: 36px;
            margin-bottom: 15px;
        }
        
        .album-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .studio-name {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .upload-section {
            max-width: 600px;
            margin: 0 auto 50px;
        }
        
        .upload-container {
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            transition: all 0.3s ease;
            border: 4px dashed rgba(255,255,255,0.5);
        }
        
        .upload-container:hover {
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
            border-color: white;
        }
        
        .upload-container.dragover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            border-color: white;
        }
        
        .file-input-wrapper {
            position: relative;
            cursor: pointer;
        }
        
        input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            cursor: pointer;
        }
        
        .upload-icon {
            font-size: 80px;
            color: white;
            margin-bottom: 20px;
            pointer-events: none;
        }
        
        .upload-text {
            color: white;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
            pointer-events: none;
        }
        
        .upload-subtext {
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            margin-bottom: 25px;
            pointer-events: none;
        }
        
        .file-preview {
            margin-top: 20px;
            padding: 15px 25px;
            background: rgba(255,255,255,0.2);
            border-radius: 50px;
            color: white;
            font-weight: 600;
            display: none;
            backdrop-filter: blur(10px);
        }
        
        .btn-search {
            width: 100%;
            padding: 18px;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-search:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 25px rgba(0,0,0,0.3);
        }
        
        .btn-search:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 16px;
            box-shadow: 0 5px 20px rgba(255, 107, 107, 0.4);
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .results-section {
            margin-top: 50px;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .results-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            border-radius: 15px;
            color: white;
        }
        
        .results-header h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .results-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .download-all-btn {
            margin-top: 15px;
            padding: 12px 30px;
            background: white;
            color: #27ae60;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .download-all-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }
        
        .result-card {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            animation: zoomIn 0.5s ease;
        }
        
        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .result-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
        }
        
        .result-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
        }
        
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.7) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: flex-end;
            padding: 20px;
        }
        
        .result-card:hover .image-overlay {
            opacity: 1;
        }
        
        .match-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);
        }
        
        .download-btn {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: white;
            color: #667eea;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            opacity: 0;
            transform: translateY(10px);
        }
        
        .result-card:hover .download-btn {
            opacity: 1;
            transform: translateY(0);
        }
        
        .download-btn:hover {
            background: #667eea;
            color: white;
            transform: scale(1.1) translateY(0);
        }
        
        .no-match {
            text-align: center;
            padding: 80px 40px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 20px;
        }
        
        .no-match-icon {
            font-size: 100px;
            margin-bottom: 25px;
            opacity: 0.3;
        }
        
        .no-match h3 {
            font-size: 28px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .no-match p {
            font-size: 16px;
            color: #999;
            line-height: 1.6;
        }
        
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            flex-direction: column;
        }
        
        .loading.active {
            display: flex;
        }
        
        .spinner {
            width: 80px;
            height: 80px;
            border: 8px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: white;
            margin-top: 20px;
            font-size: 18px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            h1 {
                font-size: 28px;
            }
            .results-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <div class="loading-text">Searching for your face...</div>
    </div>
    
    <div class="container">
        <div class="header">
            <div class="header-icon">🔍</div>
            <h1>Find Your Photos</h1>
            <div class="album-badge">
                <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($album['album_name']); ?>
            </div>
            <div class="studio-name">
                <i class="fas fa-camera"></i> <?php echo htmlspecialchars($album['studio_name']); ?>
            </div>
        </div>
        
        <div class="upload-section">
            <form method="POST" enctype="multipart/form-data" id="searchForm">
                <div class="upload-container" id="uploadContainer">
                    <label for="user_photo" class="file-input-wrapper">
                        <input type="file" id="user_photo" name="user_photo" accept="image/*" required>
                        <div class="upload-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="upload-text">Upload Your Photo</div>
                        <div class="upload-subtext">Click or drag & drop your photo here</div>
                    </label>
                    <div class="file-preview" id="filePreview"></div>
                </div>
                <button type="submit" class="btn-search" id="submitBtn">
                    <i class="fas fa-search"></i> Search for Matches
                </button>
            </form>
        </div>
        
        <?php if ($searched): ?>
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-triangle" style="font-size: 24px;"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php elseif (count($matches) > 0): ?>
                <div class="results-section">
                    <div class="results-header">
                        <h2><i class="fas fa-check-circle"></i> Match Found!</h2>
                        <p>We found <?php echo count($matches); ?> photo<?php echo count($matches) > 1 ? 's' : ''; ?> with your face</p>
                        <button onclick="downloadAll()" class="download-all-btn">
                            <i class="fas fa-download"></i> Download All (<?php echo count($matches); ?>)
                        </button>
                    </div>
                    <div class="results-grid">
                        <?php foreach ($matches as $index => $match): ?>
                            <div class="result-card" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                <img src="../uploads/albums/<?php echo $album_id . '/' . htmlspecialchars($match); ?>" 
                                     alt="Match <?php echo $index + 1; ?>"
                                     class="match-image">
                                <div class="image-overlay"></div>
                                <div class="match-badge">
                                    <i class="fas fa-check"></i> Match
                                </div>
                                <a href="../uploads/albums/<?php echo $album_id . '/' . htmlspecialchars($match); ?>" 
                                   download="photo_<?php echo $index + 1; ?>.<?php echo pathinfo($match, PATHINFO_EXTENSION); ?>"
                                   class="download-btn"
                                   title="Download Image">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-match">
                    <div class="no-match-icon">😔</div>
                    <h3>No Matching Photos Found</h3>
                    <p>We couldn't find any photos with your face in this album.<br>Try uploading a different photo with a clear view of your face.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script>
        const fileInput = document.getElementById('user_photo');
        const filePreview = document.getElementById('filePreview');
        const uploadContainer = document.getElementById('uploadContainer');
        const searchForm = document.getElementById('searchForm');
        const loading = document.getElementById('loading');
        const submitBtn = document.getElementById('submitBtn');
        
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const fileName = e.target.files[0].name;
                filePreview.innerHTML = '<i class="fas fa-check-circle"></i> ' + fileName;
                filePreview.style.display = 'block';
                submitBtn.disabled = false;
            }
        });
        
        uploadContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadContainer.classList.add('dragover');
        });
        
        uploadContainer.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadContainer.classList.remove('dragover');
        });
        
        uploadContainer.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadContainer.classList.remove('dragover');
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                const fileName = e.dataTransfer.files[0].name;
                filePreview.innerHTML = '<i class="fas fa-check-circle"></i> ' + fileName;
                filePreview.style.display = 'block';
                submitBtn.disabled = false;
            }
        });
        
        searchForm.addEventListener('submit', function(e) {
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select a photo first!');
                return false;
            }
            loading.classList.add('active');
        });
        
        document.addEventListener('dragover', (e) => e.preventDefault());
        document.addEventListener('drop', (e) => e.preventDefault());
        
        // Download all images function
        function downloadAll() {
            const images = document.querySelectorAll('.match-image');
            images.forEach((img, index) => {
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = img.src;
                    link.download = 'photo_' + (index + 1) + '.jpg';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }, index * 300);
            });
        }
    </script>
</body>
</html>
