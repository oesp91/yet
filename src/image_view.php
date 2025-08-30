<?php
require_once 'api.php';

requireAdminAuth();

$imageData = null;
$error = null;
$debugInfo = [];

if (isset($_REQUEST['file_path']) && !empty($_REQUEST['file_path'])) {
    $fullPath = $_REQUEST['file_path'];

    $debugInfo = [
        'original_request' => $_REQUEST['file_path'],
        'used_path' => $fullPath,
        'file_exists' => file_exists($fullPath)
    ];
    
    $imageInfo = @getimagesize($fullPath);
    if ($imageInfo !== false) {
        $fileStats = stat($fullPath);
        $imageData = [
            'filename' => basename($fullPath),
            'path' => $fullPath,
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'mime_type' => $imageInfo['mime'],
            'file_size' => $fileStats['size'],
            'created' => date('Y-m-d H:i:s', $fileStats['ctime']),
            'modified' => date('Y-m-d H:i:s', $fileStats['mtime']),
            'permissions' => substr(sprintf('%o', $fileStats['mode']), -4),
            'channels' => isset($imageInfo['channels']) ? $imageInfo['channels'] : 'N/A',
            'bits' => isset($imageInfo['bits']) ? $imageInfo['bits'] : 'N/A'
        ];
        
        if ($imageData['mime_type'] === 'image/jpeg' && function_exists('exif_read_data')) {
            $exifData = @exif_read_data($fullPath);
            if ($exifData) {
                $imageData['exif'] = $exifData;
            }
        }
    } else {
        $error = "Invalid image file";
    }
} else {
    $error = "No file specified";
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}

