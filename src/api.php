<?php
function validateImageFile($file, $allowedMimeTypes, $maxFileSize) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error code: ' . $file['error']];
    }
    
    if ($file['size'] > $maxFileSize || $file['size'] <= 0) {
        return ['valid' => false, 'error' => 'Invalid file size: ' . $file['size'] . ' bytes'];
    }
    
    if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Temporary file not accessible'];
    }
    
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['valid' => false, 'error' => 'Not a valid image file'];
    }
    
    $detectedMime = $imageInfo['mime'];
    if (!array_key_exists($detectedMime, $allowedMimeTypes)) {
        return ['valid' => false, 'error' => 'Unsupported image type: ' . $detectedMime];
    }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedMimeTypes[$detectedMime])) {
        return ['valid' => false, 'error' => 'File extension mismatch: ' . $fileExt];
    }
    
    if ($imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
        return ['valid' => false, 'error' => 'Image dimensions too large: ' . $imageInfo[0] . 'x' . $imageInfo[1]];
    }
    
    $handle = fopen($file['tmp_name'], 'rb');
    if (!$handle) {
        return ['valid' => false, 'error' => 'Cannot read file'];
    }
    
    $header = fread($handle, 10);
    fclose($handle);
    
    $validHeaders = [
        "\xFF\xD8\xFF",
        "\x89\x50\x4E\x47",
        "\x47\x49\x46\x38",
        "\x52\x49\x46\x46"
    ];
    
    $headerValid = false;
    foreach ($validHeaders as $validHeader) {
        if (strpos($header, $validHeader) === 0) {
            $headerValid = true;
            break;
        }
    }
    
    if (!$headerValid) {
        return ['valid' => false, 'error' => 'Invalid file header'];
    }
    
    return ['valid' => true, 'mime' => $detectedMime, 'ext' => $fileExt, 'info' => $imageInfo];
}

function generateSafeFileName($originalExt) {
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    return $uuid . '.' . $originalExt;
}

function checkFileLimit($directory, $limit) {
    $count = 0;
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && is_file($directory . $file)) {
                $count++;
            }
        }
    }
    return $count < $limit;
}

function createSecureThumbnail($source, $destination, $width, $height, $mimeType) {
    if (!extension_loaded('gd')) {
        return false;
    }
    
    $imageInfo = @getimagesize($source);
    if (!$imageInfo) {
        return false;
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    
    $memoryNeeded = $originalWidth * $originalHeight * 4;
    if ($memoryNeeded > 100 * 1024 * 1024) {
        return false;
    }
    
    $originalImage = null;
    switch ($mimeType) {
        case 'image/jpeg':
            if (function_exists('imagecreatefromjpeg')) {
                $originalImage = @imagecreatefromjpeg($source);
            }
            break;
        case 'image/png':
            if (function_exists('imagecreatefrompng')) {
                $originalImage = @imagecreatefrompng($source);
            }
            break;
        case 'image/gif':
            if (function_exists('imagecreatefromgif')) {
                $originalImage = @imagecreatefromgif($source);
            }
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $originalImage = @imagecreatefromwebp($source);
            }
            break;
        default:
            return false;
    }
    
    if (!$originalImage) {
        return false;
    }
    
    $ratio = min($width / $originalWidth, $height / $originalHeight);
    $newWidth = intval($originalWidth * $ratio);
    $newHeight = intval($originalHeight * $ratio);
    
    if (!function_exists('imagecreatetruecolor')) {
        imagedestroy($originalImage);
        return false;
    }
    
    $thumbnail = @imagecreatetruecolor($newWidth, $newHeight);
    if (!$thumbnail) {
        imagedestroy($originalImage);
        return false;
    }
    
    if ($mimeType === 'image/png') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefill($thumbnail, 0, 0, $transparent);
    }
    
    $result = @imagecopyresampled($thumbnail, $originalImage, 0, 0, 0, 0, 
                                $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    if (!$result) {
        imagedestroy($originalImage);
        imagedestroy($thumbnail);
        return false;
    }
    
    $saveResult = false;
    switch ($mimeType) {
        case 'image/jpeg':
            if (function_exists('imagejpeg')) {
                $saveResult = @imagejpeg($thumbnail, $destination, 85);
            }
            break;
        case 'image/png':
            if (function_exists('imagepng')) {
                $saveResult = @imagepng($thumbnail, $destination, 6);
            }
            break;
        case 'image/gif':
            if (function_exists('imagegif')) {
                $saveResult = @imagegif($thumbnail, $destination);
            }
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $saveResult = @imagewebp($thumbnail, $destination, 85);
            }
            break;
    }
    
    imagedestroy($originalImage);
    imagedestroy($thumbnail);
    
    return $saveResult;
}

