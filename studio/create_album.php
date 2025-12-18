<?php
require_once '../config.php';
require_once '../functions.php';
requireStudioLogin();

$studio_id = $_SESSION['studio_id'];
$success = '';
$error = '';

// Get all customers for this studio
$customers_query = $conn->prepare("SELECT customer_id, customer_name, email FROM customers WHERE studio_id = ? ORDER BY customer_name ASC");
$customers_query->bind_param("i", $studio_id);
$customers_query->execute();
$customers = $customers_query->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = intval($_POST['customer_id']);
    $album_name = clean_input($_POST['album_name']);
    
    // Verify customer belongs to this studio
    $verify = $conn->prepare("SELECT customer_id FROM customers WHERE customer_id = ? AND studio_id = ?");
    $verify->bind_param("ii", $customer_id, $studio_id);
    $verify->execute();
    if ($verify->get_result()->num_rows == 0) {
        $error = "Invalid customer selected.";
    } else {
        // Validate and upload cover image
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $file_extension = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $error = "Invalid file type. Please upload JPG, JPEG, PNG, or GIF.";
            } else {
                $imageinfo = getimagesize($_FILES['cover_image']['tmp_name']);
                
                if ($imageinfo === false) {
                    $error = "File is not a valid image.";
                } else {
                    $cover_filename = "cover_" . uniqid() . '.' . $file_extension;
                    $cover_filepath = COVERS_PATH . $cover_filename;
                    $cover_relative = "uploads/covers/" . $cover_filename;
                    
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_filepath)) {
                        // Insert album into database
                        $stmt = $conn->prepare("INSERT INTO albums (studio_id, customer_id, album_name, cover_image) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iiss", $studio_id, $customer_id, $album_name, $cover_relative);
                        
                        if ($stmt->execute()) {
                            $album_id = $stmt->insert_id;
                            
                            // Create album folder
                            $album_folder = ALBUMS_PATH . $album_id;
                            if (!file_exists($album_folder)) {
                                mkdir($album_folder, 0755, true);
                            }
                            
                            $stmt->close();
                            header("Location: upload_images.php?album_id=" . $album_id);
                            exit();
                        } else {
                            $error = "Error creating album: " . $stmt->error;
                        }
                    } else {
                        $error = "Failed to upload cover image.";
                    }
                }
            }
        } else {
            $error = "Please select a cover image.";
        }
    }
    $verify->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Album</title>
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
            max-width: 700px;
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
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
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
        
        .warning-box {
            background: #fff9e6;
            border-left: 4px solid #f39c12;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            color: #856404;
        }
        
        .warning-box a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }
        
        .warning-box a:hover {
            text-decoration: underline;
        }
        
        .form-group {
            margin-bottom: 30px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }
        
        label i {
            color: #667eea;
            margin-right: 8px;
        }
        
        select,
        input[type="text"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        select:focus,
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .upload-area {
            border: 3px dashed #e0e0e0;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
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
            font-size: 50px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .upload-text {
            color: #666;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .upload-subtext {
            color: #999;
            font-size: 14px;
        }
        
        input[type="file"] {
            display: none;
        }
        
        .preview-container {
            margin-top: 20px;
            display: none;
        }
        
        .preview-image {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            <a href="create_album.php"><i class="fas fa-plus"></i> Create Album</a>
            <a href="select_album.php"><i class="fas fa-folder"></i> My Albums</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-folder-plus"></i>
                </div>
                <h1>Create New Album</h1>
                <p class="subtitle">Select customer and create their photo album</p>
            </div>
            
            <?php if (mysqli_num_rows($customers) == 0): ?>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>No customers found!</strong> You need to add a customer first before creating an album.
                    <br><br>
                    <a href="add_customer.php"><i class="fas fa-user-plus"></i> Add Customer Now</a>
                </div>
            <?php else: ?>
            
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle" style="font-size: 20px;"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="albumForm">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Select Customer</label>
                    <select name="customer_id" required>
                        <option value="">-- Choose Customer --</option>
                        <?php while($customer = mysqli_fetch_assoc($customers)): ?>
                            <option value="<?php echo $customer['customer_id']; ?>">
                                <?php echo htmlspecialchars($customer['customer_name']); ?> 
                                (<?php echo htmlspecialchars($customer['email']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Album Name</label>
                    <input type="text" name="album_name" placeholder="e.g., Wedding 2024, Birthday Party" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-image"></i> Cover Image</label>
                    <div class="upload-area" id="uploadArea" onclick="document.getElementById('cover_image').click()">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">Click to upload or drag & drop</div>
                        <div class="upload-subtext">JPG, PNG, GIF (Max 5MB)</div>
                    </div>
                    <input type="file" id="cover_image" name="cover_image" accept="image/*" required>
                    
                    <div class="preview-container" id="previewContainer">
                        <img id="previewImage" class="preview-image" alt="Preview">
                    </div>
                </div>
                
                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    <i class="fas fa-plus-circle"></i> Create Album
                </button>
            </form>
            
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        const fileInput = document.getElementById('cover_image');
        const uploadArea = document.getElementById('uploadArea');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const submitBtn = document.getElementById('submitBtn');
        
        // File input change
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });
        
        // Drag and drop
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
                handleFile(e.dataTransfer.files[0]);
            }
        });
        
        function handleFile(file) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                    submitBtn.disabled = false;
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>
