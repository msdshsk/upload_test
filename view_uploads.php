<?php

use Shsk\Autoloader;
use Shsk\Upload\Config;

require_once 'src/Shsk/Autoloader.php';
new Autoloader();

// 設定を初期化
$config = new Config([
    'upload_dir' => './uploads/',
    'temp_dir' => './temp/',
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'mp4', 'avi', 'mov'],
]);

// ファイルダウンロード処理
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $fileName = basename($_GET['download']);
    $filePath = $config->getUploadDir() . $fileName;
    
    if (file_exists($filePath) && is_file($filePath)) {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if ($config->isAllowedExtension($extension)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
    
    http_response_code(404);
    exit('File not found');
}

// アップロードディレクトリのファイル一覧を取得
function getUploadedFiles($uploadDir): array
{
    $files = [];
    
    if (is_dir($uploadDir)) {
        $handle = opendir($uploadDir);
        while (($file = readdir($handle)) !== false) {
            if ($file !== '.' && $file !== '..' && is_file($uploadDir . $file)) {
                $files[] = [
                    'name' => $file,
                    'size' => filesize($uploadDir . $file),
                    'modified' => filemtime($uploadDir . $file),
                    'extension' => pathinfo($file, PATHINFO_EXTENSION)
                ];
            }
        }
        closedir($handle);
    }
    
    // 更新日時でソート
    usort($files, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    return $files;
}

function formatFileSize($bytes): string
{
    if ($bytes === 0) return '0 B';
    
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

$uploadedFiles = getUploadedFiles($config->getUploadDir());

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アップロードファイル一覧</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .file-list {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .file-list th,
        .file-list td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .file-list th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .file-list tr:hover {
            background-color: #f8f9fa;
        }
        
        .file-name {
            font-weight: 500;
            color: #007bff;
        }
        
        .file-size {
            color: #666;
        }
        
        .file-date {
            color: #666;
            font-size: 0.9em;
        }
        
        .file-extension {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .ext-image { background-color: #e1f5fe; color: #01579b; }
        .ext-document { background-color: #f3e5f5; color: #4a148c; }
        .ext-archive { background-color: #fff3e0; color: #e65100; }
        .ext-video { background-color: #e8f5e8; color: #1b5e20; }
        .ext-other { background-color: #f5f5f5; color: #424242; }
        
        .download-btn {
            display: inline-block;
            padding: 4px 8px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.8em;
        }
        
        .download-btn:hover {
            background-color: #0056b3;
        }
        
        .no-files {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #007bff;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.html" class="back-link">← アップロードページに戻る</a>
        
        <h1>アップロードファイル一覧</h1>
        
        <?php if (empty($uploadedFiles)): ?>
            <div class="no-files">
                <p>アップロードされたファイルはありません。</p>
            </div>
        <?php else: ?>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-value"><?= count($uploadedFiles) ?></div>
                    <div class="stat-label">ファイル数</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= formatFileSize(array_sum(array_column($uploadedFiles, 'size'))) ?></div>
                    <div class="stat-label">合計サイズ</div>
                </div>
            </div>
            
            <table class="file-list">
                <thead>
                    <tr>
                        <th>ファイル名</th>
                        <th>タイプ</th>
                        <th>サイズ</th>
                        <th>更新日時</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uploadedFiles as $file): ?>
                        <?php
                        $extClass = 'ext-other';
                        if (in_array($file['extension'], ['jpg', 'jpeg', 'png', 'gif'])) {
                            $extClass = 'ext-image';
                        } elseif (in_array($file['extension'], ['pdf', 'doc', 'docx', 'txt'])) {
                            $extClass = 'ext-document';
                        } elseif (in_array($file['extension'], ['zip'])) {
                            $extClass = 'ext-archive';
                        } elseif (in_array($file['extension'], ['mp4', 'avi', 'mov'])) {
                            $extClass = 'ext-video';
                        }
                        ?>
                        <tr>
                            <td class="file-name"><?= htmlspecialchars($file['name']) ?></td>
                            <td>
                                <span class="file-extension <?= $extClass ?>">
                                    <?= htmlspecialchars($file['extension']) ?>
                                </span>
                            </td>
                            <td class="file-size"><?= formatFileSize($file['size']) ?></td>
                            <td class="file-date"><?= date('Y/m/d H:i', $file['modified']) ?></td>
                            <td>
                                <a href="?download=<?= urlencode($file['name']) ?>" class="download-btn">ダウンロード</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html> 