function getImages($directory) {
    $images = [];
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && $file !== '.htaccess' && is_file($directory . $file)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $fullPath = $directory . $file;
                    $imageInfo = @getimagesize($fullPath);
                    if ($imageInfo !== false) {
                        $images[] = $file;
                    }
                }
            }
        }
        usort($images, function($a, $b) use ($directory) {
            return filemtime($directory . $b) - filemtime($directory . $a);
        });
    }
    return $images;
}

function executeQuery($db, $query, $pass) {
    $stmt = $db->prepare($query);
    $stmt->execute([$pass]);
    return $stmt;
}

function initializeUploadDirectories($uploadDir, $thumbDir) {
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        file_put_contents($uploadDir . '.htaccess', "php_flag engine off\nAddType text/plain .php .php3 .phtml .pht .phps");
    }
    if (!file_exists($thumbDir)) {
        mkdir($thumbDir, 0755, true);
        file_put_contents($thumbDir . '.htaccess', "php_flag engine off\nAddType text/plain .php .php3 .phtml .pht .phps");
    }
}

function processImageUpload($file, $uploadDir, $thumbDir, $allowedMimeTypes, $maxFileSize, $maxFiles) {
    if (!checkFileLimit($uploadDir, $maxFiles)) {
        return ['success' => false, 'message' => "Maximum file limit reached ($maxFiles files)"];
    }
    
    $validation = validateImageFile($file, $allowedMimeTypes, $maxFileSize);
    
    if (!$validation['valid']) {
        return ['success' => false, 'message' => "Upload failed: " . $validation['error']];
    }
    
    $newFileName = generateSafeFileName($validation['ext']);
    $uploadPath = $uploadDir . $newFileName;
    $thumbPath = $thumbDir . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        chmod($uploadPath, 0644);
        
        if (extension_loaded('gd')) {
            if (createSecureThumbnail($uploadPath, $thumbPath, 300, 300, $validation['mime'])) {
                chmod($thumbPath, 0644);
            }
        }
        
        return ['success' => true, 'message' => 'File uploaded successfully!', 'filename' => $newFileName];
    } else {
        return ['success' => false, 'message' => 'File upload failed - check directory permissions'];
    }
}

function deleteImageFile($filename, $uploadDir, $thumbDir, $validImages) {
    $deleteFile = basename($filename);
    $filePath = $uploadDir . $deleteFile;
    $thumbPath = $thumbDir . $deleteFile;
    
    if (file_exists($filePath) && in_array($deleteFile, $validImages)) {
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo !== false) {
            unlink($filePath);
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
            return ['success' => true, 'message' => 'File deleted successfully'];
        }
    }
    
    return ['success' => false, 'message' => 'File not found or invalid'];
}


function generateCSRFToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}


function validateCSRFToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}


function requireAdminAuth() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: /login.php');
        exit;
    }
}

function initializeUploadSettings() {
    ini_set('file_uploads', 1);
    ini_set('upload_max_filesize', '10M');
    ini_set('post_max_size', '10M');
    ini_set('max_execution_time', 30);
}
?>