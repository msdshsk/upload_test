<?php

namespace Shsk\Upload;

use Shsk\Upload\Config;
use Shsk\Upload\Exception\UploadException;

class Validator
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * UUIDのバリデーション
     */
    public function validateUuid(?string $uuid): void
    {
        if (empty($uuid)) {
            throw new UploadException('UUID is required');
        }

        // uniqid('', true)の形式: 英数字 + '.' + 英数字
        // より柔軟なパターンマッチング
        if (!preg_match('/^[a-f0-9]+\.[a-f0-9]+$/', $uuid)) {
            throw new UploadException("Invalid UUID format: {$uuid}");
        }

        // 基本的な長さチェック（最小限）
        if (strlen($uuid) < 10 || strlen($uuid) > 30) {
            throw new UploadException("UUID length out of range: {$uuid}");
        }
    }

    /**
     * チャンクインデックスのバリデーション
     */
    public function validateChunkIndex($index): int
    {
        if (!is_numeric($index)) {
            throw new UploadException('Chunk index must be numeric');
        }

        $index = (int)$index;
        if ($index < 0) {
            throw new UploadException('Chunk index must be non-negative');
        }

        return $index;
    }

    /**
     * チャンクデータのバリデーション
     */
    public function validateChunkData(array $fileData): void
    {
        // アップロードエラーのチェック
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new UploadException($this->getUploadErrorMessage($fileData['error']));
        }

        // ファイルサイズのチェック
        if ($fileData['size'] > $this->config->getMaxChunkSize()) {
            throw new UploadException('Chunk size exceeds maximum allowed size');
        }

        // ファイルが空でないかチェック
        if ($fileData['size'] === 0) {
            throw new UploadException('Chunk cannot be empty');
        }

        // 一時ファイルが存在するかチェック
        if (!file_exists($fileData['tmp_name'])) {
            throw new UploadException('Temporary file not found');
        }
    }

    /**
     * ファイル名のバリデーション
     */
    public function validateFileName(string $fileName): void
    {
        if (empty($fileName)) {
            throw new UploadException('File name is required');
        }

        // ファイル名の長さチェック
        if (strlen($fileName) > 255) {
            throw new UploadException('File name is too long');
        }

        // 危険な文字のチェック
        if (preg_match('/[\/\\\:\*\?\"\<\>\|]/', $fileName)) {
            throw new UploadException('File name contains invalid characters');
        }

        // パストラバーサル攻撃の防止
        if (strpos($fileName, '..') !== false) {
            throw new UploadException('File name contains invalid path');
        }

        // 拡張子のチェック
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (!$this->config->isAllowedExtension($extension)) {
            throw new UploadException("File extension '{$extension}' is not allowed");
        }
    }

    /**
     * ファイルサイズの予想値をバリデーション
     */
    public function validateExpectedFileSize(?int $expectedSize): void
    {
        if ($expectedSize !== null && $expectedSize > $this->config->getMaxFileSize()) {
            throw new UploadException('Expected file size exceeds maximum allowed size');
        }
    }

    /**
     * HTTPメソッドのバリデーション
     */
    public function validateHttpMethod(string $method): void
    {
        $allowedMethods = ['GET', 'PUT'];
        if (!in_array($method, $allowedMethods)) {
            throw new UploadException("HTTP method '{$method}' is not allowed");
        }
    }

    /**
     * アップロードエラーメッセージの取得
     */
    private function getUploadErrorMessage(int $error): string
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File size exceeds PHP upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File size exceeds HTML form MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
} 