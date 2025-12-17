<?php
require_once '../config.php';
require_once '../functions.php';
requireStudioLogin();

$studio_id = $_SESSION['studio_id'];

$sql = "SELECT a.*, COUNT(ai.image_id) as image_count 
        FROM albums a 
        LEFT JOIN album_images ai ON a.album_id = ai.album_id 
        WHERE a.studio_id = $studio_id
        GROUP BY a.album_id 
        ORDER BY a.created_at DESC";
$result = mysqli_query($conn, $sql);

$delete_success = isset($_GET['deleted']) && $_GET['deleted'] == 'success';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Albums</title>
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
        
        .header {
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
        
        .header h1 {
            color: #333;
            font-size: 28px;
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
        
        .success-message {
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
        
        .albums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .album-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
        }
        
        .album-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }
        
        .album-cover {
            width: 100%;
            height: 250px;
            object-fit: cover;
            cursor: pointer;
        }
        
        .album-info {
            padding: 20px;
        }
        
        .album-name {
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .album-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .album-meta i {
            color: #667eea;
        }
        
        .album-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 8px 15px;
            font-size: 14px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-weight: 600;
            flex: 1;
            justify-content: center;
            min-width: 80px;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-view:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-qr {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .btn-qr:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-qr:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }
        
        .btn-delete:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .empty-state i {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
            color: #667eea;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #666;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 600px;
            margin: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: zoomIn 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .modal-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .modal-icon.delete {
            color: #ff6b6b;
        }
        
        .modal-icon.qr {
            color: #3498db;
        }
        
        .modal-content h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .modal-content p {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .qr-display {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
        }
        
        .qr-display img {
            max-width: 300px;
            width: 100%;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .qr-link {
            background: white;
            padding: 15px;
            border-radius: 10px;
            word-break: break-all;
            font-size: 14px;
            color: #667eea;
            margin-top: 15px;
            border: 2px solid #667eea;
        }
        
        .copy-link-btn {
            margin-top: 15px;
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .copy-link-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .copy-link-btn.copied {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-cancel {
            background: #e0e0e0;
            color: #666;
        }
        
        .btn-cancel:hover {
            background: #d0d0d0;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }
        
        .btn-confirm:hover {
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }
        
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f0f0f0;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .close-btn:hover {
            background: #e0e0e0;
            transform: rotate(90deg);
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
            .albums-grid {
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
            <a href="create_album.php"><i class="fas fa-plus"></i> Create Album</a>
            <a href="select_album.php"><i class="fas fa-folder"></i> My Albums</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if ($delete_success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle" style="font-size: 24px;"></i>
                <span>Album deleted successfully!</span>
            </div>
        <?php endif; ?>
        
        <div class="header">
            <h1><i class="fas fa-folder-open"></i> My Albums (<?php echo mysqli_num_rows($result); ?>)</h1>
            <a href="create_album.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Create New Album
            </a>
        </div>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="albums-grid">
                <?php while($album = mysqli_fetch_assoc($result)): ?>
                    <div class="album-card">
                        <img src="../<?php echo htmlspecialchars($album['cover_image']); ?>" 
                             alt="Album Cover" 
                             class="album-cover"
                             onclick="location.href='upload_images.php?album_id=<?php echo $album['album_id']; ?>'">
                        <div class="album-info">
                            <div class="album-name">
                                <?php echo htmlspecialchars($album['album_name']); ?>
                            </div>
                            <div class="album-meta">
                                <span><i class="fas fa-images"></i> <?php echo $album['image_count']; ?> photos</span>
                                <span><i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($album['created_at'])); ?></span>
                            </div>
                            <div class="album-actions">
                                <a href="upload_images.php?album_id=<?php echo $album['album_id']; ?>" class="btn-small btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if ($album['qr_code']): ?>
                                    <button onclick="showQRCode(<?php echo $album['album_id']; ?>, '<?php echo htmlspecialchars(addslashes($album['album_name'])); ?>', '../<?php echo htmlspecialchars($album['qr_code']); ?>', '<?php echo htmlspecialchars($album['shareable_link']); ?>')" 
                                            class="btn-small btn-qr">
                                        <i class="fas fa-qrcode"></i> QR
                                    </button>
                                <?php else: ?>
                                    <button class="btn-small btn-qr" disabled title="Upload images first to generate QR">
                                        <i class="fas fa-qrcode"></i> QR
                                    </button>
                                <?php endif; ?>
                                <button onclick="confirmDelete(<?php echo $album['album_id']; ?>, '<?php echo htmlspecialchars(addslashes($album['album_name'])); ?>')" 
                                        class="btn-small btn-delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-images"></i>
                <h3>No Albums Yet</h3>
                <p>Create your first album to get started!</p>
                <a href="create_album.php" class="btn btn-primary" style="margin-top: 30px;">
                    <i class="fas fa-plus-circle"></i> Create First Album
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- QR Code Modal -->
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeQRModal()">×</button>
            <div class="modal-icon qr">
                <i class="fas fa-qrcode"></i>
            </div>
            <h3 id="qrAlbumName"></h3>
            <p>Scan this QR code or share the link to let users find their photos</p>
            <div class="qr-display">
                <img id="qrImage" src="" alt="QR Code">
                <div class="qr-link" id="qrLink"></div>
                <button onclick="copyLink()" class="copy-link-btn" id="copyBtn">
                    <i class="fas fa-copy"></i> Copy Link
                </button>
            </div>
            <div class="modal-actions">
                <button onclick="closeQRModal()" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Close
                </button>
                <a id="downloadQRBtn" href="" download class="btn btn-primary">
                    <i class="fas fa-download"></i> Download QR
                </a>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeDeleteModal()">×</button>
            <div class="modal-icon delete">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Delete Album?</h3>
            <p>Are you sure you want to delete "<strong id="albumNameText"></strong>"?<br>
            This will permanently delete the album, all its images, and QR code.</p>
            <div class="modal-actions">
                <button onclick="closeDeleteModal()" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-confirm">
                    <i class="fas fa-trash"></i> Delete Album
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // QR Code Modal Functions
        function showQRCode(albumId, albumName, qrCode, shareableLink) {
            const modal = document.getElementById('qrModal');
            const qrImage = document.getElementById('qrImage');
            const qrLink = document.getElementById('qrLink');
            const qrAlbumName = document.getElementById('qrAlbumName');
            const downloadBtn = document.getElementById('downloadQRBtn');
            
            qrAlbumName.textContent = albumName;
            qrImage.src = qrCode;
            qrLink.textContent = shareableLink;
            downloadBtn.href = qrCode;
            downloadBtn.download = 'qr_code_' + albumName.replace(/\s+/g, '_') + '.png';
            
            modal.classList.add('active');
        }
        
        function closeQRModal() {
            const modal = document.getElementById('qrModal');
            modal.classList.remove('active');
        }
        
        function copyLink() {
            const linkText = document.getElementById('qrLink').textContent;
            const copyBtn = document.getElementById('copyBtn');
            
            navigator.clipboard.writeText(linkText).then(() => {
                const originalHTML = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                copyBtn.classList.add('copied');
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalHTML;
                    copyBtn.classList.remove('copied');
                }, 2000);
            });
        }
        
        // Delete Modal Functions
        function confirmDelete(albumId, albumName) {
            const modal = document.getElementById('deleteModal');
            const albumNameText = document.getElementById('albumNameText');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            albumNameText.textContent = albumName;
            confirmBtn.href = 'delete_album.php?id=' + albumId + '&confirm=yes';
            
            modal.classList.add('active');
        }
        
        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('active');
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const qrModal = document.getElementById('qrModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target == qrModal) {
                closeQRModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }
        
        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeQRModal();
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>
