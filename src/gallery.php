<?php
require_once 'api.php';

requireAdminAuth();

error_reporting(E_ALL);
ini_set('display_errors', 1);

initializeUploadSettings();

$uploadDir = 'uploads/';
$thumbDir = 'uploads/thumbs/';

initializeUploadDirectories($uploadDir, $thumbDir);

$allowedMimeTypes = [
    'image/jpeg' => ['jpg', 'jpeg'],
    'image/png' => ['png'],
    'image/gif' => ['gif'],
    'image/webp' => ['webp']
];

$maxFileSize = 10 * 1024 * 1024;
$maxFiles = 100;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $message = "Security error: Invalid request";
        $messageType = "error";
    } else {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $message = "Upload error code: " . $_FILES['image']['error'];
            $messageType = "error";
        } else {
            $result = processImageUpload($_FILES['image'], $uploadDir, $thumbDir, $allowedMimeTypes, $maxFileSize, $maxFiles);
            
            if ($result['success']) {
                $message = $result['message'];
                $messageType = "success";
            } else {
                $message = $result['message'];
                $messageType = "error";
            }
        }
    }
}

$images = getImages($uploadDir);

if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    if (!isset($_GET['csrf_token']) || !validateCSRFToken($_GET['csrf_token'])) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?error=csrf');
        exit;
    }
    
    $result = deleteImageFile($_GET['delete'], $uploadDir, $thumbDir, $images);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$csrfToken = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Media Server</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #0a0a0a;
            min-height: 100vh;
            padding: 0;
            color: #ffffff;
            margin: 0;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #111111;
            min-height: 100vh;
            position: relative;
        }
        
        .header {
            background: #111111;
            color: #ffffff;
            padding: 40px 40px 20px;
            text-align: left;
            border-bottom: 1px solid #222222;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .header-left h1 {
            font-size: 3rem;
            margin: 0 0 8px 0;
            font-weight: 300;
            letter-spacing: -0.02em;
        }
        
        .header-left p {
            font-size: 1.1rem;
            color: #888888;
            margin: 0;
            font-weight: 400;
        }
        
        .header-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
        }
        
        .user-info {
            font-size: 14px;
            color: #cccccc;
            text-align: right;
        }
        
        .admin-badge {
            background: #333333;
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 8px;
        }
        
        .logout-btn {
            background: transparent;
            color: #ef4444;
            border: 1px solid #ef4444;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.15s ease;
            font-weight: 500;
        }
        
        .logout-btn:hover {
            background: #ef4444;
            color: #ffffff;
        }
        
        .upload-section {
            padding: 40px;
            background: #111111;
            border-bottom: 1px solid #222222;
        }
        
        .upload-form {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 16px;
            max-width: 600px;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            background: #1a1a1a;
            color: #ffffff;
            padding: 14px 24px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid #333333;
            font-size: 14px;
            font-weight: 500;
            flex: 1;
            user-select: none;
        }
        
        .file-input-wrapper:hover {
            background: #222222;
            border-color: #444444;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        
        .upload-btn {
            background: #ffffff;
            color: #000000;
            border: none;
            padding: 14px 32px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        
        .upload-btn:hover {
            background: #f0f0f0;
        }
        
        .upload-btn:active {
            transform: translateY(1px);
        }
        
        .message {
            padding: 16px 24px;
            border-radius: 4px;
            margin: 24px 0;
            font-weight: 500;
            font-size: 14px;
        }
        
        .message.success {
            background: #0f2a1a;
            color: #4ade80;
            border: 1px solid #166534;
        }
        
        .message.error {
            background: #2d1b1b;
            color: #f87171;
            border: 1px solid #991b1b;
        }
        
        .gallery {
            padding: 40px;
            background: #111111;
        }
        
        .gallery h2 {
            margin: 0 0 40px 0;
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 500;
            letter-spacing: -0.01em;
        }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        
        .image-card {
            background: #1a1a1a;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #222222;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .image-card:hover {
            border-color: #333333;
            transform: translateY(-2px);
        }
        
        .image-card img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .image-card:hover img {
            opacity: 0.9;
        }
        
        .image-info {
            padding: 20px;
            background: #1a1a1a;
        }
        
        .image-name {
            font-weight: 500;
            margin-bottom: 12px;
            color: #ffffff;
            word-break: break-all;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .image-actions {
            display: flex;
            gap: 8px;
            margin-top: 0;
        }
        
        .btn {
            padding: 8px 16px;
            border: 1px solid transparent;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.15s ease;
            font-weight: 500;
            text-align: center;
        }
        
        .btn-view {
            background: #333333;
            color: #ffffff;
            border-color: #333333;
        }
        
        .btn-view:hover {
            background: #444444;
            border-color: #444444;
        }
        
        .btn-delete {
            background: transparent;
            color: #ef4444;
            border-color: #ef4444;
        }
        
        .btn-delete:hover {
            background: #ef4444;
            color: #ffffff;
        }
        
        .empty-gallery {
            text-align: center;
            color: #666666;
            font-size: 1rem;
            margin: 80px 0;
            font-weight: 400;
        }
        
        /* 모달 스타일 */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.98);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            margin: auto;
            display: block;
            width: 90%;
            max-width: 900px;
            max-height: 90%;
            object-fit: contain;
            margin-top: 5vh;
        }
        
        .close {
            position: absolute;
            top: 24px;
            right: 32px;
            color: #ffffff;
            font-size: 32px;
            font-weight: 300;
            cursor: pointer;
            z-index: 1001;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            transition: all 0.2s ease;
        }
        
        .close:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 24px;
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .header-left h1 {
                font-size: 2.5rem;
            }
            
            .header-right {
                align-items: flex-start;
                width: 100%;
            }
            
            .upload-section {
                padding: 24px;
            }
            
            .upload-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .gallery {
                padding: 24px;
            }
            
            .image-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 16px;
            }
            
            .modal-content {
                width: 95%;
                margin-top: 10vh;
            }
            
            .close {
                top: 16px;
                right: 16px;
                font-size: 28px;
            }
        }
        
        @media (max-width: 480px) {
            .image-grid {
                grid-template-columns: 1fr;
            }
            
            .header-left h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>Gallery</h1>
                <p>Modern media server for image management</p>
            </div>
            <div class="header-right">
                <div class="user-info">
                    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                    <span class="admin-badge">ADMIN</span>
                </div>
                <a href="/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="upload-section">
            <form class="upload-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <label class="file-input-wrapper">
                    <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" required>
                    <span class="file-label">Choose File</span>
                </label>
                <button type="submit" class="upload-btn">Upload</button>
            </form>
            
            <?php if (isset($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="gallery">
            <h2>Images (<?php echo count($images); ?>)</h2>
            
            <?php if (empty($images)): ?>
                <div class="empty-gallery">
                    No images uploaded yet.<br>
                    Upload your first image above.
                </div>
            <?php else: ?>
                <div class="image-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="image-card">
                            <?php 
                            $thumbPath = $thumbDir . $image;
                            $imagePath = $uploadDir . $image;
                            $displayImage = file_exists($thumbPath) ? $thumbPath : $imagePath;
                            ?>
                            <img src="<?php echo htmlspecialchars($displayImage); ?>" 
                                 alt="<?php echo htmlspecialchars($image); ?>"
                                 onclick="openModal('<?php echo htmlspecialchars($imagePath); ?>')">
                            <div class="image-info">
                                <div class="image-name"><?php echo htmlspecialchars($image); ?></div>
                                <div class="image-actions">
                                    <a href="/image_view.php?file_path=uploads/<?php echo urlencode($image); ?>" 
                                       class="btn btn-view">View</a>
                                    <a href="?delete=<?php echo urlencode($image); ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('Delete this image?')">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.querySelector('input[type="file"]');
            const fileLabel = document.querySelector('.file-label');
            
            if (fileInput && fileLabel) {
                fileInput.addEventListener('change', function(e) {
                    const fileName = e.target.files[0]?.name;
                    if (fileName) {
                        const shortName = fileName.length > 30 ? fileName.substring(0, 27) + '...' : fileName;
                        fileLabel.textContent = shortName;
                    } else {
                        fileLabel.textContent = 'Choose File';
                    }
                });
            }
        });
        
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImg.src = imageSrc;
        }
        
        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>