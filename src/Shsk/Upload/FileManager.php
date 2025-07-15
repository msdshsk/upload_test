<?php

namespace Shsk\Upload;

use Shsk\Upload\Config;
use Shsk\Upload\Exception\UploadException;
use Shsk\Http\Request\PutRequestParser;

class FileManager
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        
        // ディレクトリの自動作成
        if ($this->config->getAutoCreateDirs()) {
            $this->ensureDirectoryExists($this->config->getTempDir());
            $this->ensureDirectoryExists($this->config->getUploadDir());
        }
    }

    /**
     * チャンクを保存
     */
    public function saveChunk(string $uuid, int $index, string $tmpFile): string
    {
        $chunkPath = $this->getChunkPath($uuid, $index);
        
        if (!PutRequestParser::moveUploadedFile($tmpFile, $chunkPath)) {
            throw new UploadException("Failed to save chunk: {$index}");
        }

        return $chunkPath;
    }

    /**
     * チャンクを結合してファイルを作成
     */
    public function joinChunks(string $uuid, string $fileName): string
    {
        $chunks = $this->getChunkFiles($uuid);
        if (empty($chunks)) {
            throw new UploadException("No chunks found for UUID: {$uuid}");
        }

        $finalPath = $this->getFinalFilePath($fileName);
        
        // ファイルを結合
        $fp = fopen($finalPath, 'wb');
        if (!$fp) {
            throw new UploadException("Failed to create final file: {$finalPath}");
        }

        foreach ($chunks as $chunkPath) {
            $chunkFp = fopen($chunkPath, 'rb');
            if (!$chunkFp) {
                fclose($fp);
                throw new UploadException("Failed to read chunk: {$chunkPath}");
            }

            while (!feof($chunkFp)) {
                $data = fread($chunkFp, 8192);
                fwrite($fp, $data);
            }
            fclose($chunkFp);
        }

        fclose($fp);

        // チャンクファイルを削除
        $this->cleanupChunks($chunks);

        return $finalPath;
    }

    /**
     * チャンクファイルのパスを取得
     */
    private function getChunkPath(string $uuid, int $index): string
    {
        return $this->config->getTempDir() . sprintf("%s---%08d", $uuid, $index);
    }

    /**
     * チャンクファイルの一覧を取得
     */
    private function getChunkFiles(string $uuid): array
    {
        $pattern = $this->config->getTempDir() . $uuid . "---*";
        $files = glob($pattern);
        
        if ($files === false) {
            return [];
        }

        sort($files);
        return $files;
    }

    /**
     * 最終ファイルのパスを取得（重複時は番号を追加）
     */
    private function getFinalFilePath(string $fileName): string
    {
        $basePath = $this->config->getUploadDir() . $fileName;
        
        if (!file_exists($basePath)) {
            return $basePath;
        }

        $pathInfo = pathinfo($fileName);
        $name = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';
        $dir = $this->config->getUploadDir();

        $counter = 2;
        while (true) {
            $newName = $extension ? "{$name}({$counter}).{$extension}" : "{$name}({$counter})";
            $newPath = $dir . $newName;
            
            if (!file_exists($newPath)) {
                return $newPath;
            }
            
            $counter++;
        }
    }



    /**
     * チャンクファイルをクリーンアップ
     */
    private function cleanupChunks(array $chunkPaths): void
    {
        foreach ($chunkPaths as $chunkPath) {
            if (file_exists($chunkPath)) {
                unlink($chunkPath);
            }
        }
    }

    /**
     * 古いチャンクファイルをクリーンアップ（定期実行用）
     */
    public function cleanupOldChunks(int $maxAge = 3600): void
    {
        $pattern = $this->config->getTempDir() . "*---*";
        $files = glob($pattern);
        
        if ($files === false) {
            return;
        }

        $now = time();
        foreach ($files as $file) {
            if (($now - filemtime($file)) > $maxAge) {
                unlink($file);
            }
        }
    }

    /**
     * ディレクトリが存在しない場合は作成
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new UploadException("Failed to create directory: {$directory}");
            }
        }
    }
} 