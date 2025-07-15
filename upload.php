<?php

use Shsk\Autoloader;
use Shsk\Upload\ChunkUploadHandler;
use Shsk\Upload\Config;
use Shsk\Upload\Response;
use Shsk\Upload\Logger;

require_once 'src/Shsk/Autoloader.php';
new Autoloader();

// 設定を初期化
$config = new Config([
    'upload_dir' => './uploads/',
    'temp_dir' => './temp/',
    'max_chunk_size' => 1024 * 1024 * 10, // 10MB
    'max_file_size' => 1024 * 1024 * 100, // 100MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'mp4', 'avi', 'mov'],
    'auto_create_dirs' => true,
]);

// ログを初期化
$logger = new Logger('upload.log', 'INFO');

$handler = new ChunkUploadHandler($config, $logger);
$response = new Response();

// HTTPメソッドに応じた処理
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $result = $handler->handleGetRequest();
        break;
        
    case 'PUT':
        $result = $handler->handlePutRequest();
        break;
        
    default:
        $result = $response->error('Method not allowed', 405);
        break;
}

// レスポンスを出力
$response->output($result);