function getMimeTypeDescription($mimeType) {
    $descriptions = [
        'image/jpeg' => 'JPEG Image',
        'image/png' => 'PNG Image', 
        'image/gif' => 'GIF Image',
        'image/webp' => 'WebP Image'
    ];
    return isset($descriptions[$mimeType]) ? $descriptions[$mimeType] : $mimeType;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Viewer - Media Server</title>
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
            color: #ffffff;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #111111;
            min-height: 100vh;
        }
        
        .header {
            background: #111111;
            color: #ffffff;
            padding: 40px 40px 20px;
            border-bottom: 1px solid #222222;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left h1 {
            font-size: 2rem;
            margin: 0;
            font-weight: 300;
            letter-spacing: -0.02em;
        }
        
        .back-btn {
            background: #333333;
            color: #ffffff;
            border: 1px solid #444444;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.15s ease;
        }
        
        .back-btn:hover {
            background: #444444;
        }
        
        .content {
            padding: 40px;
        }
        
        .error {
            background: #2d1b1b;
            color: #f87171;
            border: 1px solid #991b1b;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 16px;
        }
        
        .image-viewer {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            align-items: start;
        }
        
        .image-display {
            background: #1a1a1a;
            border: 1px solid #222222;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .image-display img {
            max-width: 100%;
            max-height: 600px;
            border-radius: 4px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .image-info {
            background: #1a1a1a;
            border: 1px solid #222222;
            border-radius: 8px;
            padding: 30px;
        }
        
        .info-section {
            margin-bottom: 30px;
        }
        
        .info-section:last-child {
            margin-bottom: 0;
        }
        
        .info-title {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #333333;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #1a1a1a;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #cccccc;
            font-size: 14px;
        }
        
        .info-value {
            color: #ffffff;
            font-size: 14px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
            background: #0a0a0a;
            padding: 4px 8px;
            border-radius: 3px;
            word-break: break-all;
        }
        
        .exif-data {
            max-height: 300px;
            overflow-y: auto;
            background: #0a0a0a;
            border: 1px solid #333333;
            border-radius: 4px;
            padding: 15px;
        }
        
        .exif-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 12px;
            border-bottom: 1px solid #1a1a1a;
        }
        
        .exif-item:last-child {
            border-bottom: none;
        }
        
        .exif-key {
            color: #888888;
            font-weight: 500;
            flex: 1;
            margin-right: 10px;
        }
        
        .exif-value {
            color: #ffffff;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
            flex: 1;
            text-align: right;
            word-break: break-all;
        }
        
        @media (max-width: 1024px) {
            .image-viewer {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .header {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .content {
                padding: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .image-info {
                padding: 20px;
            }
            
            .info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .info-value {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>Image Viewer</h1>
            </div>
            <a href="/gallery.php" class="back-btn">← Back to Gallery</a>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                    
                    <!-- 디버깅 정보 표시 (개발 중에만) -->
                    <?php if (!empty($debugInfo)): ?>
                    <details style="margin-top: 20px; background: #1a1a1a; padding: 15px; border-radius: 4px;">
                        <summary style="cursor: pointer; color: #cccccc; font-weight: 500;">Debug Information</summary>
                        <div style="margin-top: 10px; font-family: monospace; font-size: 12px;">
                            <?php foreach ($debugInfo as $key => $value): ?>
                                <div style="margin: 5px 0;">
                                    <strong><?php echo htmlspecialchars($key); ?>:</strong> 
                                    <?php if (is_array($value)): ?>
                                        <pre style="background: #0a0a0a; padding: 5px; margin: 5px 0; border-radius: 3px;"><?php echo htmlspecialchars(print_r($value, true)); ?></pre>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars(var_export($value, true)); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <?php endif; ?>
                </div>
            <?php elseif ($imageData): ?>
                <div class="image-viewer">
                    <div class="image-display">
                        <img src="<?php echo htmlspecialchars($imageData['path']); ?>" 
                             alt="<?php echo htmlspecialchars($imageData['filename']); ?>">
                    </div>
                    
                    <div class="image-info">
                        <div class="info-section">
                            <div class="info-title">Basic Information</div>
                            <div class="info-item">
                                <span class="info-label">Filename:</span>
                                <span class="info-value"><?php echo htmlspecialchars($imageData['filename']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">File Size:</span>
                                <span class="info-value"><?php echo formatBytes($imageData['file_size']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Type:</span>
                                <span class="info-value"><?php echo getMimeTypeDescription($imageData['mime_type']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">MIME Type:</span>
                                <span class="info-value"><?php echo htmlspecialchars($imageData['mime_type']); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <div class="info-title">Image Properties</div>
                            <div class="info-item">
                                <span class="info-label">Dimensions:</span>
                                <span class="info-value"><?php echo $imageData['width']; ?> × <?php echo $imageData['height']; ?> px</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Aspect Ratio:</span>
                                <span class="info-value"><?php echo round($imageData['width'] / $imageData['height'], 2); ?>:1</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Channels:</span>
                                <span class="info-value"><?php echo $imageData['channels']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Bit Depth:</span>
                                <span class="info-value"><?php echo $imageData['bits']; ?> bits</span>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <div class="info-title">File System</div>
                            <div class="info-item">
                                <span class="info-label">Created:</span>
                                <span class="info-value"><?php echo $imageData['created']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Modified:</span>
                                <span class="info-value"><?php echo $imageData['modified']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Permissions:</span>
                                <span class="info-value"><?php echo $imageData['permissions']; ?></span>
                            </div>
                        </div>
                        
                        <?php if (isset($imageData['exif']) && !empty($imageData['exif'])): ?>
                        <div class="info-section">
                            <div class="info-title">EXIF Data</div>
                            <div class="exif-data">
                                <?php foreach ($imageData['exif'] as $key => $value): ?>
                                    <?php if (is_string($value) || is_numeric($value)): ?>
                                    <div class="exif-item">
                                        <span class="exif-key"><?php echo htmlspecialchars($key); ?>:</span>
                                        <span class="exif-value"><?php echo htmlspecialchars($value); ?></span>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>